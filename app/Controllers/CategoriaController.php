<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Categoria;
use PDOException;

final class CategoriaController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->redirect('/regras?aba=faixas');
    }

    public function novo(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->render('admin/categorias/novo', ['categoria' => null, 'erro' => null]);
    }

    public function criar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $nome = trim((string) $this->input('nome', ''));
        $ordem = (int) $this->input('ordem', 0);

        if ($nome === '') {
            $this->render('admin/categorias/novo', ['categoria' => ['nome' => $nome, 'ordem' => $ordem], 'erro' => 'Preencha o nome da categoria.']);
            return;
        }

        try {
            $id = Categoria::create($nome, $ordem);
        } catch (PDOException) {
            $this->render('admin/categorias/novo', ['categoria' => ['nome' => $nome, 'ordem' => $ordem], 'erro' => 'Já existe uma categoria com esse nome.']);
            return;
        }

        Audit::log('criar', 'categoria', $id);
        Flash::set('sucesso', 'Categoria criada. Agora configure as faixas de comissão.');
        $this->redirect("/categorias/{$id}/editar");
    }

    public function editar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $categoria = Categoria::find((int) $id);
        if ($categoria === null) {
            Flash::set('erro', 'Categoria não encontrada.');
            $this->redirect('/regras?aba=faixas');
        }

        $this->render('admin/categorias/editar', [
            'categoria' => $categoria,
            'faixas' => Categoria::faixas((int) $id),
            'composicoes' => Categoria::composicoes((int) $id),
            'outrasCategorias' => Categoria::listaParaSelect((int) $id),
        ]);
    }

    public function atualizar(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();
        $categoriaId = (int) $id;

        $nome = trim((string) $this->input('nome', ''));
        $ordem = (int) $this->input('ordem', 0);
        if ($nome === '') {
            Flash::set('erro', 'Preencha o nome da categoria.');
            $this->redirect("/categorias/{$id}/editar");
        }

        [$faixas, $erroFaixas] = $this->validarFaixas();
        if ($erroFaixas !== null) {
            Flash::set('erro', $erroFaixas);
            $this->redirect("/categorias/{$id}/editar");
        }

        [$composicoes, $erroComposicoes] = $this->validarComposicoes($categoriaId);
        if ($erroComposicoes !== null) {
            Flash::set('erro', $erroComposicoes);
            $this->redirect("/categorias/{$id}/editar");
        }

        try {
            Categoria::salvarCompleta($categoriaId, $nome, $ordem, $faixas, $composicoes);
        } catch (PDOException) {
            Flash::set('erro', 'Já existe uma categoria com esse nome.');
            $this->redirect("/categorias/{$id}/editar");
        }

        Audit::log('atualizar', 'categoria', $categoriaId);
        Flash::set('sucesso', 'Categoria atualizada.');
        $this->redirect('/regras?aba=faixas');
    }

    public function alternarStatus(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();
        Categoria::alternarStatus((int) $id);
        Audit::log('alternar_status', 'categoria', (int) $id);
        Flash::set('sucesso', 'Status atualizado.');
        $this->redirect('/regras?aba=faixas');
    }

    /** @return array{0: array, 1: string|null} */
    private function validarFaixas(): array
    {
        $limitesPost = $this->input('faixa_limite', []);
        $percentuaisPost = $this->input('faixa_percentual', []);
        $limites = is_array($limitesPost) ? $limitesPost : [];
        $percentuais = is_array($percentuaisPost) ? $percentuaisPost : [];

        $linhas = [];
        $total = max(count($limites), count($percentuais));
        for ($i = 0; $i < $total; $i++) {
            $percentualRaw = trim(str_replace(',', '.', (string) ($percentuais[$i] ?? '')));
            if ($percentualRaw === '') {
                continue;
            }
            if (!is_numeric($percentualRaw) || (float) $percentualRaw <= 0) {
                return [[], 'Percentual inválido na linha ' . ($i + 1) . ' das faixas.'];
            }

            $limiteRaw = trim(str_replace(',', '.', (string) ($limites[$i] ?? '')));
            $limite = null;
            if ($limiteRaw !== '') {
                if (!is_numeric($limiteRaw) || (float) $limiteRaw <= 0) {
                    return [[], 'Limite inválido na linha ' . ($i + 1) . ' das faixas.'];
                }
                $limite = (float) $limiteRaw;
            }

            $linhas[] = ['limite_ate' => $limite, 'percentual' => (float) $percentualRaw];
        }

        if (empty($linhas)) {
            return [[], 'Cadastre ao menos uma faixa de comissão.'];
        }

        $anterior = 0.0;
        foreach ($linhas as $index => $linha) {
            $ehUltima = $index === count($linhas) - 1;
            if ($linha['limite_ate'] === null && !$ehUltima) {
                return [[], 'Só a última faixa pode ficar sem limite (significa "acima de").'];
            }
            if ($linha['limite_ate'] !== null) {
                if ($linha['limite_ate'] <= $anterior) {
                    return [[], 'Os limites das faixas precisam ser crescentes.'];
                }
                $anterior = $linha['limite_ate'];
            }
        }

        return [$linhas, null];
    }

    /** @return array{0: array, 1: string|null} */
    private function validarComposicoes(int $categoriaId): array
    {
        $tiposPost = $this->input('composicao_tipo', []);
        $categoriasPost = $this->input('composicao_categoria', []);
        $flagsPost = $this->input('composicao_flag', []);
        $tipos = is_array($tiposPost) ? $tiposPost : [];
        $categoriasFonte = is_array($categoriasPost) ? $categoriasPost : [];
        $flags = is_array($flagsPost) ? $flagsPost : [];

        $linhas = [];
        foreach ($tipos as $i => $tipoRaw) {
            $tipo = trim((string) $tipoRaw);
            if ($tipo === '') {
                continue;
            }
            if ($tipo === 'categoria') {
                $fonteCategoriaId = (int) ($categoriasFonte[$i] ?? 0);
                if ($fonteCategoriaId <= 0 || $fonteCategoriaId === $categoriaId) {
                    return [[], 'Selecione uma categoria de origem válida na composição (diferente da própria categoria).'];
                }
                $linhas[] = ['fonte_tipo' => 'categoria', 'fonte_categoria_id' => $fonteCategoriaId, 'fonte_flag' => null];
            } elseif ($tipo === 'flag_venda') {
                $flag = trim((string) ($flags[$i] ?? ''));
                if (!array_key_exists($flag, Categoria::FLAGS_VENDA)) {
                    return [[], 'Selecione uma marcação de venda válida na composição.'];
                }
                $linhas[] = ['fonte_tipo' => 'flag_venda', 'fonte_categoria_id' => null, 'fonte_flag' => $flag];
            } else {
                return [[], 'Tipo de composição inválido.'];
            }
        }

        return [$linhas, null];
    }
}
