<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    private const SESSION_KEY = '_flash';

    public static function set(string $tipo, string $mensagem): void
    {
        $_SESSION[self::SESSION_KEY] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /** @return array{tipo:string, mensagem:string}|null */
    public static function pull(): ?array
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return null;
        }
        $flash = $_SESSION[self::SESSION_KEY];
        unset($_SESSION[self::SESSION_KEY]);

        return $flash;
    }
}
