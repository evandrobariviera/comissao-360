<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Filial;
use App\Models\Funcionario;
use PDOException;

final class UsuarioController extends Controller
{
    private const PAPEIS_VALIDOS = [Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE, Auth::PAPEL_FUNCIONARIO];

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('admin/usuarios/index', ['usuarios' => Funcionario::listAll()]);
    }

    public function novo(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('admin/usuarios/form', [
            'usuario' => null,
            'filiais' => Filial::all(),
            'erro' => null,
        ]);
    }

    public function criar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        [$dados, $erro] = $this->validar(criando: true);
        if ($erro !== null) {
            $this->render('admin/usuarios/form', ['usuario' => $dados, 'filiais' => Filial::all(), 'erro' => $erro]);
            return;
        }

        try {
            $id = Funcionario::create($dados['nome'], $dados['cargo'], $dados['email'], $dados['senha'], $dados['papel'], $dados['filiais']);
        } catch (PDOException) {
            $this->render('admin/usuarios/form', ['usuario' => $dados, 'filiais' => Filial::all(), 'erro' => 'Já existe um usuário com esse e-mail.']);
            return;
        }

        Audit::log('criar', 'funcionario', $id);
        Flash::set('sucesso', 'Usuário cadastrado.');
        $this->redirect('/usuarios');
    }

    public function editar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $usuario = Funcionario::find((int) $id);
        if ($usuario === null) {
            Flash::set('erro', 'Usuário não encontrado.');
            $this->redirect('/usuarios');
        }
        $this->render('admin/usuarios/form', ['usuario' => $usuario, 'filiais' => Filial::all(), 'erro' => null]);
    }

    public function atualizar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $atual = Funcionario::find((int) $id);
        if ($atual === null) {
            Flash::set('erro', 'Usuário não encontrado.');
            $this->redirect('/usuarios');
        }

        [$dados, $erro] = $this->validar(criando: false);
        if ($erro !== null) {
            $dados['id'] = $id;
            $this->render('admin/usuarios/form', ['usuario' => $dados, 'filiais' => Filial::all(), 'erro' => $erro]);
            return;
        }

        try {
            Funcionario::update(
                (int) $id,
                (int) $atual['usuario_id'],
                $dados['nome'],
                $dados['cargo'],
                $dados['email'],
                $dados['papel'],
                $dados['senha'] !== '' ? $dados['senha'] : null,
                $dados['filiais']
            );
        } catch (PDOException) {
            $dados['id'] = $id;
            $this->render('admin/usuarios/form', ['usuario' => $dados, 'filiais' => Filial::all(), 'erro' => 'Já existe um usuário com esse e-mail.']);
            return;
        }

        Audit::log('atualizar', 'funcionario', (int) $id);
        Flash::set('sucesso', 'Usuário atualizado.');
        $this->redirect('/usuarios');
    }

    public function alternarStatus(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $usuario = Funcionario::find((int) $id);
        if ($usuario === null) {
            Flash::set('erro', 'Usuário não encontrado.');
            $this->redirect('/usuarios');
        }
        if ((int) $usuario['usuario_id'] === Auth::id()) {
            Flash::set('erro', 'Você não pode desativar o próprio usuário.');
            $this->redirect('/usuarios');
        }

        Funcionario::alternarStatus((int) $id, (int) $usuario['usuario_id']);
        Audit::log('alternar_status', 'funcionario', (int) $id);
        Flash::set('sucesso', 'Status atualizado.');
        $this->redirect('/usuarios');
    }

    /** @return array{0: array, 1: string|null} */
    private function validar(bool $criando): array
    {
        $nome = trim((string) $this->input('nome', ''));
        $cargo = trim((string) $this->input('cargo', ''));
        $email = trim((string) $this->input('email', ''));
        $papel = (string) $this->input('papel', '');
        $senha = trim((string) $this->input('senha', ''));
        $filiaisPost = $this->input('filiais', []);
        $filiais = is_array($filiaisPost) ? array_map('intval', $filiaisPost) : [];

        $dados = [
            'nome' => $nome,
            'cargo' => $cargo !== '' ? $cargo : null,
            'email' => $email,
            'papel' => $papel,
            'senha' => $senha,
            'filiais' => $filiais,
        ];

        if ($nome === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [$dados, 'Preencha nome e um e-mail válido.'];
        }
        if (!in_array($papel, self::PAPEIS_VALIDOS, true)) {
            return [$dados, 'Selecione um papel de acesso válido.'];
        }
        if ($criando && strlen($senha) < 8) {
            return [$dados, 'A senha inicial precisa ter pelo menos 8 caracteres.'];
        }
        if (!$criando && $senha !== '' && strlen($senha) < 8) {
            return [$dados, 'A nova senha precisa ter pelo menos 8 caracteres (ou deixe em branco para não alterar).'];
        }
        if (empty($filiais)) {
            return [$dados, 'Selecione pelo menos uma filial.'];
        }

        return [$dados, null];
    }
}
