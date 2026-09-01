<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Comissao;
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

        $this->render('fechamento/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'linhas' => ResumoCalculator::porFilial($filialId, (int) $periodo['id']),
        ]);
    }

    public function aprovar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $periodo = Periodo::ativo();
        if ($periodo['status'] === 'aprovado') {
            Flash::set('erro', 'Este período já foi aprovado.');
            $this->redirect('/fechamento');
        }

        foreach (ResumoCalculator::rede((int) $periodo['id']) as $linha) {
            Comissao::upsert(
                (int) $periodo['id'],
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

        Periodo::aprovar((int) $periodo['id'], (int) Auth::id());
        Audit::log('aprovar', 'periodo', (int) $periodo['id']);
        Flash::set('sucesso', 'Fechamento aprovado — o período está travado para novos lançamentos.');
        $this->redirect('/fechamento');
    }
}
