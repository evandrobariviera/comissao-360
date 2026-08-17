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

    /**
     * @return array<int, array{chave:string, valor:string, descricao:?string}>
     */
    public static function todosDetalhados(): array
    {
        return Database::pdo()->query('SELECT chave, valor, descricao FROM parametro ORDER BY chave')->fetchAll();
    }

    /** Atualiza só os parâmetros já existentes (chave => novo valor); nunca cria chave nova. */
    public static function atualizar(array $pares): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE parametro SET valor = :valor WHERE chave = :chave');
        $pdo->beginTransaction();
        try {
            foreach ($pares as $chave => $valor) {
                $stmt->execute(['chave' => $chave, 'valor' => $valor]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
