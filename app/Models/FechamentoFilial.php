<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Status de fechamento de UMA filial num período — independente das outras filiais do mesmo mês.
 * Ausência de linha em `fechamento_filial` significa "aberto" (nunca precisa popular vazio).
 */
final class FechamentoFilial
{
    /** @return array{id: ?int, periodo_id:int, filial_id:int, status:string, aprovado_por: ?int, aprovado_em: ?string} */
    public static function status(int $periodoId, int $filialId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM fechamento_filial WHERE periodo_id = :periodo_id AND filial_id = :filial_id');
        $stmt->execute(['periodo_id' => $periodoId, 'filial_id' => $filialId]);
        $row = $stmt->fetch();
        if ($row !== false) {
            return $row;
        }

        return ['id' => null, 'periodo_id' => $periodoId, 'filial_id' => $filialId, 'status' => 'aberto', 'aprovado_por' => null, 'aprovado_em' => null];
    }

    public static function estaAberto(int $periodoId, int $filialId): bool
    {
        return self::status($periodoId, $filialId)['status'] === 'aberto';
    }

    /** @return array<int, array{id: ?int, periodo_id:int, filial_id:int, status:string, aprovado_por: ?int, aprovado_em: ?string}> [filial_id => status] */
    public static function statusPorFilial(int $periodoId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM fechamento_filial WHERE periodo_id = :periodo_id');
        $stmt->execute(['periodo_id' => $periodoId]);

        $porFilial = [];
        foreach ($stmt->fetchAll() as $row) {
            $porFilial[(int) $row['filial_id']] = $row;
        }

        return $porFilial;
    }

    public static function aprovar(int $periodoId, int $filialId, int $aprovadoPor): void
    {
        Database::pdo()->prepare(
            'INSERT INTO fechamento_filial (periodo_id, filial_id, status, aprovado_por, aprovado_em)
             VALUES (:periodo_id, :filial_id, "aprovado", :aprovado_por, NOW())
             ON DUPLICATE KEY UPDATE status = "aprovado", aprovado_por = VALUES(aprovado_por), aprovado_em = VALUES(aprovado_em)'
        )->execute(['periodo_id' => $periodoId, 'filial_id' => $filialId, 'aprovado_por' => $aprovadoPor]);
    }

    public static function reabrir(int $periodoId, int $filialId): void
    {
        Database::pdo()->prepare(
            'INSERT INTO fechamento_filial (periodo_id, filial_id, status, aprovado_por, aprovado_em)
             VALUES (:periodo_id, :filial_id, "aberto", NULL, NULL)
             ON DUPLICATE KEY UPDATE status = "aberto", aprovado_por = NULL, aprovado_em = NULL'
        )->execute(['periodo_id' => $periodoId, 'filial_id' => $filialId]);
    }
}
