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

    /** Funcionário dono deste usuário (gerente/funcionário), ou 0 se não houver vínculo. */
    public static function idPorUsuario(int $usuarioId): int
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM funcionario WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);
        $id = $stmt->fetchColumn();

        return $id === false ? 0 : (int) $id;
    }

    /** Funcionário (com dados do usuário) dono deste usuário logado, ou null se não houver vínculo. */
    public static function porUsuario(int $usuarioId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.id, f.nome, f.cargo, f.avatar_path, u.email, u.papel
             FROM funcionario f
             JOIN usuario u ON u.id = f.usuario_id
             WHERE f.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function atualizarAvatar(int $funcionarioId, ?string $avatarPath): void
    {
        Database::pdo()
            ->prepare('UPDATE funcionario SET avatar_path = :avatar_path WHERE id = :id')
            ->execute(['avatar_path' => $avatarPath, 'id' => $funcionarioId]);
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
        $row['filial_principal'] = self::filialPrincipal($id);

        return $row;
    }

    /** @return int[] */
    public static function filiaisVinculadas(int $funcionarioId): array
    {
        $stmt = Database::pdo()->prepare('SELECT filial_id FROM funcionario_filial WHERE funcionario_id = :id');
        $stmt->execute(['id' => $funcionarioId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function filialPrincipal(int $funcionarioId): ?int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT filial_id FROM funcionario_filial WHERE funcionario_id = :id AND principal = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $funcionarioId]);
        $valor = $stmt->fetchColumn();

        return $valor === false ? null : (int) $valor;
    }

    /** Filiais às quais o dono deste usuário (gerente/funcionário) está vinculado. */
    public static function filiaisDoUsuario(int $usuarioId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT fl.id, fl.nome
             FROM funcionario f
             JOIN funcionario_filial ff ON ff.funcionario_id = f.id
             JOIN filial fl ON fl.id = ff.filial_id
             WHERE f.usuario_id = :usuario_id AND fl.ativo = 1
             ORDER BY fl.nome'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Todos os funcionários ativos, com sua filial principal (fallback: a primeira vinculada,
     * caso nenhuma esteja marcada como principal). Usado no fechamento mensal (rede toda).
     */
    public static function todosComFilialPrincipal(): array
    {
        return Database::pdo()->query(
            'SELECT f.id AS funcionario_id, f.nome,
                    COALESCE(
                        (SELECT filial_id FROM funcionario_filial WHERE funcionario_id = f.id AND principal = 1 LIMIT 1),
                        (SELECT filial_id FROM funcionario_filial WHERE funcionario_id = f.id ORDER BY filial_id LIMIT 1)
                    ) AS filial_id
             FROM funcionario f
             WHERE f.ativo = 1
             ORDER BY f.nome'
        )->fetchAll();
    }

    /**
     * Funcionários ativos cuja filial PRINCIPAL é esta — usado no fechamento, onde os
     * pilares de filial e o prêmio precisam de uma única filial "dona" por pessoa
     * (evita, por exemplo, alguém vinculado a Frai 1 e Frai 2 aparecer com pontuação
     * diferente dependendo de qual das duas telas de fechamento você está olhando).
     */
    public static function porFilialPrincipal(int $filialId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.id, f.nome
             FROM funcionario f
             JOIN funcionario_filial ff ON ff.funcionario_id = f.id
             WHERE ff.filial_id = :filial_id AND ff.principal = 1 AND f.ativo = 1
             ORDER BY f.nome'
        );
        $stmt->execute(['filial_id' => $filialId]);

        return $stmt->fetchAll();
    }

    /** Funcionários ativos vinculados a uma filial (para lançamento de vendas). */
    public static function porFilial(int $filialId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.id, f.nome
             FROM funcionario f
             JOIN funcionario_filial ff ON ff.funcionario_id = f.id
             WHERE ff.filial_id = :filial_id AND f.ativo = 1
             ORDER BY f.nome'
        );
        $stmt->execute(['filial_id' => $filialId]);

        return $stmt->fetchAll();
    }

    /** @param int[] $filialIds */
    public static function create(
        string $nome,
        ?string $cargo,
        string $email,
        string $senha,
        string $papel,
        array $filialIds,
        ?int $filialPrincipal = null
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

            self::sincronizarFiliais($pdo, $funcionarioId, $filialIds, $filialPrincipal);

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
        array $filialIds,
        ?int $filialPrincipal = null
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

            self::sincronizarFiliais($pdo, $funcionarioId, $filialIds, $filialPrincipal);

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

    /**
     * @param int[] $filialIds
     * A filial principal precisa estar entre as marcadas; se não vier informada
     * (ou vier inválida), cai no comportamento antigo: a primeira da lista.
     */
    private static function sincronizarFiliais(PDO $pdo, int $funcionarioId, array $filialIds, ?int $filialPrincipal): void
    {
        $pdo->prepare('DELETE FROM funcionario_filial WHERE funcionario_id = :id')->execute(['id' => $funcionarioId]);

        $filialIds = array_values($filialIds);
        if ($filialPrincipal === null || !in_array($filialPrincipal, $filialIds, true)) {
            $filialPrincipal = $filialIds[0] ?? null;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO funcionario_filial (funcionario_id, filial_id, principal) VALUES (:funcionario_id, :filial_id, :principal)'
        );
        foreach ($filialIds as $filialId) {
            $stmt->execute([
                'funcionario_id' => $funcionarioId,
                'filial_id' => $filialId,
                'principal' => $filialId === $filialPrincipal ? 1 : 0,
            ]);
        }
    }
}
