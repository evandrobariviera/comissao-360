<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Viz;
use App\Models\Categoria;
use App\Models\Corrida;
use App\Models\Filial;
use App\Models\Funcionario;
use App\Models\Indicador;
use App\Models\Meta;
use App\Models\Periodo;
use App\Models\Venda;
use App\Services\CorridaCalculator;
use App\Services\ResumoCalculator;
use App\Services\RitmoDiarioCalculator;

final class DashboardController extends Controller
{
    /**
     * Landing de /dashboard: admin vê a rede, gerente e funcionário veem o mesmo painel pessoal
     * ("minha performance"). O que o gerente tem de estratégico (visão agregada da filial,
     * oportunidades da equipe, ranking) fica em /painel-filial — não é mais a tela de entrada dele.
     */
    public function index(): void
    {
        Auth::require();

        $papel = Auth::papel();
        $periodo = Periodo::ativo();

        match ($papel) {
            Auth::PAPEL_ADMIN => $this->rede((int) $periodo['id'], $periodo),
            default => $this->pessoal((int) $periodo['id'], $periodo),
        };
    }

    /** Painel estratégico da filial — só gerente (o admin já tem a visão de rede em /dashboard). */
    public function painelFilial(): void
    {
        Auth::require(Auth::PAPEL_GERENTE);
        $periodo = Periodo::ativo();
        $this->porFilial((int) $periodo['id'], $periodo);
    }

    private function rede(int $periodoId, array $periodo): void
    {
        $linhas = ResumoCalculator::rede($periodoId);
        $filiais = Filial::ativas();

        $comissaoTotal = array_sum(array_column($linhas, 'total'));
        $vendaRealizada = Meta::totalRedeVendaBrutaRealizada($periodoId);
        $metaRede = Meta::totalRedeVenda($periodoId);

        $filiaisComMeta = [];
        $filiaisBateram = 0;
        $vendaBrutaPorFilial = [];
        foreach ($filiais as $f) {
            $filialId = (int) $f['id'];
            $meta = Meta::filial($filialId, $periodoId);
            $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
            $realizado = $meta !== null ? (float) $meta['venda_bruta_realizada'] : 0.0;
            if ($metaVenda > 0 && $realizado >= $metaVenda) {
                $filiaisBateram++;
            }
            $filiaisComMeta[] = ['nome' => $f['nome'], 'realizado' => $realizado, 'meta' => $metaVenda];
            $vendaBrutaPorFilial[$filialId] = $realizado;
        }

        [$nomesFiliaisMix, $mixLinhas] = self::montarMixGrade($filiais, $vendaBrutaPorFilial, $vendaRealizada, $periodoId);

        $nomeFilialPorId = [];
        foreach ($filiais as $f) {
            $nomeFilialPorId[(int) $f['id']] = $f['nome'];
        }

        $ranking = array_map(
            static fn ($l) => [
                'nome' => $l['nome'],
                'valor' => $l['total'],
                'formatado' => Viz::money($l['total']),
                'sub' => $nomeFilialPorId[(int) $l['filial_id']] ?? null,
                'nivel' => $l['pontuacao']['nivel'] ?? null,
            ],
            $linhas
        );
        usort($ranking, static fn ($a, $b) => $b['valor'] <=> $a['valor']);
        $ranking = array_slice($ranking, 0, 10);

        // Distribuição por faixa da Meta 360 (grade 2×2), da melhor para a pior.
        $faixas = Database::pdo()->query(
            'SELECT nivel, multiplicador, pontos_de, pontos_ate FROM multiplicador_faixa ORDER BY pontos_de DESC'
        )->fetchAll(\PDO::FETCH_ASSOC);
        $contagem = array_count_values(array_column(array_column($linhas, 'pontuacao'), 'nivel'));
        $distribuicao = [];
        foreach ($faixas as $fx) {
            $mult = (float) $fx['multiplicador'];
            $distribuicao[] = [
                'nome' => $fx['nivel'] . ' (' . (int) $fx['pontos_de'] . '–' . (int) $fx['pontos_ate'] . ')',
                'count' => $contagem[$fx['nivel']] ?? 0,
                'sub' => 'multiplicador ' . number_format($mult, 2, ',', '.') . '×',
                'cor' => $mult >= 1.5 ? 'green' : ($mult >= 1.2 ? 'blue' : ($mult < 1.0 ? 'red' : '')),
            ];
        }

        $this->render('dashboard/rede', [
            'periodo' => $periodo,
            'comissaoTotal' => $comissaoTotal,
            'vendaRealizada' => $vendaRealizada,
            'metaRede' => $metaRede,
            'totalFuncionarios' => count($linhas),
            'filiaisBateram' => $filiaisBateram,
            'totalFiliais' => count($filiais),
            'filiaisComMeta' => $filiaisComMeta,
            'ranking' => $ranking,
            'distribuicao' => $distribuicao,
            'oportunidades' => self::oportunidades($linhas, 5),
            'mixNomesFiliais' => $nomesFiliaisMix,
            'mixLinhas' => $mixLinhas,
            'corrida' => self::corridaParaPainel(),
        ]);
    }

    /**
     * Resumo compacto da Corrida dos Campeões para a faixa do Painel da rede:
     * edição padrão (aberta mais recente) + top 3 por grupo, ao vivo. Null se
     * não houver edição. Reusa o mesmo cálculo de CorridaController::dadosComuns().
     *
     * @return array{edicao: array{nome:string, rotulo:string}, grupos: array<int, array{grupo:string, premio:string, linhas: array<int, array{nome:string, premio:string}>}>, diasRestantes:int}|null
     */
    private static function corridaParaPainel(): ?array
    {
        $edicao = Corrida::edicaoPadrao();
        if ($edicao === null) {
            return null;
        }

        $grupos = [];
        foreach (Corrida::grupos((int) $edicao['id']) as $grupo) {
            $linhas = CorridaCalculator::rankingGrupo(
                Corrida::lancamentosDoGrupo((int) $grupo['id']),
                (float) $grupo['premio_bruto']
            );
            $top = [];
            foreach (array_slice($linhas, 0, 3) as $l) {
                $top[] = ['nome' => $l['nome'], 'premio' => Viz::money($l['premio'])];
            }
            $grupos[] = [
                'grupo' => $grupo['nome'],
                'premio' => Viz::money((float) $grupo['premio_bruto']),
                'linhas' => $top,
            ];
        }

        $fim = new \DateTimeImmutable((string) $edicao['data_fim']);
        $hoje = new \DateTimeImmutable('today');
        $diasRestantes = (int) $hoje->diff($fim)->format('%r%a');

        $nome = trim((string) ($edicao['nome'] ?? '')) !== ''
            ? (string) $edicao['nome']
            : 'Corrida dos Campeões — ' . (int) $edicao['trimestre'] . 'º tri';
        $rotulo = (int) $edicao['trimestre'] . 'º tri/' . (int) $edicao['ano']
            . ' · encerra ' . $fim->format('d/m');

        return [
            'edicao' => ['nome' => $nome, 'rotulo' => $rotulo],
            'grupos' => $grupos,
            'diasRestantes' => $diasRestantes,
        ];
    }

    private function porFilial(int $periodoId, array $periodo): void
    {
        $filiaisPermitidas = Funcionario::filiaisDoUsuario((int) Auth::id());
        if (empty($filiaisPermitidas)) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $filialId = (int) ($this->input('filial_id', $filiaisPermitidas[0]['id']));
        $ids = array_column($filiaisPermitidas, 'id');
        if (!in_array($filialId, $ids, true)) {
            $filialId = (int) $ids[0];
        }

        $linhas = ResumoCalculator::porFilial($filialId, $periodoId);
        $meta = Meta::filial($filialId, $periodoId);
        $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
        $metaRentab = $meta !== null ? (float) $meta['meta_rentabilidade'] : 0.0;
        $realizado = $meta !== null ? (float) $meta['venda_bruta_realizada'] : 0.0;
        $rentabFilial = Indicador::rentabilidadeFilial($filialId, $periodoId);
        $rentabRealizada = $rentabFilial !== null ? (float) $rentabFilial['rentabilidade_pct'] : 0.0;
        $comissaoTotal = array_sum(array_column($linhas, 'total'));

        $ranking = array_map(
            static fn ($l) => ['nome' => $l['nome'], 'valor' => $l['total'], 'formatado' => \App\Core\Viz::money($l['total'])],
            $linhas
        );
        usort($ranking, static fn ($a, $b) => $b['valor'] <=> $a['valor']);

        $this->render('dashboard/filial', [
            'periodo' => $periodo,
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'metaVenda' => $metaVenda,
            'metaRentab' => $metaRentab,
            'rentabRealizada' => $rentabRealizada,
            'realizado' => $realizado,
            'comissaoTotal' => $comissaoTotal,
            'totalFuncionarios' => count($linhas),
            'ranking' => $ranking,
            'oportunidades' => self::oportunidades($linhas, 5),
            'ritmo' => RitmoDiarioCalculator::calcular($filialId, $periodoId, $periodo),
            'mediasFilial' => Indicador::mediasFilial($filialId, $periodoId),
        ]);
    }

    /**
     * Monta [nomesFiliais, linhas] pro Viz::mixGrade() — usado tanto no painel de rede (todas as
     * filiais) quanto no painel pessoal (só a filial da pessoa, com a coluna Rede pra comparação).
     * $vendaBrutaPorFilialRede precisa cobrir TODAS as filiais da rede (não só as de $filiais),
     * senão o total de rede usado no denominador da coluna "Rede" fica incompleto.
     *
     * @param array<int, array{id:int|string, nome:string}> $filiais colunas a mostrar, em ordem
     * @param array<int, float> $vendaBrutaPorFilialRede [filial_id => venda bruta do mês], rede toda
     * @return array{0: array<int,string>, 1: array}
     */
    private static function montarMixGrade(array $filiais, array $vendaBrutaPorFilialRede, float $vendaRealizadaRede, int $periodoId): array
    {
        $nomesFiliais = [];
        foreach ($filiais as $f) {
            $nomesFiliais[(int) $f['id']] = $f['nome'];
        }

        $mixPorFilial = Meta::mixRealizadoPorFilial($periodoId);
        $mixTotaisRede = [];
        foreach ($mixPorFilial as $porCategoria) {
            foreach ($porCategoria as $catId => $valor) {
                $mixTotaisRede[$catId] = ($mixTotaisRede[$catId] ?? 0.0) + $valor;
            }
        }

        $linhas = [];
        foreach (Categoria::comMetaPercentual() as $c) {
            $catId = (int) $c['id'];
            $porFilial = [];
            foreach ($nomesFiliais as $filialId => $nomeFilial) {
                $totalFilial = $vendaBrutaPorFilialRede[$filialId] ?? 0.0;
                $valorFilial = $mixPorFilial[$filialId][$catId] ?? 0.0;
                $porFilial[$filialId] = $totalFilial > 0 ? ($valorFilial / $totalFilial) * 100 : 0.0;
            }
            $linhas[] = [
                'nome' => $c['nome'],
                'meta_pct' => (float) $c['meta_percentual_pct'],
                'maior_melhor' => ($c['meta_percentual_tipo'] ?? 'piso') === 'piso',
                'rede_pct' => $vendaRealizadaRede > 0 ? (($mixTotaisRede[$catId] ?? 0.0) / $vendaRealizadaRede) * 100 : 0.0,
                'por_filial' => $porFilial,
            ];
        }

        return [$nomesFiliais, $linhas];
    }

    /**
     * Achata o detalhe por categoria de várias pessoas numa lista única de
     * "faltam R$X para a próxima faixa", ordenada pela oportunidade mais perto de acontecer.
     *
     * @param array<int, array> $linhas
     * @return array<int, array{nome:string, categoria:string, falta:float, ganho:float, percentual:float, percentual_proximo:float}>
     */
    private static function oportunidades(array $linhas, int $limite): array
    {
        $achatado = [];
        foreach ($linhas as $l) {
            foreach ($l['detalhe_categorias'] as $d) {
                if ($d['proxima_faixa'] === null) {
                    continue;
                }
                $achatado[] = [
                    'nome' => $l['nome'],
                    'categoria' => $d['categoria'],
                    'falta' => $d['proxima_faixa']['falta'],
                    'ganho' => $d['proxima_faixa']['ganho'],
                    'percentual' => $d['percentual'],
                    'percentual_proximo' => $d['proxima_faixa']['percentual'],
                ];
            }
        }
        usort($achatado, static fn ($a, $b) => $a['falta'] <=> $b['falta']);

        return array_slice($achatado, 0, $limite);
    }

    private function pessoal(int $periodoId, array $periodo): void
    {
        $funcionario = Funcionario::find(Funcionario::idPorUsuario((int) Auth::id()));
        if ($funcionario === null) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $filialPrincipal = Funcionario::filialPrincipal((int) $funcionario['id']);
        if ($filialPrincipal === null) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $linhas = ResumoCalculator::porFilial($filialPrincipal, $periodoId);
        usort($linhas, static fn ($a, $b) => $b['total'] <=> $a['total']);

        $minhaLinha = null;
        $posicao = null;
        foreach ($linhas as $i => $l) {
            if ($l['funcionario_id'] === (int) $funcionario['id']) {
                $minhaLinha = $l;
                $posicao = $i + 1;
                break;
            }
        }

        $metaIndividual = Meta::totalIndividual((int) $funcionario['id'], $periodoId);
        $realizadoIndividual = Venda::somaTotalFuncionario((int) $funcionario['id'], $periodoId);

        $metaFilial = Meta::filial($filialPrincipal, $periodoId);
        $rentabFilialRow = Indicador::rentabilidadeFilial($filialPrincipal, $periodoId);

        $filiaisRede = Filial::ativas();
        $vendaBrutaPorFilialRede = [];
        foreach ($filiaisRede as $f) {
            $m = Meta::filial((int) $f['id'], $periodoId);
            $vendaBrutaPorFilialRede[(int) $f['id']] = $m !== null ? (float) $m['venda_bruta_realizada'] : 0.0;
        }
        $minhaFilial = Filial::find($filialPrincipal);
        [$mixNomesFiliais, $mixLinhas] = self::montarMixGrade(
            $minhaFilial !== null ? [$minhaFilial] : [],
            $vendaBrutaPorFilialRede,
            Meta::totalRedeVendaBrutaRealizada($periodoId),
            $periodoId
        );

        // Simulador "se eu vender mais X": faixas (degrau) + valor já vendido por categoria.
        $simulador = [];
        foreach (Categoria::ativas() as $cat) {
            $valorAtual = 0.0;
            if ($minhaLinha !== null) {
                foreach ($minhaLinha['detalhe_categorias'] as $d) {
                    if ($d['categoria'] === $cat['nome']) {
                        $valorAtual = (float) $d['valor'];
                        break;
                    }
                }
            }
            $simulador[] = [
                'nome' => $cat['nome'],
                'valor_atual' => $valorAtual,
                'faixas' => array_map(static fn ($fx) => [
                    'ate' => $fx['limite_ate'] !== null ? (float) $fx['limite_ate'] : null,
                    'pct' => (float) $fx['percentual'],
                ], Categoria::faixas((int) $cat['id'])),
            ];
        }

        $this->render('dashboard/pessoal', [
            'periodo' => $periodo,
            'nome' => $funcionario['nome'],
            'linha' => $minhaLinha,
            'simulador' => $simulador,
            'posicao' => $posicao,
            'totalNaFilial' => count($linhas),
            'metaIndividual' => $metaIndividual,
            'realizadoIndividual' => $realizadoIndividual,
            'metaVendaFilial' => $metaFilial !== null ? (float) $metaFilial['meta_venda'] : 0.0,
            'realizadoFilial' => $metaFilial !== null ? (float) $metaFilial['venda_bruta_realizada'] : 0.0,
            'metaRentabFilial' => $metaFilial !== null ? (float) $metaFilial['meta_rentabilidade'] : 0.0,
            'rentabFilialRealizada' => $rentabFilialRow !== null ? (float) $rentabFilialRow['rentabilidade_pct'] : 0.0,
            'ritmo' => RitmoDiarioCalculator::calcular($filialPrincipal, $periodoId, $periodo),
            'checklist' => Indicador::checklist($filialPrincipal, $periodoId),
            'mixNomesFiliais' => $mixNomesFiliais,
            'mixLinhas' => $mixLinhas,
        ]);
    }

}
