<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meta;

/**
 * Bloco 3 — Prêmio de filial: valor fixo pago a todos se a filial bateu a meta do mês
 * (briefing §2.4). O "realizado" é a venda bruta que o gerente alimenta manualmente
 * (não a soma da grade de funcionários), porque nem toda venda da loja passa por lá.
 */
final class PremioFilialService
{
    public static function calcular(int $filialId, int $periodoId): float
    {
        $meta = Meta::filial($filialId, $periodoId);
        if ($meta === null || (float) $meta['meta_venda'] <= 0) {
            return 0.0;
        }

        $realizado = (float) $meta['venda_bruta_realizada'];

        return $realizado >= (float) $meta['meta_venda'] ? (float) $meta['valor_premio'] : 0.0;
    }
}
