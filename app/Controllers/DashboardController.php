<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $papel = Auth::papel();
        $view = match ($papel) {
            Auth::PAPEL_ADMIN => 'admin/dashboard',
            Auth::PAPEL_GERENTE => 'gerente/dashboard',
            default => 'funcionario/dashboard',
        };

        $this->render($view, [
            'email' => $_SESSION['email'] ?? '',
            'papel' => $papel,
        ]);
    }
}
