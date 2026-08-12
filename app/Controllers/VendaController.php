<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Categoria;
use App\Models\Funcionario;
use App\Models\Meta;
use App\Models\Periodo;
use App\Models\Venda;

final class VendaController extends Controller
{
    use EscopoFilialTrait;

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);

        $filiaisPermitidas = $this->filiaisPermitidas();
        if (empty($filiaisPermitidas)) {
            $this->render('vendas/sem_filial', []);
            return;
        }

        $filialId = $this->resolverFilialId($filiaisPermitidas);
        $periodo = Periodo::atual();
        $periodoId = (int) $periodo['id'];
        $funcionarios = Funcionario::porFilial($filialId);
        $categorias = Categoria::ativas();
        $funcionarioIds = array_column($funcionarios, 'id');

        $this->render('vendas/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'funcionarios' => $funcionarios,
            'categorias' => $categorias,
            'metaFilial' => Meta::filial($filialId, $periodoId),
            'grid' => Venda::gridTotais($funcionarioIds, array_column($categorias, 'id'), $periodoId),
            'gridSn' => Venda::mapaSemNota($funcionarioIds, $periodoId),
            'ajustes' => Venda::porFilialPeriodo($filialId, $periodoId),
        ]);
    }

    public function atualizarBruta(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));
        $periodo = Periodo::atual();

        if ($periodo['status'] !== 'aberto') {
            Flash::set('erro', 'Este período já foi fechado — não é mais possível alterar a venda bruta.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $valorRaw = trim(str_replace(',', '.', (string) $this->input('venda_bruta', '')));
        if (!is_numeric($valorRaw) || (float) $valorRaw < 0) {
            Flash::set('erro', 'Informe um valor de venda bruta válido.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        Meta::atualizarVendaBruta((int) $periodo['id'], $filialId, (float) $valorRaw, (int) Auth::id());

        Audit::log('atualizar_venda_bruta', 'meta_filial', $filialId, "periodo={$periodo['id']}, valor={$valorRaw}");
        Flash::set('sucesso', 'Venda bruta da filial atualizada.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }

    public function salvarGrade(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));
        $periodo = Periodo::atual();

        if ($periodo['status'] !== 'aberto') {
            Flash::set('erro', 'Este período já foi fechado — não é mais possível alterar vendas.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $funcionarioIdsValidos = array_column(Funcionario::porFilial($filialId), 'id');
        $categorias = Categoria::ativas();
        $categoriaIdsValidos = array_column($categorias, 'id');
        $categoriaManipulacaoId = 0;
        foreach ($categorias as $c) {
            if ($c['nome'] === 'Manipulação') {
                $categoriaManipulacaoId = (int) $c['id'];
            }
        }

        $totaisPost = $this->input('total', []);
        $snPost = $this->input('sn', []);

        $totais = [];
        $totaisSn = [];
        foreach ($funcionarioIdsValidos as $funcionarioId) {
            foreach ($categoriaIdsValidos as $categoriaId) {
                $bruto = is_array($totaisPost) ? ($totaisPost[$funcionarioId][$categoriaId] ?? '0') : '0';
                $valorRaw = trim(str_replace(',', '.', (string) $bruto));
                if ($valorRaw === '') {
                    $valorRaw = '0';
                }
                if (!is_numeric($valorRaw) || (float) $valorRaw < 0) {
                    Flash::set('erro', 'Um dos totais por funcionário/categoria está inválido.');
                    $this->redirect("/vendas?filial_id={$filialId}");
                }
                $totais[$funcionarioId][$categoriaId] = (float) $valorRaw;
            }

            $snBruto = is_array($snPost) ? ($snPost[$funcionarioId] ?? '0') : '0';
            $snRaw = trim(str_replace(',', '.', (string) $snBruto));
            if ($snRaw === '') {
                $snRaw = '0';
            }
            if (!is_numeric($snRaw) || (float) $snRaw < 0) {
                Flash::set('erro', 'Um dos totais de Manipulação S/N está inválido.');
                $this->redirect("/vendas?filial_id={$filialId}");
            }
            if ($categoriaManipulacaoId > 0 && (float) $snRaw > $totais[$funcionarioId][$categoriaManipulacaoId]) {
                Flash::set('erro', 'O total de Manipulação S/N não pode ser maior que o total de Manipulação.');
                $this->redirect("/vendas?filial_id={$filialId}");
            }
            $totaisSn[$funcionarioId] = (float) $snRaw;
        }

        Venda::salvarGrade((int) $periodo['id'], $filialId, $categoriaManipulacaoId, $totais, $totaisSn, (int) Auth::id());

        Audit::log('salvar_grade', 'venda_lancamento', $filialId, "periodo={$periodo['id']}");
        Flash::set('sucesso', 'Vendas atualizadas.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }

    public function excluir(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $venda = Venda::find((int) $id);
        if ($venda === null) {
            Flash::set('erro', 'Lançamento não encontrado.');
            $this->redirect('/vendas');
        }

        $filialId = (int) $venda['filial_id'];
        $filiaisPermitidas = $this->filiaisPermitidas();
        $idsPermitidos = array_column($filiaisPermitidas, 'id');
        if (!in_array($filialId, $idsPermitidos, true)) {
            Flash::set('erro', 'Você não tem acesso a essa filial.');
            $this->redirect('/vendas');
        }

        $periodo = Periodo::find((int) $venda['periodo_id']);
        if ($periodo === null || $periodo['status'] !== 'aberto') {
            Flash::set('erro', 'Este período já foi fechado — não é mais possível excluir lançamentos.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        Venda::delete((int) $id);
        Audit::log('excluir', 'venda_lancamento', (int) $id);
        Flash::set('sucesso', 'Lançamento excluído.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }
}
