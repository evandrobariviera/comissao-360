<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meta;
use App\Models\Venda;

/** Bloco 3 — Prêmio de filial: valor fixo pago a todos se a filial bateu a meta do mês (briefing §2.4). */
final class PremioFilialService
{
    public static function calcular(int $filialId, int $periodoId): float
    {
        $meta = Meta::filial($filialId, $periodoId);
        if ($meta === null || (float) $meta['meta_venda'] <= 0) {
            return 0.0;
        }

        $realizado = Venda::somaTotalFilial($filialId, $periodoId);

        return $realizado >= (float) $meta['meta_venda'] ? (float) $meta['valor_premio'] : 0.0;
    }
}
