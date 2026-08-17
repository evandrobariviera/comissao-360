<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Indicador;
use App\Models\Meta;
use App\Models\Venda;

/** Bloco 2 — Meta 360º: 4 pilares (0–100 pts) → tabela de multiplicador (briefing §2.3). */
final class Pontuacao360Calculator
{
    /**
     * @param array<string,string> $parametros Parametro::todos()
     * @return array{
     *   pontos_individual: float, pontos_filial: float, pontos_qualidade: float, pontos_equipe: float,
     *   pontuacao_total: float, nivel: string, multiplicador: float, multiplicador_protegido: float
     * }
     */
    public static function calcular(int $funcionarioId, int $filialId, int $periodoId, array $parametros): array
    {
        $pontosIndividual = self::pontosIndividual($funcionarioId, $periodoId);
        $pontosFilial = self::pontosFilial($filialId, $periodoId);
        $pontosQualidade = self::pontosQualidade($funcionarioId, $filialId, $periodoId, $parametros);
        $pontosEquipe = self::pontosEquipe($filialId, $periodoId, $parametros);

        $total = $pontosIndividual + $pontosFilial + $pontosQualidade + $pontosEquipe;
        [$multiplicador, $nivel] = self::multiplicador($total);

        return [
            'pontos_individual' => $pontosIndividual,
            'pontos_filial' => $pontosFilial,
            'pontos_qualidade' => $pontosQualidade,
            'pontos_equipe' => $pontosEquipe,
            'pontuacao_total' => $total,
            'nivel' => $nivel,
            'multiplicador' => $multiplicador,
            'multiplicador_protegido' => max(1.0, $multiplicador),
        ];
    }

    private static function pontosIndividual(int $funcionarioId, int $periodoId): float
    {
        $meta = Meta::totalIndividual($funcionarioId, $periodoId);
        $realizado = Venda::somaTotalFuncionario($funcionarioId, $periodoId);
        $atingimento = $meta > 0 ? ($realizado / $meta) * 100 : 0.0;

        return self::escala($atingimento, [110 => 40, 100 => 38, 90 => 34, 80 => 28, 70 => 20]);
    }

    private static function pontosFilial(int $filialId, int $periodoId): float
    {
        $meta = Meta::filial($filialId, $periodoId);
        $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
        $realizado = $meta !== null ? (float) $meta['venda_bruta_realizada'] : 0.0;
        $atingimento = $metaVenda > 0 ? ($realizado / $metaVenda) * 100 : 0.0;

        return self::escala($atingimento, [100 => 30, 90 => 22, 80 => 15]);
    }

    private static function pontosQualidade(int $funcionarioId, int $filialId, int $periodoId, array $parametros): float
    {
        $desconto = null;
        $rentabFunc = null;
        $ticketMedio = null;
        foreach (Indicador::funcionariosComIndicador($filialId, $periodoId) as $f) {
            if ((int) $f['id'] === $funcionarioId) {
                $desconto = $f['desconto_medio'];
                $rentabFunc = $f['rentabilidade_pct'];
                $ticketMedio = $f['ticket_medio'];
                break;
            }
        }

        $metaFilial = Meta::filial($filialId, $periodoId);

        // Desconto médio: faixa linear invertida (desconto BAIXO é bom) — piso = pontuação
        // cheia, teto = zero. Piso/teto são "parâmetro mãe" global, com override opcional por filial.
        $descontoPiso = self::resolvido($metaFilial, 'desconto_piso_pct', $parametros, 10.0);
        $descontoTeto = self::resolvido($metaFilial, 'desconto_teto_pct', $parametros, 15.0);
        $ptsMaxDesconto = (float) ($parametros['desconto_pts_max'] ?? 5);
        $pontosDesconto = 0.0;
        if ($desconto !== null && $descontoTeto > $descontoPiso) {
            $pontosDesconto = max(0.0, min($ptsMaxDesconto, $ptsMaxDesconto * ($descontoTeto - (float) $desconto) / ($descontoTeto - $descontoPiso)));
        }

        // Rentabilidade: mesma régua linear pra filial e funcionário — piso começa a pontuar,
        // teto dá a pontuação cheia. Piso/teto também são globais com override opcional por filial.
        $rentabPiso = self::resolvido($metaFilial, 'rentab_piso_pct', $parametros, 35.0);
        $rentabTeto = self::resolvido($metaFilial, 'rentab_teto_pct', $parametros, 40.0);

        $rentabFilial = Indicador::rentabilidadeFilial($filialId, $periodoId);
        $ptsMaxRentabFilial = (float) ($parametros['rentab_filial_pts'] ?? 5);
        $pontosRentabFilial = 0.0;
        if ($rentabFilial !== null && $rentabTeto > $rentabPiso) {
            $pontosRentabFilial = max(0.0, min($ptsMaxRentabFilial, $ptsMaxRentabFilial * ((float) $rentabFilial['rentabilidade_pct'] - $rentabPiso) / ($rentabTeto - $rentabPiso)));
        }

        $ptsMaxRentabFunc = (float) ($parametros['rentab_funcionario_pts'] ?? 5);
        $pontosRentabFunc = 0.0;
        if ($rentabFunc !== null && $rentabTeto > $rentabPiso) {
            $pontosRentabFunc = max(0.0, min($ptsMaxRentabFunc, $ptsMaxRentabFunc * ((float) $rentabFunc - $rentabPiso) / ($rentabTeto - $rentabPiso)));
        }

        // Ticket médio: faixa linear igual desconto médio, mas invertida (ticket ALTO é bom).
        // Mesmo mecanismo global + override por filial dos demais sub-pilares.
        $ptsMaxTicket = (float) ($parametros['ticket_medio_pts_max'] ?? 5);
        $ticketPiso = self::resolvido($metaFilial, 'ticket_medio_piso', $parametros, 70.0);
        $ticketTeto = self::resolvido($metaFilial, 'ticket_medio_teto', $parametros, 100.0);
        $pontosTicket = 0.0;
        if ($ticketMedio !== null && $ticketTeto > $ticketPiso) {
            $pontosTicket = max(0.0, min($ptsMaxTicket, $ptsMaxTicket * ((float) $ticketMedio - $ticketPiso) / ($ticketTeto - $ticketPiso)));
        }

        $ptsMaxQualidade = (float) ($parametros['peso_qualidade_max'] ?? 20);

        return min($ptsMaxQualidade, $pontosDesconto + $pontosRentabFilial + $pontosRentabFunc + $pontosTicket);
    }

    /**
     * Resolve um parâmetro do sub-pilar Qualidade: override da filial (meta_filial.$chave, se não
     * for NULL) sobrescreve o padrão global (parametro.$chave); na ausência dos dois, usa $default.
     */
    private static function resolvido(?array $metaFilial, string $chave, array $parametros, float $default): float
    {
        if ($metaFilial !== null && $metaFilial[$chave] !== null) {
            return (float) $metaFilial[$chave];
        }

        return (float) ($parametros[$chave] ?? $default);
    }

    private static function pontosEquipe(int $filialId, int $periodoId, array $parametros): float
    {
        $checklist = Indicador::checklist($filialId, $periodoId);
        if ($checklist === null) {
            return 0.0;
        }

        $criterios = [
            'c1_sem_falta_injustificada', 'c2_cumpriu_escala', 'c3_setor_organizado',
            'c4_ajudou_treinou_colega', 'c5_loja_bateu_meta_coletiva',
            'c6_venda_5_catalogos', 'c7_venda_30_a_vencer', 'c8_venda_30_linha_propria',
        ];
        $ptsMaxEquipe = (float) ($parametros['peso_equipe_max'] ?? 10);
        $pontosPorItem = $ptsMaxEquipe / count($criterios);

        $pontos = 0.0;
        foreach ($criterios as $c) {
            if (!empty($checklist[$c])) {
                $pontos += $pontosPorItem;
            }
        }

        return $pontos;
    }

    /** @param array<int,int> $faixas pares [limiar_mínimo => pontos], em ordem decrescente de limiar */
    private static function escala(float $atingimentoPct, array $faixas): float
    {
        foreach ($faixas as $limiar => $pontos) {
            if ($atingimentoPct >= $limiar) {
                return (float) $pontos;
            }
        }

        return 0.0;
    }

    /**
     * A tabela de multiplicador é uma faixa fechada (ex.: 70–79) — como os pilares acima podem gerar
     * pontuação com casas decimais, arredondamos antes de buscar a faixa (evita "buraco" entre faixas).
     *
     * @return array{0: float, 1: string}
     */
    private static function multiplicador(float $pontuacaoTotal): array
    {
        $pontos = (int) round(max(0.0, min(100.0, $pontuacaoTotal)));

        $stmt = Database::pdo()->prepare(
            'SELECT multiplicador, nivel FROM multiplicador_faixa WHERE :pontos BETWEEN pontos_de AND pontos_ate LIMIT 1'
        );
        $stmt->execute(['pontos' => $pontos]);
        $row = $stmt->fetch();

        return $row !== false ? [(float) $row['multiplicador'], (string) $row['nivel']] : [1.0, 'Em desenvolvimento'];
    }
}
