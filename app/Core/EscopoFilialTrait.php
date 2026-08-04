<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Filial;
use App\Models\Funcionario;

/**
 * Resolve a quais filiais o usuário logado tem acesso: todas (admin) ou só
 * as vinculadas ao seu cadastro de funcionário (gerente). Usado por
 * qualquer tela de lançamento operacional (vendas, indicadores, etc.).
 */
trait EscopoFilialTrait
{
    protected function filiaisPermitidas(): array
    {
        return Auth::papel() === Auth::PAPEL_ADMIN
            ? Filial::ativas()
            : Funcionario::filiaisDoUsuario((int) Auth::id());
    }

    protected function resolverFilialId(array $filiaisPermitidas, int $solicitada = 0): int
    {
        $ids = array_column($filiaisPermitidas, 'id');
        if ($solicitada === 0) {
            $solicitada = (int) ($this->input('filial_id', $ids[0] ?? 0));
        }

        return in_array($solicitada, $ids, true) ? $solicitada : (int) ($ids[0] ?? 0);
    }
}
