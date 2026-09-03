<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Corrida;
use App\Models\Filial;
use App\Models\Funcionario;
use App\Services\CorridaCalculator;

/**
 * Corrida dos Campeões — bonificação trimestral, competição única entre todos os
 * funcionários (sem separar por filial). Admin gerencia edições/grupos/grade;
 * gerente e funcionário têm a vitrine somente-leitura dos rankings.
 */
final class CorridaController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $edicoes = Corrida::edicoes();
        $edicao = $this->resolverEdicao($edicoes);

        if (Auth::papel() === Auth::PAPEL_ADMIN) {
            $funcionarios = $this->funcionariosComFilial();
            $grade = [];
            if ($edicao !== null) {
                $grade = Corrida::grade(
                    array_column($funcionarios, 'id'),
                    array_column(Corrida::grupos((int) $edicao['id']), 'id'),
                    (int) $edicao['id']
                );
            }
            $this->render('corrida/painel', $this->dadosComuns($edicao, $edicoes) + [
                'funcionarios' => $funcionarios,
                'grade' => $grade,
            ]);
            return;
        }

        $funcionarioId = Funcionario::idPorUsuario((int) Auth::id());
        $this->render('corrida/vitrine', $this->dadosComuns($edicao, $edicoes) + [
            'destaqueFuncionarioId' => $funcionarioId ?: null,
        ]);
    }

    // ----------------------------------------------------------------
    // Edições (admin)
    // ----------------------------------------------------------------

    public function criarEdicao(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        [$trimestre, $ano, $nome, $inicio, $fim, $erro] = $this->lerFormularioEdicao();
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect('/corrida');
        }

        try {
            $id = Corrida::criarEdicao($trimestre, $ano, $nome, $inicio, $fim, (int) Auth::id());
        } catch (\PDOException $e) {
            Flash::set('erro', 'Já existe uma edição para esse trimestre/ano.');
            $this->redirect('/corrida');
        }

        Audit::log('criar', 'corrida_edicao', $id, "{$trimestre}/{$ano}");
        Flash::set('sucesso', 'Edição criada.');
        $this->redirect("/corrida?edicao={$id}");
    }

    public function atualizarEdicao(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $id;
        $edicao = Corrida::edicao($edicaoId);
        if ($edicao === null) {
            $this->redirect('/corrida');
        }
        if ($edicao['status'] === 'fechada') {
            Flash::set('erro', 'Esta edição está fechada — reabra antes de editar.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        [$trimestre, $ano, $nome, $inicio, $fim, $erro] = $this->lerFormularioEdicao();
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        try {
            Corrida::atualizarEdicao($edicaoId, $trimestre, $ano, $nome, $inicio, $fim);
        } catch (\PDOException $e) {
            Flash::set('erro', 'Já existe uma edição para esse trimestre/ano.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Audit::log('atualizar', 'corrida_edicao', $edicaoId);
        Flash::set('sucesso', 'Edição atualizada.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    public function excluirEdicao(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $id;
        $edicao = Corrida::edicao($edicaoId);
        if ($edicao === null) {
            $this->redirect('/corrida');
        }
        if ($edicao['status'] === 'fechada') {
            Flash::set('erro', 'Não dá para excluir uma edição fechada. Reabra primeiro.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Corrida::excluirEdicao($edicaoId);
        Audit::log('excluir', 'corrida_edicao', $edicaoId);
        Flash::set('sucesso', 'Edição excluída.');
        $this->redirect('/corrida');
    }

    public function fecharEdicao(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $id;
        if (!Corrida::estaAberta($edicaoId)) {
            Flash::set('erro', 'Esta edição não está aberta.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Corrida::fecharEdicao($edicaoId, (int) Auth::id());
        Audit::log('fechar', 'corrida_edicao', $edicaoId);
        Flash::set('sucesso', 'Edição fechada — resultados congelados.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    public function reabrirEdicao(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $id;
        $edicao = Corrida::edicao($edicaoId);
        if ($edicao === null || $edicao['status'] !== 'fechada') {
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Corrida::reabrirEdicao($edicaoId);
        Audit::log('reabrir', 'corrida_edicao', $edicaoId);
        Flash::set('sucesso', 'Edição reaberta.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    // ----------------------------------------------------------------
    // Grupos (admin)
    // ----------------------------------------------------------------

    public function criarGrupo(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $this->input('edicao_id', 0);
        if (!Corrida::estaAberta($edicaoId)) {
            Flash::set('erro', 'A edição precisa estar aberta para adicionar grupos.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        [$nome, $premio, $erro] = $this->lerFormularioGrupo();
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        $grupoId = Corrida::criarGrupo($edicaoId, $nome, $premio);
        Audit::log('criar', 'corrida_grupo', $grupoId, "edicao={$edicaoId}");
        Flash::set('sucesso', 'Grupo adicionado.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    public function atualizarGrupo(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $grupo = Corrida::grupo((int) $id);
        if ($grupo === null) {
            $this->redirect('/corrida');
        }
        $edicaoId = (int) $grupo['edicao_id'];
        if (!Corrida::estaAberta($edicaoId)) {
            Flash::set('erro', 'A edição está fechada — reabra para editar grupos.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        [$nome, $premio, $erro] = $this->lerFormularioGrupo();
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Corrida::atualizarGrupo((int) $id, $nome, $premio);
        Audit::log('atualizar', 'corrida_grupo', (int) $id);
        Flash::set('sucesso', 'Grupo atualizado.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    public function excluirGrupo(string $id): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $grupo = Corrida::grupo((int) $id);
        if ($grupo === null) {
            $this->redirect('/corrida');
        }
        $edicaoId = (int) $grupo['edicao_id'];
        if (!Corrida::estaAberta($edicaoId)) {
            Flash::set('erro', 'A edição está fechada — reabra para excluir grupos.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        Corrida::excluirGrupo((int) $id);
        Audit::log('excluir', 'corrida_grupo', (int) $id);
        Flash::set('sucesso', 'Grupo removido.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    // ----------------------------------------------------------------
    // Grade de lançamento (admin)
    // ----------------------------------------------------------------

    public function salvarGrade(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $edicaoId = (int) $this->input('edicao_id', 0);
        if (!Corrida::estaAberta($edicaoId)) {
            Flash::set('erro', 'A edição precisa estar aberta para lançar valores.');
            $this->redirect("/corrida?edicao={$edicaoId}");
        }

        $grupoIds = array_column(Corrida::grupos($edicaoId), 'id');
        $funcionarioIds = array_column($this->funcionariosComFilial(), 'id');
        $post = $this->input('valor', []);
        $post = is_array($post) ? $post : [];

        $valores = [];
        foreach ($funcionarioIds as $funcionarioId) {
            foreach ($grupoIds as $grupoId) {
                $bruto = $post[$funcionarioId][$grupoId] ?? '';
                $raw = trim(str_replace(',', '.', (string) $bruto));
                if ($raw === '') {
                    $raw = '0';
                }
                if (!is_numeric($raw) || (float) $raw < 0) {
                    Flash::set('erro', 'Há um valor inválido na grade de lançamento.');
                    $this->redirect("/corrida?edicao={$edicaoId}");
                }
                $valores[(int) $funcionarioId][(int) $grupoId] = (float) $raw;
            }
        }

        Corrida::salvarGrade($valores, (int) Auth::id());
        Audit::log('salvar_grade', 'corrida_edicao', $edicaoId);
        Flash::set('sucesso', 'Valores da corrida atualizados.');
        $this->redirect("/corrida?edicao={$edicaoId}");
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** @param list<array<string,mixed>> $edicoes */
    private function resolverEdicao(array $edicoes): ?array
    {
        $solicitada = (int) $this->input('edicao', 0);
        if ($solicitada > 0) {
            foreach ($edicoes as $e) {
                if ((int) $e['id'] === $solicitada) {
                    return $e;
                }
            }
        }

        return Corrida::edicaoPadrao();
    }

    /**
     * Dados partilhados entre o painel (admin) e a vitrine (gerente/funcionário):
     * cabeçalho da edição, ranking por grupo (ao vivo) e o ranking geral da aba selecionada.
     */
    private function dadosComuns(?array $edicao, array $edicoes): array
    {
        $nomesFiliais = [];
        foreach (Filial::all() as $f) {
            $nomesFiliais[(int) $f['id']] = $f['nome'];
        }

        if ($edicao === null) {
            return [
                'edicao' => null,
                'edicoes' => $edicoes,
                'grupos' => [],
                'gruposRank' => [],
                'rankingGeral' => [],
                'abaGeral' => 'trimestre',
                'nomesFiliais' => $nomesFiliais,
                'totalPremiado' => 0.0,
            ];
        }

        $edicaoId = (int) $edicao['id'];
        $grupos = Corrida::grupos($edicaoId);

        $gruposRank = [];
        foreach ($grupos as $grupo) {
            $gruposRank[] = [
                'grupo' => $grupo,
                'linhas' => CorridaCalculator::rankingGrupo(
                    Corrida::lancamentosDoGrupo((int) $grupo['id']),
                    (float) $grupo['premio_bruto']
                ),
            ];
        }

        $abaGeral = in_array($this->input('rg'), ['trimestre', 'semestre', 'ano'], true)
            ? (string) $this->input('rg')
            : 'trimestre';

        $relacionadas = Corrida::edicoesRelacionadas($edicao);
        $escopo = match ($abaGeral) {
            'semestre' => $relacionadas['semestre'],
            'ano' => $relacionadas['ano'],
            default => [$edicaoId],
        };
        $rankingGeral = CorridaCalculator::rankingGeral(Corrida::lancamentosDasEdicoes($escopo));

        return [
            'edicao' => $edicao,
            'edicoes' => $edicoes,
            'grupos' => $grupos,
            'gruposRank' => $gruposRank,
            'rankingGeral' => $rankingGeral,
            'abaGeral' => $abaGeral,
            'nomesFiliais' => $nomesFiliais,
            'totalPremiado' => $edicao['status'] === 'fechada' ? Corrida::totalPremiado($edicaoId) : 0.0,
        ];
    }

    /** @return list<array{id:int, nome:string, filial_id:?int}> todos os funcionários ativos da rede */
    private function funcionariosComFilial(): array
    {
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['funcionario_id'],
            'nome' => (string) $r['nome'],
            'filial_id' => $r['filial_id'] !== null ? (int) $r['filial_id'] : null,
        ], Funcionario::todosComFilialPrincipal());
    }

    /** @return array{0:int,1:int,2:string,3:string,4:string,5:?string} trimestre, ano, nome, inicio, fim, erro */
    private function lerFormularioEdicao(): array
    {
        $trimestre = (int) $this->input('trimestre', 0);
        $ano = (int) $this->input('ano', 0);
        $nome = trim((string) $this->input('nome', ''));
        $inicio = trim((string) $this->input('data_inicio', ''));
        $fim = trim((string) $this->input('data_fim', ''));

        $erro = null;
        if ($trimestre < 1 || $trimestre > 4) {
            $erro = 'Trimestre precisa ser de 1 a 4.';
        } elseif ($ano < 2020 || $ano > 2100) {
            $erro = 'Ano inválido.';
        } elseif (!$this->dataValida($inicio) || !$this->dataValida($fim)) {
            $erro = 'Preencha as datas de início e fim (AAAA-MM-DD).';
        } elseif ($fim < $inicio) {
            $erro = 'A data de fim precisa ser igual ou posterior à de início.';
        }

        return [$trimestre, $ano, $nome, $inicio, $fim, $erro];
    }

    /** @return array{0:string,1:float,2:?string} nome, premio_bruto, erro */
    private function lerFormularioGrupo(): array
    {
        $nome = trim((string) $this->input('nome', ''));
        $premioRaw = trim(str_replace(',', '.', (string) $this->input('premio_bruto', '')));

        $erro = null;
        if ($nome === '') {
            $erro = 'Dê um nome ao grupo.';
        } elseif ($premioRaw === '' || !is_numeric($premioRaw) || (float) $premioRaw < 0) {
            $erro = 'Prêmio bruto precisa ser um valor válido (0 ou mais).';
        }

        return [$nome, (float) $premioRaw, $erro];
    }

    private function dataValida(string $data): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $data);

        return $d !== false && $d->format('Y-m-d') === $data;
    }
}
