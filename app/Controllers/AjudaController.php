<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class AjudaController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $this->render('ajuda/index', []);
    }
}
