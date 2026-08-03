<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Filial
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM filial ORDER BY nome')->fetchAll();
    }

    public static function ativas(): array
    {
        return Database::pdo()->query('SELECT * FROM filial WHERE ativo = 1 ORDER BY nome')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM filial WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function create(string $nome, string $codigo): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO filial (nome, codigo) VALUES (:nome, :codigo)');
        $stmt->execute(['nome' => $nome, 'codigo' => $codigo]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $nome, string $codigo): void
    {
        $stmt = Database::pdo()->prepare('UPDATE filial SET nome = :nome, codigo = :codigo WHERE id = :id');
        $stmt->execute(['nome' => $nome, 'codigo' => $codigo, 'id' => $id]);
    }

    public static function alternarStatus(int $id): void
    {
        Database::pdo()->prepare('UPDATE filial SET ativo = NOT ativo WHERE id = :id')->execute(['id' => $id]);
    }
}
