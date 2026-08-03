<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', ['erro' => null], null);
    }

    public function login(): void
    {
        $token = $this->input('_csrf');
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            $this->render('auth/login', ['erro' => 'Sessão expirada, tente novamente.'], null);
            return;
        }

        $email = trim((string) $this->input('email', ''));
        $senha = (string) $this->input('senha', '');

        if ($email === '' || $senha === '' || !Auth::attempt($email, $senha)) {
            $this->render('auth/login', ['erro' => 'E-mail ou senha inválidos.'], null);
            return;
        }

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
