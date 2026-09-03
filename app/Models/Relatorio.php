<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Motor de relatórios comparativos (mês a mês). Cada relatório devolve a MESMA estrutura
 * normalizada — a view `relatorios/index` renderiza qualquer um genericamente:
 *
 *   [
 *     'titulo'      => string,
 *     'formato'     => 'money' | 'pct' | 'decimal' | 'int',
 *     'direcao'     => 'maior_melhor' | 'menor_melhor',   // colore a variação
 *     'colDimensao' => 'Filial' | 'Categoria' | ...,
 *     'periodos'    => list<array{id:int, rotulo:string}>,      // antigo -> recente
 *     'linhas'      => list<array{rotulo:string, valores: array<int, ?float>}>,
 *     'total'       => array{rotulo:string, valores: array<int, ?float>} | null,
 *     'nota'        => ?string,
 *   ]
 *
 * Só leitura / agregação — não captura nada. Relatórios de comissão/pontuação/nível leem o
 * snapshot `comissao_calculada` (só filiais com fechamento aprovado); os de venda leem as
 * tabelas ao vivo, servindo qualquer mês.
 */
final class Relatorio
{
    private const MESES = [1=>'jan',2=>'fev',3=>'mar',4=>'abr',5=>'mai',6=>'jun',7=>'jul',8=>'ago',9=>'set',10=>'out',11=>'nov',12=>'dez'];

    /** Colunas numéricas de comissao_calculada que um relatório pode somar (whitelist). */
    private const METRICAS_COMISSAO = [
        'total' => 'Total (comissão + prêmio)',
        'comissao_base' => 'Comissão base',
        'comissao_ajustada' => 'Comissão ajustada',
        'premio_filial' => 'Prêmio de filial',
    ];

    /** Métricas de qualidade: [chave => [rótulo, coluna/origem, formato, direção]]. */
    private const METRICAS_INDICADOR = [
        'ticket_medio' => ['Ticket médio', 'ticket_medio', 'money', 'maior_melhor'],
        'desconto_medio' => ['Desconto médio', 'desconto_medio', 'pct', 'menor_melhor'],
        'rentab_funcionario' => ['Rentabilidade (média dos funcionários)', 'rentabilidade_pct', 'pct', 'maior_melhor'],
        'rentab_filial' => ['Rentabilidade da filial', '__rentab_filial__', 'pct', 'maior_melhor'],
    ];

    /**
     * Catálogo: chave => metadados. `filtros` diz quais controles a tela mostra pra esse relatório:
     *  'filial'            = seletor Rede + filiais
     *  'filial_obrigatoria'= seletor só de filiais (sem Rede)
     *  'metrica_comissao'  = seletor das colunas de comissão
     *  'metrica_indicador' = seletor das métricas de qualidade
     */
    public static function catalogo(): array
    {
        return [
            'venda_bruta_filial' => ['titulo' => 'Venda bruta por filial', 'familia' => 'Vendas', 'filtros' => []],
            'venda_categoria' => ['titulo' => 'Venda por categoria', 'familia' => 'Vendas', 'filtros' => ['filial']],
            'realizado_meta' => ['titulo' => 'Realizado × meta por filial (%)', 'familia' => 'Vendas', 'filtros' => []],
            'mix_categoria' => ['titulo' => 'Mix de vendas por categoria (%)', 'familia' => 'Vendas', 'filtros' => ['filial']],
            'comissao_filial' => ['titulo' => 'Comissão paga por filial', 'familia' => 'Comissão & Pontuação', 'filtros' => ['metrica_comissao']],
            'comissao_funcionario' => ['titulo' => 'Comissão por funcionário (numa filial)', 'familia' => 'Comissão & Pontuação', 'filtros' => ['filial_obrigatoria', 'metrica_comissao']],
            'pontuacao_media' => ['titulo' => 'Pontuação 360 média por filial', 'familia' => 'Comissão & Pontuação', 'filtros' => []],
            'distribuicao_niveis' => ['titulo' => 'Distribuição de níveis', 'familia' => 'Comissão & Pontuação', 'filtros' => ['filial']],
            'indicadores_filial' => ['titulo' => 'Indicadores de qualidade por filial', 'familia' => 'Qualidade', 'filtros' => ['metrica_indicador']],
            'checklist_filial' => ['titulo' => 'Checklist de equipe por filial', 'familia' => 'Qualidade', 'filtros' => []],
        ];
    }

    public static function metricasComissao(): array
    {
        return self::METRICAS_COMISSAO;
    }

    public static function metricasIndicador(): array
    {
        return array_map(static fn (array $m): string => $m[0], self::METRICAS_INDICADOR);
    }

    /**
     * @param list<array<string,mixed>> $periodos linhas de `periodo`, ordenadas antigo -> recente
     * @param int    $filialId 0 = rede
     * @param string $metrica  chave da métrica (relatórios que têm seletor)
     */
    public static function gerar(string $rel, array $periodos, int $filialId, string $metrica): array
    {
        $ids = array_map(static fn (array $p): int => (int) $p['id'], $periodos);
        $rotulos = [];
        foreach ($periodos as $p) {
            $rotulos[] = ['id' => (int) $p['id'], 'rotulo' => self::MESES[(int) $p['mes']] . '/' . substr((string) $p['ano'], 2)];
        }

        // Defaults preenchidos só onde o relatório não define (união com o report à esquerda ganhando).
        $base = ['nota' => null, 'direcao' => 'maior_melhor'];

        $report = match ($rel) {
            'venda_bruta_filial' => self::vendaBrutaFilial($ids),
            'venda_categoria' => self::vendaCategoria($ids, $filialId),
            'realizado_meta' => self::realizadoMeta($ids),
            'mix_categoria' => self::mixCategoria($ids, $filialId),
            'comissao_filial' => self::comissaoFilial($ids, $metrica),
            'comissao_funcionario' => self::comissaoFuncionario($ids, $filialId, $metrica),
            'pontuacao_media' => self::pontuacaoMedia($ids),
            'distribuicao_niveis' => self::distribuicaoNiveis($ids, $filialId),
            'indicadores_filial' => self::indicadoresFilial($ids, $metrica),
            'checklist_filial' => self::checklistFilial($ids),
            default => ['titulo' => '—', 'formato' => 'int', 'colDimensao' => '—', 'linhas' => [], 'total' => null],
        };

        return ['periodos' => $rotulos] + $report + $base;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** @param list<int> $ids */
    private static function in(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }

    /** Filiais ativas como [id => nome], na ordem de exibição. */
    private static function filiais(): array
    {
        $out = [];
        foreach (Filial::ativas() as $f) {
            $out[(int) $f['id']] = $f['nome'];
        }

        return $out;
    }

    /**
     * Monta as linhas a partir de um mapa [dimKey => [periodoId => valor]] e um dicionário de rótulos.
     * @param array<int|string, array<int, float>> $mapa
     * @param array<int|string, string> $rotulos dimKey => rótulo (define a ordem das linhas)
     * @param list<int> $periodoIds
     * @return list<array{rotulo:string, valores: array<int, ?float>}>
     */
    private static function linhas(array $mapa, array $rotulos, array $periodoIds, bool $zeroSemDado = false): array
    {
        $linhas = [];
        foreach ($rotulos as $key => $rotulo) {
            $valores = [];
            foreach ($periodoIds as $pid) {
                $valores[$pid] = $mapa[$key][$pid] ?? ($zeroSemDado ? 0.0 : null);
            }
            $linhas[] = ['rotulo' => $rotulo, 'valores' => $valores];
        }

        return $linhas;
    }

    /** Linha de total = soma das linhas por período. @param list<int> $periodoIds */
    private static function totalSoma(array $linhas, array $periodoIds, string $rotulo): array
    {
        $valores = [];
        foreach ($periodoIds as $pid) {
            $soma = null;
            foreach ($linhas as $l) {
                if ($l['valores'][$pid] !== null) {
                    $soma = ($soma ?? 0.0) + $l['valores'][$pid];
                }
            }
            $valores[$pid] = $soma;
        }

        return ['rotulo' => $rotulo, 'valores' => $valores];
    }

    // ----------------------------------------------------------------
    // Relatórios — Vendas
    // ----------------------------------------------------------------

    /** @param list<int> $ids */
    private static function vendaBrutaFilial(array $ids): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT periodo_id, filial_id, SUM(valor) v FROM venda_bruta_lancamento
             WHERE periodo_id IN (' . self::in($ids) . ') GROUP BY periodo_id, filial_id'
        );
        $stmt->execute($ids);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['filial_id']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $linhas = self::linhas($mapa, self::filiais(), $ids);

        return [
            'titulo' => 'Venda bruta por filial',
            'formato' => 'money',
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Rede'),
            'nota' => 'Soma dos lançamentos diários de venda bruta da filial (/vendas).',
        ];
    }

    /** @param list<int> $ids */
    private static function vendaCategoria(array $ids, int $filialId): array
    {
        $sql = 'SELECT periodo_id, categoria_id, SUM(valor) v FROM venda_lancamento
                WHERE periodo_id IN (' . self::in($ids) . ')';
        $params = $ids;
        if ($filialId > 0) {
            $sql .= ' AND filial_id = ?';
            $params[] = $filialId;
        }
        $sql .= ' GROUP BY periodo_id, categoria_id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['categoria_id']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $rotulos = [];
        foreach (Categoria::ativas() as $c) {
            $rotulos[(int) $c['id']] = $c['nome'];
        }
        $linhas = self::linhas($mapa, $rotulos, $ids);

        return [
            'titulo' => 'Venda por categoria' . ($filialId > 0 ? ' — ' . (self::filiais()[$filialId] ?? '') : ' — Rede'),
            'formato' => 'money',
            'colDimensao' => 'Categoria',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Total'),
            'nota' => 'Realizado da grade por funcionário/categoria (/vendas).',
        ];
    }

    /** @param list<int> $ids */
    private static function realizadoMeta(array $ids): array
    {
        $pdo = Database::pdo();

        $realizado = [];
        $stmt = $pdo->prepare(
            'SELECT periodo_id, filial_id, SUM(valor) v FROM venda_bruta_lancamento
             WHERE periodo_id IN (' . self::in($ids) . ') GROUP BY periodo_id, filial_id'
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) {
            $realizado[(int) $r['filial_id']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $meta = [];
        $stmt = $pdo->prepare(
            'SELECT periodo_id, filial_id, meta_venda FROM meta_filial WHERE periodo_id IN (' . self::in($ids) . ')'
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $r) {
            $meta[(int) $r['filial_id']][(int) $r['periodo_id']] = (float) $r['meta_venda'];
        }

        $filiais = self::filiais();
        $linhas = [];
        $realTot = [];
        $metaTot = [];
        foreach ($filiais as $fid => $nome) {
            $valores = [];
            foreach ($ids as $pid) {
                $m = $meta[$fid][$pid] ?? 0.0;
                $rz = $realizado[$fid][$pid] ?? null;
                $valores[$pid] = ($m > 0 && $rz !== null) ? round($rz / $m * 100, 1) : null;
                if ($m > 0) {
                    $metaTot[$pid] = ($metaTot[$pid] ?? 0.0) + $m;
                    $realTot[$pid] = ($realTot[$pid] ?? 0.0) + ($rz ?? 0.0);
                }
            }
            $linhas[] = ['rotulo' => $nome, 'valores' => $valores];
        }

        $totalValores = [];
        foreach ($ids as $pid) {
            $totalValores[$pid] = !empty($metaTot[$pid]) ? round(($realTot[$pid] ?? 0.0) / $metaTot[$pid] * 100, 1) : null;
        }

        return [
            'titulo' => 'Realizado × meta por filial (% de atingimento)',
            'formato' => 'pct',
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => ['rotulo' => 'Rede', 'valores' => $totalValores],
            'nota' => 'Realizado = venda bruta lançada; meta = meta de venda da filial (/metas). 100% = meta batida.',
        ];
    }

    /** @param list<int> $ids */
    private static function mixCategoria(array $ids, int $filialId): array
    {
        $tracked = [];
        foreach (Categoria::ativas() as $c) {
            if ($c['meta_percentual_pct'] !== null) {
                $simbolo = $c['meta_percentual_tipo'] === 'teto' ? '≤' : '≥';
                $tracked[(int) $c['id']] = $c['nome'] . ' (meta ' . $simbolo . ' '
                    . rtrim(rtrim(number_format((float) $c['meta_percentual_pct'], 1, ',', ''), '0'), ',') . '%)';
            }
        }

        $mapa = [];
        foreach ($ids as $pid) {
            $porCat = $filialId > 0
                ? (Meta::mixRealizadoPorFilial($pid)[$filialId] ?? [])
                : Meta::mixRealizadoRede($pid);
            $totalPeriodo = array_sum($porCat);
            if ($totalPeriodo <= 0) {
                continue;
            }
            foreach ($tracked as $catId => $_) {
                $mapa[$catId][$pid] = round(($porCat[$catId] ?? 0.0) / $totalPeriodo * 100, 1);
            }
        }

        $linhas = self::linhas($mapa, $tracked, $ids);

        return [
            'titulo' => 'Mix de vendas por categoria (% do total)' . ($filialId > 0 ? ' — ' . (self::filiais()[$filialId] ?? '') : ' — Rede'),
            'formato' => 'pct',
            'colDimensao' => 'Categoria',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Total rastreado'),
            'nota' => 'Base: recorte por categoria lançado junto da venda bruta (acumulado do mês). RX tem meta de teto (quanto menor, melhor).',
        ];
    }

    // ----------------------------------------------------------------
    // Relatórios — Comissão & Pontuação (só filiais com fechamento aprovado)
    // ----------------------------------------------------------------

    private const NOTA_FECHADO = 'Só entram filiais com fechamento aprovado no mês. Meses/filiais em aberto aparecem vazios.';

    /** @param list<int> $ids */
    private static function comissaoFilial(array $ids, string $metrica): array
    {
        $col = array_key_exists($metrica, self::METRICAS_COMISSAO) ? $metrica : 'total';

        $stmt = Database::pdo()->prepare(
            "SELECT periodo_id, filial_id, SUM($col) v FROM comissao_calculada
             WHERE periodo_id IN (" . self::in($ids) . ') GROUP BY periodo_id, filial_id'
        );
        $stmt->execute($ids);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['filial_id']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $linhas = self::linhas($mapa, self::filiais(), $ids);

        return [
            'titulo' => 'Comissão paga por filial — ' . self::METRICAS_COMISSAO[$col],
            'formato' => 'money',
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Rede'),
            'nota' => self::NOTA_FECHADO,
        ];
    }

    /** @param list<int> $ids */
    private static function comissaoFuncionario(array $ids, int $filialId, string $metrica): array
    {
        $col = array_key_exists($metrica, self::METRICAS_COMISSAO) ? $metrica : 'total';

        $stmt = Database::pdo()->prepare(
            "SELECT cc.periodo_id, cc.funcionario_id, f.nome, SUM(cc.$col) v
               FROM comissao_calculada cc JOIN funcionario f ON f.id = cc.funcionario_id
              WHERE cc.periodo_id IN (" . self::in($ids) . ') AND cc.filial_id = ?
              GROUP BY cc.periodo_id, cc.funcionario_id, f.nome'
        );
        $stmt->execute([...$ids, $filialId]);

        $mapa = [];
        $rotulos = [];
        foreach ($stmt->fetchAll() as $r) {
            $fid = (int) $r['funcionario_id'];
            $mapa[$fid][(int) $r['periodo_id']] = (float) $r['v'];
            $rotulos[$fid] = (string) $r['nome'];
        }
        asort($rotulos);

        $linhas = self::linhas($mapa, $rotulos, $ids);

        return [
            'titulo' => 'Comissão por funcionário — ' . (self::filiais()[$filialId] ?? 'filial') . ' · ' . self::METRICAS_COMISSAO[$col],
            'formato' => 'money',
            'colDimensao' => 'Funcionário',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Total'),
            'nota' => self::NOTA_FECHADO,
        ];
    }

    /** @param list<int> $ids */
    private static function pontuacaoMedia(array $ids): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT periodo_id, filial_id, SUM(pontuacao_360) s, COUNT(*) n FROM comissao_calculada
             WHERE periodo_id IN (' . self::in($ids) . ') GROUP BY periodo_id, filial_id'
        );
        $stmt->execute($ids);

        $mapa = [];
        $somaRede = [];
        $nRede = [];
        foreach ($stmt->fetchAll() as $r) {
            $pid = (int) $r['periodo_id'];
            $mapa[(int) $r['filial_id']][$pid] = (int) $r['n'] > 0 ? round((float) $r['s'] / (int) $r['n'], 1) : null;
            $somaRede[$pid] = ($somaRede[$pid] ?? 0.0) + (float) $r['s'];
            $nRede[$pid] = ($nRede[$pid] ?? 0) + (int) $r['n'];
        }

        $linhas = self::linhas($mapa, self::filiais(), $ids);
        $totalValores = [];
        foreach ($ids as $pid) {
            $totalValores[$pid] = !empty($nRede[$pid]) ? round($somaRede[$pid] / $nRede[$pid], 1) : null;
        }

        return [
            'titulo' => 'Pontuação 360 média por filial',
            'formato' => 'decimal',
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => ['rotulo' => 'Média da rede', 'valores' => $totalValores],
            'nota' => self::NOTA_FECHADO,
        ];
    }

    /** @param list<int> $ids */
    private static function distribuicaoNiveis(array $ids, int $filialId): array
    {
        $ordem = Database::pdo()->query('SELECT nivel FROM multiplicador_faixa ORDER BY pontos_de')->fetchAll(\PDO::FETCH_COLUMN);

        $sql = 'SELECT cc.periodo_id, mf.nivel, COUNT(*) v
                  FROM comissao_calculada cc
                  JOIN multiplicador_faixa mf ON ROUND(cc.pontuacao_360) BETWEEN mf.pontos_de AND mf.pontos_ate
                 WHERE cc.periodo_id IN (' . self::in($ids) . ')';
        $params = $ids;
        if ($filialId > 0) {
            $sql .= ' AND cc.filial_id = ?';
            $params[] = $filialId;
        }
        $sql .= ' GROUP BY cc.periodo_id, mf.nivel';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(string) $r['nivel']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $rotulos = [];
        foreach ($ordem as $nivel) {
            $rotulos[$nivel] = $nivel;
        }
        $linhas = self::linhas($mapa, $rotulos, $ids, true);

        return [
            'titulo' => 'Distribuição de níveis' . ($filialId > 0 ? ' — ' . (self::filiais()[$filialId] ?? '') : ' — Rede'),
            'formato' => 'int',
            'colDimensao' => 'Nível',
            'linhas' => $linhas,
            'total' => self::totalSoma($linhas, $ids, 'Funcionários'),
            'nota' => self::NOTA_FECHADO,
        ];
    }

    // ----------------------------------------------------------------
    // Relatórios — Qualidade
    // ----------------------------------------------------------------

    /** @param list<int> $ids */
    private static function indicadoresFilial(array $ids, string $metrica): array
    {
        [$rotulo, $origem, $formato, $direcao] = self::METRICAS_INDICADOR[$metrica] ?? self::METRICAS_INDICADOR['ticket_medio'];

        $mapa = [];
        $somaRede = [];
        $nRede = [];

        if ($origem === '__rentab_filial__') {
            $stmt = Database::pdo()->prepare(
                'SELECT periodo_id, filial_id, rentabilidade_pct v FROM rentabilidade_filial
                 WHERE periodo_id IN (' . self::in($ids) . ')'
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $r) {
                $pid = (int) $r['periodo_id'];
                $mapa[(int) $r['filial_id']][$pid] = (float) $r['v'];
                $somaRede[$pid] = ($somaRede[$pid] ?? 0.0) + (float) $r['v'];
                $nRede[$pid] = ($nRede[$pid] ?? 0) + 1;
            }
        } else {
            $stmt = Database::pdo()->prepare(
                "SELECT i.periodo_id, ff.filial_id, SUM(i.$origem) s, COUNT(*) n
                   FROM indicador_funcionario i
                   JOIN funcionario_filial ff ON ff.funcionario_id = i.funcionario_id AND ff.principal = 1
                  WHERE i.periodo_id IN (" . self::in($ids) . ')
                  GROUP BY i.periodo_id, ff.filial_id'
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $r) {
                $pid = (int) $r['periodo_id'];
                $n = (int) $r['n'];
                $mapa[(int) $r['filial_id']][$pid] = $n > 0 ? round((float) $r['s'] / $n, 2) : null;
                $somaRede[$pid] = ($somaRede[$pid] ?? 0.0) + (float) $r['s'];
                $nRede[$pid] = ($nRede[$pid] ?? 0) + $n;
            }
        }

        $linhas = self::linhas($mapa, self::filiais(), $ids);
        $totalValores = [];
        foreach ($ids as $pid) {
            $totalValores[$pid] = !empty($nRede[$pid]) ? round($somaRede[$pid] / $nRede[$pid], 2) : null;
        }

        return [
            'titulo' => 'Indicadores de qualidade por filial — ' . $rotulo,
            'formato' => $formato,
            'direcao' => $direcao,
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => ['rotulo' => 'Média da rede', 'valores' => $totalValores],
            'nota' => $origem === '__rentab_filial__'
                ? 'Rentabilidade lançada por filial no fechamento (/indicadores).'
                : 'Média dos indicadores lançados por funcionário (/indicadores), agrupada pela filial principal.',
        ];
    }

    /** @param list<int> $ids */
    private static function checklistFilial(array $ids): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT periodo_id, filial_id,
                    (c1_sem_falta_injustificada + c2_cumpriu_escala + c3_setor_organizado + c4_ajudou_treinou_colega
                     + c5_loja_bateu_meta_coletiva + c6_venda_5_catalogos + c7_venda_30_a_vencer + c8_venda_30_linha_propria) v
               FROM checklist_equipe WHERE periodo_id IN (' . self::in($ids) . ')'
        );
        $stmt->execute($ids);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['filial_id']][(int) $r['periodo_id']] = (float) $r['v'];
        }

        $linhas = self::linhas($mapa, self::filiais(), $ids);

        return [
            'titulo' => 'Checklist de equipe por filial (itens cumpridos, de 8)',
            'formato' => 'int',
            'colDimensao' => 'Filial',
            'linhas' => $linhas,
            'total' => null,
            'nota' => 'Quantos dos 8 itens do checklist de equipe a filial cumpriu no mês (/indicadores).',
        ];
    }
}
