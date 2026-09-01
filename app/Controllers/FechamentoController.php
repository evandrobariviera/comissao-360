<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Comissao;
use App\Models\FechamentoFilial;
use App\Models\Filial;
use App\Models\Periodo;
use App\Services\ResumoCalculator;

final class FechamentoController extends Controller
{
    use EscopoFilialTrait;

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);

        $filiaisPermitidas = $this->filiaisPermitidas();
        if (empty($filiaisPermitidas)) {
            $this->render('fechamento/sem_filial', []);
            return;
        }

        $filialId = $this->resolverFilialId($filiaisPermitidas);
        $periodo = Periodo::ativo();
        $periodoId = (int) $periodo['id'];

        $this->render('fechamento/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'fechamento' => FechamentoFilial::status($periodoId, $filialId),
            'statusPorFilial' => FechamentoFilial::statusPorFilial($periodoId),
            'linhas' => ResumoCalculator::porFilial($filialId, $periodoId),
        ]);
    }

    /** Aprova o fechamento de UMA filial no período ativo — as outras filiais não são afetadas. */
    public function aprovar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $filialId = $this->resolverFilialId(Filial::ativas(), (int) $this->input('filial_id', 0));
        $periodo = Periodo::ativo();
        $periodoId = (int) $periodo['id'];
        $nomeFilial = $this->nomeFilial($filialId);

        if (!FechamentoFilial::estaAberto($periodoId, $filialId)) {
            Flash::set('erro', "O fechamento de {$nomeFilial} já foi aprovado neste período.");
            $this->redirect("/fechamento?filial_id={$filialId}");
        }

        foreach (ResumoCalculator::porFilial($filialId, $periodoId) as $linha) {
            Comissao::upsert(
                $periodoId,
                $linha['funcionario_id'],
                $linha['filial_id'],
                $linha['comissao_base'],
                $linha['pontuacao']['pontuacao_total'],
                $linha['pontuacao']['multiplicador_protegido'],
                $linha['comissao_ajustada'],
                $linha['premio_filial'],
                $linha['total'],
                (int) Auth::id()
            );
        }

        FechamentoFilial::aprovar($periodoId, $filialId, (int) Auth::id());
        Audit::log('aprovar', 'fechamento_filial', $filialId, "periodo={$periodoId}");
        Flash::set('sucesso', "Fechamento de {$nomeFilial} aprovado — lançamentos travados pra essa filial neste período.");
        $this->redirect("/fechamento?filial_id={$filialId}");
    }

    /** Reabre o fechamento de UMA filial já aprovada — volta a permitir lançamentos até aprovar de novo. */
    public function reabrir(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $filialId = $this->resolverFilialId(Filial::ativas(), (int) $this->input('filial_id', 0));
        $periodo = Periodo::ativo();
        $periodoId = (int) $periodo['id'];
        $nomeFilial = $this->nomeFilial($filialId);

        if (FechamentoFilial::estaAberto($periodoId, $filialId)) {
            Flash::set('erro', "O fechamento de {$nomeFilial} já está aberto neste período.");
            $this->redirect("/fechamento?filial_id={$filialId}");
        }

        FechamentoFilial::reabrir($periodoId, $filialId);
        Audit::log('reabrir', 'fechamento_filial', $filialId, "periodo={$periodoId}");
        Flash::set('sucesso', "Fechamento de {$nomeFilial} reaberto — lançamentos voltam a ficar editáveis.");
        $this->redirect("/fechamento?filial_id={$filialId}");
    }

    private function nomeFilial(int $filialId): string
    {
        return Filial::find($filialId)['nome'] ?? "filial #{$filialId}";
    }
}
