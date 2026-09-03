<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Corrida dos Campeões — ranking e rateio do prêmio de cada grupo.
 *
 * Regras (v1, confirmadas com o cliente em 2026-09-02):
 *  - Competem todos os funcionários juntos, sem separar por filial.
 *  - Por grupo, ranqueia por valor vendido (só quem vendeu > 0) e premia o top 5.
 *  - Rateio do prêmio bruto do grupo: pesos lineares 5-4-3-2-1 com DENOMINADOR FIXO 15
 *    (1º = 5/15 ≈ 33,3%, 2º = 4/15, 3º = 3/15, 4º = 2/15, 5º = 1/15). Posição sem
 *    ninguém (menos de 5 venderam) simplesmente não é paga — a rede economiza a fatia.
 *  - Empate: ranking de competição (1, 2, 2, 4). Os empatados dividem em partes iguais
 *    a soma das fatias das posições que ocupam.
 *  - Ranking geral (soma de todos os grupos): só exibição, sem prêmio.
 *
 * Serviço sem estado nem acesso a banco — recebe os lançamentos já carregados.
 */
final class CorridaCalculator
{
    /** Peso de cada colocação premiada. */
    public const PESOS = [1 => 5, 2 => 4, 3 => 3, 4 => 2, 5 => 1];

    /** Denominador fixo do rateio (soma de 5+4+3+2+1). */
    public const DENOMINADOR = 15;

    /**
     * Ranking de um grupo, com a colocação e o prêmio de cada premiado.
     *
     * @param array<int, array{funcionario_id:int|string, nome?:string, filial_id?:int|string|null, valor_vendido:int|float|string}> $lancamentos
     * @return list<array{colocacao:int, empate:bool, premiado:bool, funcionario_id:int, nome:string, filial_id:?int, valor_vendido:float, premio:float}>
     */
    public static function rankingGrupo(array $lancamentos, float $premioBruto): array
    {
        $itens = [];
        foreach ($lancamentos as $l) {
            $valor = (float) $l['valor_vendido'];
            if ($valor <= 0.0) {
                continue;
            }
            $itens[] = [
                'funcionario_id' => (int) $l['funcionario_id'],
                'nome' => (string) ($l['nome'] ?? ''),
                'filial_id' => isset($l['filial_id']) && $l['filial_id'] !== null ? (int) $l['filial_id'] : null,
                'valor_vendido' => $valor,
            ];
        }

        usort($itens, static function (array $a, array $b): int {
            return $b['valor_vendido'] <=> $a['valor_vendido']
                ?: strcmp($a['nome'], $b['nome']);
        });

        $total = count($itens);
        $resultado = [];
        $i = 0;
        while ($i < $total) {
            // Bloco de empate a partir de $i (mesmo valor_vendido, com tolerância de centavo).
            $fim = $i;
            while ($fim + 1 < $total
                && abs($itens[$fim + 1]['valor_vendido'] - $itens[$i]['valor_vendido']) < 0.005) {
                $fim++;
            }

            $tamanho = $fim - $i + 1;
            $colocacao = $i + 1; // ranking de competição: a posição é o nº de quem vem antes + 1

            // Soma das fatias das posições que este bloco ocupa (colocacao .. colocacao+tamanho-1).
            $somaPesos = 0;
            for ($p = $colocacao; $p < $colocacao + $tamanho; $p++) {
                $somaPesos += self::PESOS[$p] ?? 0;
            }
            $premioCada = $somaPesos > 0
                ? round($premioBruto * ($somaPesos / $tamanho) / self::DENOMINADOR, 2)
                : 0.0;

            for ($k = $i; $k <= $fim; $k++) {
                $resultado[] = [
                    'colocacao' => $colocacao,
                    'empate' => $tamanho > 1,
                    'premiado' => $premioCada > 0.0,
                    'funcionario_id' => $itens[$k]['funcionario_id'],
                    'nome' => $itens[$k]['nome'],
                    'filial_id' => $itens[$k]['filial_id'],
                    'valor_vendido' => $itens[$k]['valor_vendido'],
                    'premio' => $premioCada,
                ];
            }

            $i = $fim + 1;
        }

        return $resultado;
    }

    /**
     * Ranking geral acumulado: soma do valor vendido em todos os grupos, por funcionário.
     * Só exibição — não distribui prêmio.
     *
     * @param array<int, array{funcionario_id:int|string, nome?:string, filial_id?:int|string|null, valor_vendido:int|float|string}> $lancamentos
     * @return list<array{colocacao:int, funcionario_id:int, nome:string, filial_id:?int, total:float}>
     */
    public static function rankingGeral(array $lancamentos): array
    {
        $porFuncionario = [];
        foreach ($lancamentos as $l) {
            $id = (int) $l['funcionario_id'];
            if (!isset($porFuncionario[$id])) {
                $porFuncionario[$id] = [
                    'funcionario_id' => $id,
                    'nome' => (string) ($l['nome'] ?? ''),
                    'filial_id' => isset($l['filial_id']) && $l['filial_id'] !== null ? (int) $l['filial_id'] : null,
                    'total' => 0.0,
                ];
            }
            $porFuncionario[$id]['total'] += (float) $l['valor_vendido'];
        }

        $linhas = array_values(array_filter($porFuncionario, static fn (array $r): bool => $r['total'] > 0.0));
        usort($linhas, static function (array $a, array $b): int {
            return $b['total'] <=> $a['total'] ?: strcmp($a['nome'], $b['nome']);
        });

        $resultado = [];
        $total = count($linhas);
        $i = 0;
        while ($i < $total) {
            $fim = $i;
            while ($fim + 1 < $total && abs($linhas[$fim + 1]['total'] - $linhas[$i]['total']) < 0.005) {
                $fim++;
            }
            for ($k = $i; $k <= $fim; $k++) {
                $resultado[] = ['colocacao' => $i + 1] + $linhas[$k];
            }
            $i = $fim + 1;
        }

        return $resultado;
    }

    /**
     * Prévia do rateio de um prêmio bruto pelas 5 posições (para telas de configuração/ajuda).
     * @return array<int, float> colocacao (1..5) => valor
     */
    public static function previaRateio(float $premioBruto): array
    {
        $previa = [];
        foreach (self::PESOS as $colocacao => $peso) {
            $previa[$colocacao] = round($premioBruto * $peso / self::DENOMINADOR, 2);
        }

        return $previa;
    }
}
