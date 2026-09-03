<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/**
 * Landing de "Configuração" — reúne os cadastros e regras que mudam pouco ao longo do ano
 * (Filiais, Usuários, Regras de comissão). Fica fora da barra principal, acessível pelo
 * link "Configuração" no canto superior.
 */
final class ConfigController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('config/index');
    }
}
