<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Models\Venda;

/**
 * Bloco 1 — Comissão-base por categoria (briefing §2.2).
 *
 * A alíquota da faixa em que o valor total vendido na categoria cai incide
 * sobre o valor INTEIRO (tabela de degrau, não progressivo por marginal —
 * confirmado na fórmula do simulador em Excel).
 */
final class ComissaoCalculator
{
    /**
     * @return array{comissao_base: float, detalhe: array<int, array{
     *   categoria:string, valor:float, percentual:float, comissao:float,
     *   proxima_faixa: null|array{falta:float, percentual:float, comissao_projetada:float, ganho:float}
     * }>}
     */
    public static function calcular(int $funcionarioId, int $periodoId): array
    {
        $comissaoBase = 0.0;
        $detalhe = [];

        foreach (Categoria::ativas() as $categoria) {
            $categoriaId = (int) $categoria['id'];
            $valor = self::valorParaFaixa($categoriaId, $funcionarioId, $periodoId);
            $faixas = Categoria::faixas($categoriaId);
            $percentual = self::percentualAplicavel($valor, $faixas);
            $comissaoCategoria = $valor * ($percentual / 100);
            $comissaoBase += $comissaoCategoria;

            $detalhe[] = [
                'categoria' => $categoria['nome'],
                'valor' => $valor,
                'percentual' => $percentual,
                'comissao' => $comissaoCategoria,
                'proxima_faixa' => self::proximaFaixa($valor, $comissaoCategoria, $faixas),
            ];
        }

        return ['comissao_base' => $comissaoBase, 'detalhe' => $detalhe];
    }

    /** Soma o valor vendido na própria categoria + fontes declaradas em categoria_composicao (Nível 3). */
    private static function valorParaFaixa(int $categoriaId, int $funcionarioId, int $periodoId): float
    {
        $valor = Venda::somaPorCategoria($funcionarioId, $periodoId, $categoriaId);

        foreach (Categoria::composicoes($categoriaId) as $composicao) {
            if ($composicao['fonte_tipo'] === 'categoria') {
                $valor += Venda::somaPorCategoria($funcionarioId, $periodoId, (int) $composicao['fonte_categoria_id']);
            } elseif ($composicao['fonte_tipo'] === 'flag_venda' && $composicao['fonte_flag'] === 'eh_sn') {
                $valor += Venda::somaSemNota($funcionarioId, $periodoId);
            }
        }

        return $valor;
    }

    private static function percentualAplicavel(float $valor, array $faixas): float
    {
        foreach ($faixas as $faixa) {
            if ($faixa['limite_ate'] === null || $valor <= (float) $faixa['limite_ate']) {
                return (float) $faixa['percentual'];
            }
        }

        return 0.0;
    }

    /**
     * Quanto falta vender nesta categoria para ultrapassar a faixa atual — e quanto a
     * comissão da categoria sobe com isso (o degrau incide sobre o valor inteiro, então
     * um pouco mais de venda pode valer muito mais de comissão). Null se já estiver na
     * última faixa (sem teto) ou se a categoria não tiver faixas configuradas.
     */
    private static function proximaFaixa(float $valor, float $comissaoAtual, array $faixas): ?array
    {
        foreach ($faixas as $indice => $faixa) {
            $dentroDesta = $faixa['limite_ate'] === null || $valor <= (float) $faixa['limite_ate'];
            if (!$dentroDesta) {
                continue;
            }
            if ($faixa['limite_ate'] === null) {
                return null; // já na faixa "acima de" — não tem próximo degrau
            }

            $proxima = $faixas[$indice + 1] ?? null;
            if ($proxima === null) {
                return null;
            }

            $falta = round((float) $faixa['limite_ate'] - $valor + 0.01, 2);
            $novoValor = $valor + $falta;
            $comissaoProjetada = $novoValor * ((float) $proxima['percentual'] / 100);

            return [
                'falta' => max(0.01, $falta),
                'percentual' => (float) $proxima['percentual'],
                'comissao_projetada' => $comissaoProjetada,
                'ganho' => $comissaoProjetada - $comissaoAtual,
            ];
        }

        return null;
    }
}
