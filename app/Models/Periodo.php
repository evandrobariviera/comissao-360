<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Periodo
{
    /** Retorna o período do mês corrente, criando-o (status 'aberto') se ainda não existir. */
    public static function atual(): array
    {
        $ano = (int) date('Y');
        $mes = (int) date('n');

        $stmt = Database::pdo()->prepare('SELECT * FROM periodo WHERE ano = :ano AND mes = :mes');
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        $row = $stmt->fetch();
        if ($row !== false) {
            return $row;
        }

        $stmt = Database::pdo()->prepare('INSERT INTO periodo (ano, mes, status) VALUES (:ano, :mes, :status)');
        $stmt->execute(['ano' => $ano, 'mes' => $mes, 'status' => 'aberto']);
        $id = (int) Database::pdo()->lastInsertId();

        return ['id' => $id, 'ano' => $ano, 'mes' => $mes, 'status' => 'aberto'];
    }

    public static function aprovar(int $id, int $aprovadoPor): void
    {
        Database::pdo()
            ->prepare('UPDATE periodo SET status = "aprovado", aprovado_por = :por, aprovado_em = NOW() WHERE id = :id')
            ->execute(['por' => $aprovadoPor, 'id' => $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM periodo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
