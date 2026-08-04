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

        $this->render('vendas/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'funcionarios' => Funcionario::porFilial($filialId),
            'categorias' => Categoria::ativas(),
            'vendas' => Venda::porFilialPeriodo($filialId, (int) $periodo['id']),
        ]);
    }

    public function criar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));

        $funcionarioId = (int) $this->input('funcionario_id', 0);
        $categoriaId = (int) $this->input('categoria_id', 0);
        $data = trim((string) $this->input('data', ''));
        $valorRaw = trim(str_replace(',', '.', (string) $this->input('valor', '')));
        $ehSn = $this->input('eh_sn') !== null;

        $periodo = Periodo::atual();

        $erro = $this->validarLancamento($filialId, $funcionarioId, $categoriaId, $data, $valorRaw, $periodo);
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect("/vendas?filial_id={$filialId}");
        }

        $id = Venda::create(
            (int) $periodo['id'],
            $funcionarioId,
            $filialId,
            $categoriaId,
            $data,
            (float) $valorRaw,
            $ehSn,
            (int) Auth::id()
        );

        Audit::log('criar', 'venda_lancamento', $id);
        Flash::set('sucesso', 'Venda lançada.');
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

    private function validarLancamento(int $filialId, int $funcionarioId, int $categoriaId, string $data, string $valorRaw, array $periodo): ?string
    {
        if ($periodo['status'] !== 'aberto') {
            return 'Este período já foi fechado — não é mais possível lançar vendas.';
        }
        if ($funcionarioId <= 0 || !Venda::funcionarioPertenceFilial($funcionarioId, $filialId)) {
            return 'Selecione um funcionário válido desta filial.';
        }
        $categoriasAtivas = array_column(Categoria::ativas(), null, 'id');
        if (!isset($categoriasAtivas[$categoriaId])) {
            return 'Selecione uma categoria válida.';
        }
        $partes = date_parse($data);
        if (!empty($partes['errors']) || !checkdate((int) $partes['month'], (int) $partes['day'], (int) $partes['year'])) {
            return 'Data inválida.';
        }
        if ((int) $partes['year'] !== (int) $periodo['ano'] || (int) $partes['month'] !== (int) $periodo['mes']) {
            return 'A data precisa estar dentro do período aberto (mês atual).';
        }
        if (!is_numeric($valorRaw) || (float) $valorRaw <= 0) {
            return 'Informe um valor de venda válido.';
        }

        return null;
    }
}
