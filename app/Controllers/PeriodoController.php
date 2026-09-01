<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Periodo;

/** Seletor global de período (cabeçalho): troca o mês/ano que todas as telas usam nesta sessão. */
final class PeriodoController extends Controller
{
    public function selecionar(): void
    {
        Auth::require();
        $this->requireCsrf();

        $ano = (int) $this->input('ano', 0);
        $mes = (int) $this->input('mes', 0);
        $destino = $this->destinoSeguro((string) $this->input('redirect', '/dashboard'));

        if (!Periodo::selecionar($ano, $mes)) {
            Flash::set('erro', 'Período inválido.');
        }

        $this->redirect($destino);
    }

    /** Só aceita caminhos internos (evita open redirect via o campo "redirect"). */
    private function destinoSeguro(string $destino): string
    {
        if ($destino === '' || $destino[0] !== '/' || str_starts_with($destino, '//')) {
            return '/dashboard';
        }

        return $destino;
    }
}
