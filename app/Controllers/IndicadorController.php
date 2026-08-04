<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Funcionario;
use App\Models\Indicador;
use App\Models\Periodo;

final class IndicadorController extends Controller
{
    use EscopoFilialTrait;

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);

        $filiaisPermitidas = $this->filiaisPermitidas();
        if (empty($filiaisPermitidas)) {
            $this->render('indicadores/sem_filial', []);
            return;
        }

        $filialId = $this->resolverFilialId($filiaisPermitidas);
        $periodo = Periodo::atual();

        $this->render('indicadores/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'funcionarios' => Indicador::funcionariosComIndicador($filialId, (int) $periodo['id']),
            'rentabFilial' => Indicador::rentabilidadeFilial($filialId, (int) $periodo['id']),
            'checklist' => Indicador::checklist($filialId, (int) $periodo['id']),
        ]);
    }

    public function salvar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));
        $periodo = Periodo::atual();

        if ($periodo['status'] !== 'aberto') {
            Flash::set('erro', 'Este período já foi fechado — não é mais possível alterar indicadores.');
            $this->redirect("/indicadores?filial_id={$filialId}");
        }

        [$indicadoresFuncionarios, $erroFuncionarios] = $this->validarIndicadoresFuncionarios($filialId);
        if ($erroFuncionarios !== null) {
            Flash::set('erro', $erroFuncionarios);
            $this->redirect("/indicadores?filial_id={$filialId}");
        }

        $rentabRaw = trim(str_replace(',', '.', (string) $this->input('rentabilidade_filial', '')));
        if ($rentabRaw === '' || !is_numeric($rentabRaw) || (float) $rentabRaw < 0 || (float) $rentabRaw > 100) {
            Flash::set('erro', 'Informe a rentabilidade da filial (0 a 100%).');
            $this->redirect("/indicadores?filial_id={$filialId}");
        }

        $checklist = [
            'c1_sem_falta_injustificada' => $this->input('c1') !== null,
            'c2_cumpriu_escala' => $this->input('c2') !== null,
            'c3_setor_organizado' => $this->input('c3') !== null,
            'c4_ajudou_treinou_colega' => $this->input('c4') !== null,
            'c5_loja_bateu_meta_coletiva' => $this->input('c5') !== null,
        ];

        Indicador::salvarTudo((int) $periodo['id'], $filialId, $indicadoresFuncionarios, (float) $rentabRaw, $checklist);

        Audit::log('salvar', 'indicador_fechamento', $filialId, "periodo={$periodo['id']}");
        Flash::set('sucesso', 'Indicadores salvos.');
        $this->redirect("/indicadores?filial_id={$filialId}");
    }

    /** @return array{0: array, 1: string|null} */
    private function validarIndicadoresFuncionarios(int $filialId): array
    {
        $idsValidos = array_column(Funcionario::porFilial($filialId), 'id');
        $descontoPost = $this->input('desconto', []);
        $rentabPost = $this->input('rentab', []);
        $desconto = is_array($descontoPost) ? $descontoPost : [];
        $rentab = is_array($rentabPost) ? $rentabPost : [];

        $linhas = [];
        foreach ($idsValidos as $funcionarioId) {
            $descontoRaw = trim(str_replace(',', '.', (string) ($desconto[$funcionarioId] ?? '')));
            $rentabRaw = trim(str_replace(',', '.', (string) ($rentab[$funcionarioId] ?? '')));

            if ($descontoRaw === '' && $rentabRaw === '') {
                continue;
            }
            if ($descontoRaw === '' || $rentabRaw === '') {
                return [[], 'Preencha desconto médio e rentabilidade juntos para cada funcionário (ou deixe os dois em branco).'];
            }
            if (!is_numeric($descontoRaw) || (float) $descontoRaw < 0 || (float) $descontoRaw > 100) {
                return [[], 'Desconto médio inválido para um dos funcionários (use 0 a 100).'];
            }
            if (!is_numeric($rentabRaw) || (float) $rentabRaw < 0 || (float) $rentabRaw > 100) {
                return [[], 'Rentabilidade inválida para um dos funcionários (use 0 a 100).'];
            }

            $linhas[] = [
                'funcionario_id' => (int) $funcionarioId,
                'desconto_medio' => (float) $descontoRaw,
                'rentabilidade_pct' => (float) $rentabRaw,
            ];
        }

        return [$linhas, null];
    }
}
