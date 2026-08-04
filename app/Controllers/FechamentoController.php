<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\EscopoFilialTrait;
use App\Core\Flash;
use App\Models\Comissao;
use App\Models\Funcionario;
use App\Models\Parametro;
use App\Models\Periodo;
use App\Services\ComissaoCalculator;
use App\Services\Pontuacao360Calculator;
use App\Services\PremioFilialService;

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
        $periodo = Periodo::atual();
        $parametros = Parametro::todos();

        $linhas = [];
        foreach (Funcionario::porFilial($filialId) as $f) {
            $linhas[] = self::calcularLinha((int) $f['id'], $f['nome'], $filialId, (int) $periodo['id'], $parametros);
        }

        $this->render('fechamento/index', [
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'linhas' => $linhas,
        ]);
    }

    public function aprovar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $periodo = Periodo::atual();
        if ($periodo['status'] === 'aprovado') {
            Flash::set('erro', 'Este período já foi aprovado.');
            $this->redirect('/fechamento');
        }

        $parametros = Parametro::todos();

        foreach (Funcionario::todosComFilialPrincipal() as $f) {
            $filialId = (int) ($f['filial_id'] ?? 0);
            if ($filialId === 0) {
                continue; // funcionário sem filial vinculada — nada a calcular
            }

            $linha = self::calcularLinha((int) $f['funcionario_id'], $f['nome'], $filialId, (int) $periodo['id'], $parametros);

            Comissao::upsert(
                (int) $periodo['id'],
                (int) $f['funcionario_id'],
                $filialId,
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

    private static function calcularLinha(int $funcionarioId, string $nome, int $filialId, int $periodoId, array $parametros): array
    {
        $comissao = ComissaoCalculator::calcular($funcionarioId, $periodoId);
        $pontuacao = Pontuacao360Calculator::calcular($funcionarioId, $filialId, $periodoId, $parametros);
        $premio = PremioFilialService::calcular($filialId, $periodoId);
        $comissaoAjustada = $comissao['comissao_base'] * $pontuacao['multiplicador_protegido'];
        $total = $comissaoAjustada + $premio;

        return [
            'funcionario_id' => $funcionarioId,
            'nome' => $nome,
            'comissao_base' => $comissao['comissao_base'],
            'detalhe_categorias' => $comissao['detalhe'],
            'pontuacao' => $pontuacao,
            'premio_filial' => $premio,
            'comissao_ajustada' => $comissaoAjustada,
            'total' => $total,
        ];
    }
}
