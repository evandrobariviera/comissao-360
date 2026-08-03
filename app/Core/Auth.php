<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

final class Auth
{
    public const PAPEL_ADMIN = 'admin';
    public const PAPEL_GERENTE = 'gerente';
    public const PAPEL_FUNCIONARIO = 'funcionario';

    public static function attempt(string $email, string $senha): bool
    {
        $usuario = Usuario::buscarPorEmail($email);
        if ($usuario === null || !(int) $usuario['ativo']) {
            return false;
        }
        if (!empty($usuario['bloqueado_ate']) && strtotime((string) $usuario['bloqueado_ate']) > time()) {
            return false;
        }
        if (!password_verify($senha, $usuario['senha_hash'])) {
            Usuario::registrarTentativaFalha((int) $usuario['id']);
            return false;
        }

        Usuario::registrarLoginSucesso((int) $usuario['id']);
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['papel'] = $usuario['papel'];
        $_SESSION['email'] = $usuario['email'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function papel(): ?string
    {
        return $_SESSION['papel'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    /**
     * Trava a rota atual. Sem argumentos, só exige estar autenticado.
     * Com argumentos, exige que o papel do usuário esteja entre os informados.
     */
    public static function require(string ...$papeis): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
        if (!empty($papeis) && !in_array(self::papel(), $papeis, true)) {
            http_response_code(403);
            require APP_DIR . '/Views/errors/403.php';
            exit;
        }
    }
}
