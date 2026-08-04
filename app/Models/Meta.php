<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Meta
{
    /** Meta Individual Total = soma das metas por categoria do funcionário no período. */
    public static function totalIndividual(int $funcionarioId, int $periodoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(meta_venda), 0) FROM meta_funcionario WHERE funcionario_id = :funcionario_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function filial(int $filialId, int $periodoId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM meta_filial WHERE filial_id = :filial_id AND periodo_id = :periodo_id');
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
