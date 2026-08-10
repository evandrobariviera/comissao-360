<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Funcionario;
use App\Models\Meta;
use App\Models\Periodo;
use App\Models\Venda;

/**
 * Telas de autoatendimento do funcionário: consulta (somente leitura) das
 * próprias vendas e metas do período aberto. Sem lançamento — quem lança é
 * sempre o gerente/admin em /vendas e /metas.
 */
final class MinhaAreaController extends Controller
{
    public function vendas(): void
    {
        Auth::require(Auth::PAPEL_FUNCIONARIO);

        $funcionarioId = Funcionario::idPorUsuario((int) Auth::id());
        if ($funcionarioId === 0) {
            $this->render('minha_area/sem_vinculo', []);
            return;
        }

        $periodo = Periodo::atual();

        $this->render('minha_area/vendas', [
            'periodo' => $periodo,
            'vendas' => Venda::porFuncionarioPeriodo($funcionarioId, (int) $periodo['id']),
        ]);
    }

    public function metas(): void
    {
        Auth::require(Auth::PAPEL_FUNCIONARIO);

        $funcionarioId = Funcionario::idPorUsuario((int) Auth::id());
        if ($funcionarioId === 0) {
            $this->render('minha_area/sem_vinculo', []);
            return;
        }

        $periodo = Periodo::atual();
        $periodoId = (int) $periodo['id'];
        $categorias = Categoria::ativas();
        $grid = Meta::grid([$funcionarioId], array_column($categorias, 'id'), $periodoId);

        $linhas = [];
        foreach ($categorias as $categoria) {
            $categoriaId = (int) $categoria['id'];
            $linhas[] = [
                'categoria' => $categoria['nome'],
                'meta' => $grid[$funcionarioId][$categoriaId] ?? 0.0,
                'realizado' => Venda::somaPorCategoria($funcionarioId, $periodoId, $categoriaId),
            ];
        }

        $this->render('minha_area/metas', [
            'periodo' => $periodo,
            'linhas' => $linhas,
            'metaTotal' => array_sum(array_column($linhas, 'meta')),
            'realizadoTotal' => array_sum(array_column($linhas, 'realizado')),
        ]);
    }
}
