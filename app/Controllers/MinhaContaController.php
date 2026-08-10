<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Funcionario;
use App\Models\Usuario;

final class MinhaContaController extends Controller
{
    private const AVATAR_DIR = APP_DIR . '/../public/uploads/avatares';
    private const AVATAR_URL_BASE = '/uploads/avatares';
    private const AVATAR_TAMANHO_MAX = 2 * 1024 * 1024;
    private const AVATAR_EXTENSOES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function index(): void
    {
        Auth::require();

        $funcionario = Funcionario::porUsuario((int) Auth::id());
        if ($funcionario === null) {
            $this->render('minha_conta/sem_vinculo', []);
            return;
        }

        $this->render('minha_conta/index', ['funcionario' => $funcionario, 'erro' => null]);
    }

    public function trocarSenha(): void
    {
        Auth::require();
        $this->requireCsrf();

        $senhaAtual = (string) $this->input('senha_atual', '');
        $novaSenha = (string) $this->input('nova_senha', '');
        $confirmacao = (string) $this->input('confirmacao', '');

        $usuario = Usuario::find((int) Auth::id());
        if ($usuario === null || !password_verify($senhaAtual, $usuario['senha_hash'])) {
            Flash::set('erro', 'Senha atual incorreta.');
            $this->redirect('/minha-conta');
        }
        if (strlen($novaSenha) < 8) {
            Flash::set('erro', 'A nova senha precisa ter pelo menos 8 caracteres.');
            $this->redirect('/minha-conta');
        }
        if ($novaSenha !== $confirmacao) {
            Flash::set('erro', 'A confirmação não bate com a nova senha.');
            $this->redirect('/minha-conta');
        }

        Usuario::atualizarSenha((int) Auth::id(), password_hash($novaSenha, PASSWORD_BCRYPT));
        Audit::log('trocar_senha', 'usuario', (int) Auth::id());
        Flash::set('sucesso', 'Senha atualizada.');
        $this->redirect('/minha-conta');
    }

    public function atualizarAvatar(): void
    {
        Auth::require();
        $this->requireCsrf();

        $funcionario = Funcionario::porUsuario((int) Auth::id());
        if ($funcionario === null) {
            Flash::set('erro', 'Usuário não encontrado.');
            $this->redirect('/minha-conta');
        }

        $arquivo = $_FILES['avatar'] ?? null;
        if ($arquivo === null || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            Flash::set('erro', 'Selecione uma imagem.');
            $this->redirect('/minha-conta');
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Flash::set('erro', 'Falha no envio da imagem.');
            $this->redirect('/minha-conta');
        }
        if ($arquivo['size'] > self::AVATAR_TAMANHO_MAX) {
            Flash::set('erro', 'A imagem precisa ter no máximo 2MB.');
            $this->redirect('/minha-conta');
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $mimeEsperado = self::AVATAR_EXTENSOES[$extensao] ?? null;
        $infoImagem = @getimagesize($arquivo['tmp_name']);
        if ($mimeEsperado === null || $infoImagem === false || $infoImagem['mime'] !== $mimeEsperado) {
            Flash::set('erro', 'Formato inválido. Use JPG, PNG ou WEBP.');
            $this->redirect('/minha-conta');
        }

        if (!is_dir(self::AVATAR_DIR)) {
            mkdir(self::AVATAR_DIR, 0755, true);
        }

        $nomeArquivo = 'avatar_' . $funcionario['id'] . '_' . time() . '.' . $extensao;
        $destino = self::AVATAR_DIR . '/' . $nomeArquivo;
        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Flash::set('erro', 'Não foi possível salvar a imagem.');
            $this->redirect('/minha-conta');
        }

        $antigo = $funcionario['avatar_path'] ?? null;
        if ($antigo !== null && $antigo !== '') {
            $caminhoAntigo = self::AVATAR_DIR . '/' . basename($antigo);
            if (is_file($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
        }

        Funcionario::atualizarAvatar((int) $funcionario['id'], self::AVATAR_URL_BASE . '/' . $nomeArquivo);
        Audit::log('atualizar_avatar', 'funcionario', (int) $funcionario['id']);
        Flash::set('sucesso', 'Foto atualizada.');
        $this->redirect('/minha-conta');
    }
}
