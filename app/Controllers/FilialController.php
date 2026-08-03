<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Filial;
use PDOException;

final class FilialController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('admin/filiais/index', ['filiais' => Filial::all()]);
    }

    public function novo(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('admin/filiais/form', ['filial' => null, 'erro' => null]);
    }

    public function criar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $nome = trim((string) $this->input('nome', ''));
        $codigo = trim((string) $this->input('codigo', ''));

        if ($nome === '' || $codigo === '') {
            $this->render('admin/filiais/form', [
                'filial' => ['nome' => $nome, 'codigo' => $codigo],
                'erro' => 'Preencha nome e código.',
            ]);
            return;
        }

        try {
            $id = Filial::create($nome, $codigo);
        } catch (PDOException) {
            $this->render('admin/filiais/form', [
                'filial' => ['nome' => $nome, 'codigo' => $codigo],
                'erro' => 'Já existe uma filial com esse código.',
            ]);
            return;
        }

        Audit::log('criar', 'filial', $id);
        Flash::set('sucesso', 'Filial cadastrada.');
        $this->redirect('/filiais');
    }

    public function editar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $filial = Filial::find((int) $id);
        if ($filial === null) {
            Flash::set('erro', 'Filial não encontrada.');
            $this->redirect('/filiais');
        }
        $this->render('admin/filiais/form', ['filial' => $filial, 'erro' => null]);
    }

    public function atualizar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $nome = trim((string) $this->input('nome', ''));
        $codigo = trim((string) $this->input('codigo', ''));

        if ($nome === '' || $codigo === '') {
            $this->render('admin/filiais/form', [
                'filial' => ['id' => $id, 'nome' => $nome, 'codigo' => $codigo],
                'erro' => 'Preencha nome e código.',
            ]);
            return;
        }

        try {
            Filial::update((int) $id, $nome, $codigo);
        } catch (PDOException) {
            $this->render('admin/filiais/form', [
                'filial' => ['id' => $id, 'nome' => $nome, 'codigo' => $codigo],
                'erro' => 'Já existe uma filial com esse código.',
            ]);
            return;
        }

        Audit::log('atualizar', 'filial', (int) $id);
        Flash::set('sucesso', 'Filial atualizada.');
        $this->redirect('/filiais');
    }

    public function alternarStatus(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();
        Filial::alternarStatus((int) $id);
        Audit::log('alternar_status', 'filial', (int) $id);
        Flash::set('sucesso', 'Status atualizado.');
        $this->redirect('/filiais');
    }
}
