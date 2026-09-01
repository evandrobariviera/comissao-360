<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Categoria;
use App\Models\FechamentoFilial;
use App\Models\Funcionario;
use App\Models\Meta;
use App\Models\Periodo;
use App\Models\Venda;
use DateTime;

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
        $periodo = Periodo::ativo();
        $periodoId = (int) $periodo['id'];
        $funcionarios = Funcionario::porFilial($filialId);
        $categorias = Categoria::ativas();
        $funcionarioIds = array_column($funcionarios, 'id');

        $this->render('vendas/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'fechamento' => FechamentoFilial::status($periodoId, $filialId),
            'funcionarios' => $funcionarios,
            'categorias' => $categorias,
            'metaFilial' => Meta::filial($filialId, $periodoId),
            'lancamentosBruta' => Meta::lancamentosVendaBruta($filialId, $periodoId),
            'categoriasMix' => Categoria::comMetaPercentual(),
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
        $periodo = Periodo::ativo();

        if (!FechamentoFilial::estaAberto((int) $periodo['id'], $filialId)) {
            Flash::set('erro', 'Esta filial já está fechada neste período — não é mais possível lançar venda bruta.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $dataRaw = trim((string) $this->input('data_bruta', ''));
        if ($dataRaw === '') {
            $dataRaw = date('Y-m-d');
        }
        $data = DateTime::createFromFormat('Y-m-d', $dataRaw);
        if ($data === false || $data->format('Y-m-d') !== $dataRaw
            || (int) $data->format('Y') !== (int) $periodo['ano'] || (int) $data->format('n') !== (int) $periodo['mes']) {
            Flash::set('erro', 'Data inválida — precisa ser um dia dentro do período aberto.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $valorRaw = trim(str_replace(',', '.', (string) $this->input('venda_bruta', '')));
        if (!is_numeric($valorRaw) || (float) $valorRaw <= 0) {
            Flash::set('erro', 'Informe um valor de venda bruta válido (maior que zero).');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $categoriaPost = $this->input('categoria_valor', []);
        $categoriaPost = is_array($categoriaPost) ? $categoriaPost : [];
        $porCategoria = [];
        foreach (Categoria::comMetaPercentual() as $c) {
            $categoriaId = (int) $c['id'];
            $bruto = trim(str_replace(',', '.', (string) ($categoriaPost[$categoriaId] ?? '')));
            if ($bruto === '') {
                continue;
            }
            if (!is_numeric($bruto) || (float) $bruto < 0) {
                Flash::set('erro', "Valor inválido pra categoria \"{$c['nome']}\".");
                $this->redirect("/vendas?filial_id={$filialId}");
            }
            $porCategoria[$categoriaId] = (float) $bruto;
        }

        Meta::adicionarLancamentoVendaBruta((int) $periodo['id'], $filialId, $dataRaw, (float) $valorRaw, (int) Auth::id(), $porCategoria);

        Audit::log('lancar_venda_bruta', 'venda_bruta_lancamento', $filialId, "periodo={$periodo['id']}, data={$dataRaw}, valor={$valorRaw}");
        Flash::set('sucesso', 'Lançamento de venda bruta adicionado.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }

    public function excluirBruta(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $lancamento = Meta::lancamentoVendaBruta((int) $id);
        if ($lancamento === null) {
            Flash::set('erro', 'Lançamento não encontrado.');
            $this->redirect('/vendas');
        }

        $filialId = (int) $lancamento['filial_id'];
        $filiaisPermitidas = $this->filiaisPermitidas();
        $idsPermitidos = array_column($filiaisPermitidas, 'id');
        if (!in_array($filialId, $idsPermitidos, true)) {
            Flash::set('erro', 'Você não tem acesso a essa filial.');
            $this->redirect('/vendas');
        }

        if (!FechamentoFilial::estaAberto((int) $lancamento['periodo_id'], $filialId)) {
            Flash::set('erro', 'Esta filial já está fechada neste período — não é mais possível excluir lançamentos.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        Meta::excluirLancamentoVendaBruta((int) $id, (int) Auth::id());
        Audit::log('excluir', 'venda_bruta_lancamento', (int) $id);
        Flash::set('sucesso', 'Lançamento excluído.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }

    public function salvarGrade(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));
        $periodo = Periodo::ativo();

        if (!FechamentoFilial::estaAberto((int) $periodo['id'], $filialId)) {
            Flash::set('erro', 'Esta filial já está fechada neste período — não é mais possível alterar vendas.');
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

        if (!FechamentoFilial::estaAberto((int) $venda['periodo_id'], $filialId)) {
            Flash::set('erro', 'Esta filial já está fechada neste período — não é mais possível excluir lançamentos.');
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        Venda::delete((int) $id);
        Audit::log('excluir', 'venda_lancamento', (int) $id);
        Flash::set('sucesso', 'Lançamento excluído.');
        $this->redirect("/vendas?filial_id={$filialId}");
    }
}
