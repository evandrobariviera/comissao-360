<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Usuario
{
    public static function buscarPorEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function registrarTentativaFalha(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE usuario SET
                tentativas_login = tentativas_login + 1,
                bloqueado_ate = IF(tentativas_login + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), bloqueado_ate)
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function registrarLoginSucesso(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE usuario SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_login = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
