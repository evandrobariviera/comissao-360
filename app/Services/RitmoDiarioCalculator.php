<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meta;
use DateTimeImmutable;

/**
 * Ritmo diário de venda bruta da filial: quanto falta vender por dia útil (domingo não conta)
 * pra bater a meta do mês, e a trajetória acumulada (ideal x realizado) até agora.
 */
final class RitmoDiarioCalculator
{
    /**
     * @param array{ano:int|string, mes:int|string} $periodo
     * @return array{
     *   meta_venda: float, realizado_total: float, meta_restante: float,
     *   meta_hoje: float, dias_uteis_restantes: int, dias_uteis_totais: int,
     *   pontos: array<int, array{dia:int, domingo:bool, hoje:bool, ideal_acumulado:float, realizado_acumulado: ?float}>
     * }
     */
    public static function calcular(int $filialId, int $periodoId, array $periodo): array
    {
        $meta = Meta::filial($filialId, $periodoId);
        $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
        $porDia = Meta::vendaBrutaPorDia($filialId, $periodoId);

        $ano = (int) $periodo['ano'];
        $mes = (int) $periodo['mes'];
        $diasNoMes = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->format('t');

        $hoje = new DateTimeImmutable('today');
        $ehMesCorrente = $hoje->format('Y-n') === "{$ano}-{$mes}";
        $diaHoje = $ehMesCorrente ? (int) $hoje->format('j') : $diasNoMes;

        $ehDomingo = [];
        $diasUteisTotais = 0;
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $domingo = (int) (new DateTimeImmutable(sprintf('%04d-%02d-%02d', $ano, $mes, $d)))->format('w') === 0;
            $ehDomingo[$d] = $domingo;
            if (!$domingo) {
                $diasUteisTotais++;
            }
        }

        $diasUteisRestantes = 0;
        for ($d = $diaHoje; $d <= $diasNoMes; $d++) {
            if (!$ehDomingo[$d]) {
                $diasUteisRestantes++;
            }
        }

        $realizadoTotal = array_sum($porDia);
        $metaRestante = max(0.0, $metaVenda - $realizadoTotal);
        $metaHoje = 0.0;
        if (!$ehMesCorrente) {
            $metaHoje = 0.0; // período fechado ou de outro mês — não há "hoje" pra perseguir
        } elseif (!empty($ehDomingo[$diaHoje])) {
            $metaHoje = 0.0; // hoje é domingo — sem expectativa de venda
        } elseif ($diasUteisRestantes > 0) {
            $metaHoje = $metaRestante / $diasUteisRestantes;
        } else {
            $metaHoje = $metaRestante; // último dia útil do mês — tudo cai nele
        }

        $acumuladoReal = 0.0;
        $pontos = [];
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $diasUteisAte = 0;
            for ($i = 1; $i <= $d; $i++) {
                if (!$ehDomingo[$i]) {
                    $diasUteisAte++;
                }
            }
            $idealAcumulado = $diasUteisTotais > 0 ? $metaVenda * $diasUteisAte / $diasUteisTotais : 0.0;

            $realizadoAcumulado = null;
            if (!$ehMesCorrente || $d <= $diaHoje) {
                $data = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
                $acumuladoReal += $porDia[$data] ?? 0.0;
                $realizadoAcumulado = $acumuladoReal;
            }

            $pontos[] = [
                'dia' => $d,
                'domingo' => $ehDomingo[$d],
                'hoje' => $ehMesCorrente && $d === $diaHoje,
                'ideal_acumulado' => $idealAcumulado,
                'realizado_acumulado' => $realizadoAcumulado,
            ];
        }

        return [
            'meta_venda' => $metaVenda,
            'realizado_total' => $realizadoTotal,
            'meta_restante' => $metaRestante,
            'meta_hoje' => $metaHoje,
            'dias_uteis_restantes' => $diasUteisRestantes,
            'dias_uteis_totais' => $diasUteisTotais,
            'pontos' => $pontos,
        ];
    }
}
