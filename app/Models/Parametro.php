<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Parametro
{
    /** @return array<string, string> */
    public static function todos(): array
    {
        $stmt = Database::pdo()->query('SELECT chave, valor FROM parametro');

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public static function float(string $chave, float $default = 0.0): float
    {
        $stmt = Database::pdo()->prepare('SELECT valor FROM parametro WHERE chave = :chave');
        $stmt->execute(['chave' => $chave]);
        $valor = $stmt->fetchColumn();

        return $valor === false ? $default : (float) $valor;
    }
}
