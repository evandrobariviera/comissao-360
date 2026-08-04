<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Snapshot do cálculo de comissão, gravado no momento da aprovação do fechamento. */
final class Comissao
{
    public static function upsert(
        int $periodoId,
        int $funcionarioId,
        int $filialId,
        float $comissaoBase,
        float $pontuacao360,
        float $multiplicador,
        float $comissaoAjustada,
        float $premioFilial,
        float $total,
        int $aprovadoPor
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO comissao_calculada (
                periodo_id, funcionario_id, filial_id, comissao_base, pontuacao_360, multiplicador,
                comissao_ajustada, premio_filial, total, status, aprovado_por, aprovado_em
             ) VALUES (
                :periodo_id, :funcionario_id, :filial_id, :comissao_base, :pontuacao_360, :multiplicador,
                :comissao_ajustada, :premio_filial, :total, "aprovado", :aprovado_por, NOW()
             ) ON DUPLICATE KEY UPDATE
                filial_id = VALUES(filial_id), comissao_base = VALUES(comissao_base), pontuacao_360 = VALUES(pontuacao_360),
                multiplicador = VALUES(multiplicador), comissao_ajustada = VALUES(comissao_ajustada),
                premio_filial = VALUES(premio_filial), total = VALUES(total), status = "aprovado",
                aprovado_por = VALUES(aprovado_por), aprovado_em = NOW(), calculado_em = NOW()'
        );
        $stmt->execute([
            'periodo_id' => $periodoId,
            'funcionario_id' => $funcionarioId,
            'filial_id' => $filialId,
            'comissao_base' => $comissaoBase,
            'pontuacao_360' => $pontuacao360,
            'multiplicador' => $multiplicador,
            'comissao_ajustada' => $comissaoAjustada,
            'premio_filial' => $premioFilial,
            'total' => $total,
            'aprovado_por' => $aprovadoPor,
        ]);
    }
}
