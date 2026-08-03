<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class Funcionario
{
    public static function listAll(): array
    {
        return Database::pdo()->query(
            'SELECT f.id, f.nome, f.cargo, f.ativo, u.email, u.papel
             FROM funcionario f
             JOIN usuario u ON u.id = f.usuario_id
             ORDER BY f.nome'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.id, f.usuario_id, f.nome, f.cargo, f.ativo, u.email, u.papel
             FROM funcionario f
             JOIN usuario u ON u.id = f.usuario_id
             WHERE f.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $row['filiais'] = self::filiaisVinculadas($id);

        return $row;
    }

    /** @return int[] */
    public static function filiaisVinculadas(int $funcionarioId): array
    {
        $stmt = Database::pdo()->prepare('SELECT filial_id FROM funcionario_filial WHERE funcionario_id = :id');
        $stmt->execute(['id' => $funcionarioId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param int[] $filialIds */
    public static function create(
        string $nome,
        ?string $cargo,
        string $email,
        string $senha,
        string $papel,
        array $filialIds
    ): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO usuario (email, senha_hash, papel) VALUES (:email, :hash, :papel)');
            $stmt->execute([
                'email' => $email,
                'hash' => password_hash($senha, PASSWORD_BCRYPT),
                'papel' => $papel,
            ]);
            $usuarioId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO funcionario (usuario_id, nome, cargo) VALUES (:usuario_id, :nome, :cargo)');
            $stmt->execute(['usuario_id' => $usuarioId, 'nome' => $nome, 'cargo' => $cargo]);
            $funcionarioId = (int) $pdo->lastInsertId();

            self::sincronizarFiliais($pdo, $funcionarioId, $filialIds);

            $pdo->commit();

            return $funcionarioId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param int[] $filialIds */
    public static function update(
        int $funcionarioId,
        int $usuarioId,
        string $nome,
        ?string $cargo,
        string $email,
        string $papel,
        ?string $novaSenha,
        array $filialIds
    ): void {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($novaSenha !== null && $novaSenha !== '') {
                $stmt = $pdo->prepare('UPDATE usuario SET email = :email, papel = :papel, senha_hash = :hash WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'papel' => $papel,
                    'hash' => password_hash($novaSenha, PASSWORD_BCRYPT),
                    'id' => $usuarioId,
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE usuario SET email = :email, papel = :papel WHERE id = :id');
                $stmt->execute(['email' => $email, 'papel' => $papel, 'id' => $usuarioId]);
            }

            $stmt = $pdo->prepare('UPDATE funcionario SET nome = :nome, cargo = :cargo WHERE id = :id');
            $stmt->execute(['nome' => $nome, 'cargo' => $cargo, 'id' => $funcionarioId]);

            self::sincronizarFiliais($pdo, $funcionarioId, $filialIds);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function alternarStatus(int $funcionarioId, int $usuarioId): void
    {
        Database::pdo()->prepare('UPDATE usuario SET ativo = NOT ativo WHERE id = :id')->execute(['id' => $usuarioId]);
    }

    /** @param int[] $filialIds */
    private static function sincronizarFiliais(PDO $pdo, int $funcionarioId, array $filialIds): void
    {
        $pdo->prepare('DELETE FROM funcionario_filial WHERE funcionario_id = :id')->execute(['id' => $funcionarioId]);

        $stmt = $pdo->prepare(
            'INSERT INTO funcionario_filial (funcionario_id, filial_id, principal) VALUES (:funcionario_id, :filial_id, :principal)'
        );
        foreach (array_values($filialIds) as $index => $filialId) {
            $stmt->execute([
                'funcionario_id' => $funcionarioId,
                'filial_id' => $filialId,
                'principal' => $index === 0 ? 1 : 0,
            ]);
        }
    }
}
