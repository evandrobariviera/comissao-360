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

final class MetaController extends Controller
{
    use EscopoFilialTrait;

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);

        $filiaisPermitidas = $this->filiaisPermitidas();
        if (empty($filiaisPermitidas)) {
            $this->render('metas/sem_filial', []);
            return;
        }

        $filialId = $this->resolverFilialId($filiaisPermitidas);
        $periodo = Periodo::atual();
        $funcionarios = Funcionario::porFilial($filialId);
        $categorias = Categoria::ativas();

        $this->render('metas/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'funcionarios' => $funcionarios,
            'categorias' => $categorias,
            'metaFilial' => Meta::filial($filialId, (int) $periodo['id']),
            'grid' => Meta::grid(array_column($funcionarios, 'id'), array_column($categorias, 'id'), (int) $periodo['id']),
        ]);
    }

    public function salvar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $filiaisPermitidas = $this->filiaisPermitidas();
        $filialId = $this->resolverFilialId($filiaisPermitidas, (int) $this->input('filial_id', 0));
        $periodo = Periodo::atual();

        if ($periodo['status'] !== 'aberto') {
            Flash::set('erro', 'Este período já foi fechado — não é mais possível alterar metas.');
            $this->redirect("/metas?filial_id={$filialId}");
        }

        $metaVendaRaw = trim(str_replace(',', '.', (string) $this->input('meta_venda_filial', '')));
        $metaRentabRaw = trim(str_replace(',', '.', (string) $this->input('meta_rentabilidade_filial', '')));
        $valorPremioRaw = trim(str_replace(',', '.', (string) $this->input('valor_premio', '')));
        $ticketPisoRaw = trim(str_replace(',', '.', (string) $this->input('ticket_medio_piso', '0')));
        $ticketTetoRaw = trim(str_replace(',', '.', (string) $this->input('ticket_medio_teto', '0')));
        if ($ticketPisoRaw === '') {
            $ticketPisoRaw = '0';
        }
        if ($ticketTetoRaw === '') {
            $ticketTetoRaw = '0';
        }

        if (!is_numeric($metaVendaRaw) || (float) $metaVendaRaw < 0
            || !is_numeric($metaRentabRaw) || (float) $metaRentabRaw < 0 || (float) $metaRentabRaw > 100
            || !is_numeric($valorPremioRaw) || (float) $valorPremioRaw < 0) {
            Flash::set('erro', 'Preencha meta de venda, rentabilidade (0 a 100%) e prêmio da filial com valores válidos.');
            $this->redirect("/metas?filial_id={$filialId}");
        }

        if (!is_numeric($ticketPisoRaw) || (float) $ticketPisoRaw < 0
            || !is_numeric($ticketTetoRaw) || (float) $ticketTetoRaw < 0
            || (float) $ticketTetoRaw > 0 && (float) $ticketTetoRaw <= (float) $ticketPisoRaw) {
            Flash::set('erro', 'Ticket médio: piso e teto precisam ser válidos e o teto maior que o piso (ou deixe os dois em 0 para não usar essa pontuação ainda).');
            $this->redirect("/metas?filial_id={$filialId}");
        }

        $idsValidos = array_column(Funcionario::porFilial($filialId), 'id');
        $categoriaIdsValidos = array_column(Categoria::ativas(), 'id');
        $metasPost = $this->input('meta', []);

        $metasFuncionarios = [];
        foreach ($idsValidos as $funcionarioId) {
            foreach ($categoriaIdsValidos as $categoriaId) {
                $bruto = is_array($metasPost) ? ($metasPost[$funcionarioId][$categoriaId] ?? '0') : '0';
                $valorRaw = trim(str_replace(',', '.', (string) $bruto));
                if ($valorRaw === '') {
                    $valorRaw = '0';
                }
                if (!is_numeric($valorRaw) || (float) $valorRaw < 0) {
                    Flash::set('erro', 'Uma das metas por funcionário/categoria está inválida.');
                    $this->redirect("/metas?filial_id={$filialId}");
                }
                $metasFuncionarios[] = [
                    'funcionario_id' => $funcionarioId,
                    'categoria_id' => $categoriaId,
                    'meta_venda' => (float) $valorRaw,
                ];
            }
        }

        Meta::salvarTudo(
            (int) $periodo['id'],
            $filialId,
            [
                'meta_venda' => (float) $metaVendaRaw,
                'meta_rentabilidade' => (float) $metaRentabRaw,
                'valor_premio' => (float) $valorPremioRaw,
                'ticket_medio_piso' => (float) $ticketPisoRaw,
                'ticket_medio_teto' => (float) $ticketTetoRaw,
            ],
            $metasFuncionarios
        );

        Audit::log('salvar', 'meta', $filialId, "periodo={$periodo['id']}");
        Flash::set('sucesso', 'Metas salvas.');
        $this->redirect("/metas?filial_id={$filialId}");
    }
}
