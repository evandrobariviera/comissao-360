<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Filial;
use App\Models\Funcionario;
use App\Models\Indicador;
use App\Models\Meta;
use App\Models\Periodo;
use App\Models\Venda;
use App\Services\ResumoCalculator;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $papel = Auth::papel();
        $periodo = Periodo::atual();

        match ($papel) {
            Auth::PAPEL_ADMIN => $this->rede((int) $periodo['id'], $periodo),
            Auth::PAPEL_GERENTE => $this->porFilial((int) $periodo['id'], $periodo),
            default => $this->pessoal((int) $periodo['id'], $periodo),
        };
    }

    private function rede(int $periodoId, array $periodo): void
    {
        $linhas = ResumoCalculator::rede($periodoId);
        $filiais = Filial::ativas();

        $comissaoTotal = array_sum(array_column($linhas, 'total'));
        $vendaRealizada = Meta::totalRedeVendaBrutaRealizada($periodoId);
        $metaRede = Meta::totalRedeVenda($periodoId);

        $filiaisComMeta = [];
        $filiaisBateram = 0;
        foreach ($filiais as $f) {
            $filialId = (int) $f['id'];
            $meta = Meta::filial($filialId, $periodoId);
            $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
            $realizado = $meta !== null ? (float) $meta['venda_bruta_realizada'] : 0.0;
            if ($metaVenda > 0 && $realizado >= $metaVenda) {
                $filiaisBateram++;
            }
            $filiaisComMeta[] = ['nome' => $f['nome'], 'realizado' => $realizado, 'meta' => $metaVenda];
        }

        $ranking = array_map(
            static fn ($l) => ['nome' => $l['nome'], 'valor' => $l['total'], 'formatado' => \App\Core\Viz::money($l['total'])],
            $linhas
        );
        usort($ranking, static fn ($a, $b) => $b['valor'] <=> $a['valor']);
        $ranking = array_slice($ranking, 0, 10);

        $ordemNiveis = Database::pdo()->query('SELECT nivel FROM multiplicador_faixa ORDER BY pontos_de')->fetchAll(\PDO::FETCH_COLUMN);
        $contagem = array_count_values(array_column(array_column($linhas, 'pontuacao'), 'nivel'));
        $distribuicao = [];
        foreach ($ordemNiveis as $nivel) {
            $distribuicao[] = ['nome' => $nivel, 'count' => $contagem[$nivel] ?? 0];
        }

        $this->render('dashboard/rede', [
            'periodo' => $periodo,
            'comissaoTotal' => $comissaoTotal,
            'vendaRealizada' => $vendaRealizada,
            'metaRede' => $metaRede,
            'totalFuncionarios' => count($linhas),
            'filiaisBateram' => $filiaisBateram,
            'totalFiliais' => count($filiais),
            'filiaisComMeta' => $filiaisComMeta,
            'ranking' => $ranking,
            'distribuicao' => $distribuicao,
            'oportunidades' => self::oportunidades($linhas, 5),
        ]);
    }

    private function porFilial(int $periodoId, array $periodo): void
    {
        $filiaisPermitidas = Funcionario::filiaisDoUsuario((int) Auth::id());
        if (empty($filiaisPermitidas)) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $filialId = (int) ($this->input('filial_id', $filiaisPermitidas[0]['id']));
        $ids = array_column($filiaisPermitidas, 'id');
        if (!in_array($filialId, $ids, true)) {
            $filialId = (int) $ids[0];
        }

        $linhas = ResumoCalculator::porFilial($filialId, $periodoId);
        $meta = Meta::filial($filialId, $periodoId);
        $metaVenda = $meta !== null ? (float) $meta['meta_venda'] : 0.0;
        $metaRentab = $meta !== null ? (float) $meta['meta_rentabilidade'] : 0.0;
        $realizado = $meta !== null ? (float) $meta['venda_bruta_realizada'] : 0.0;
        $rentabFilial = Indicador::rentabilidadeFilial($filialId, $periodoId);
        $rentabRealizada = $rentabFilial !== null ? (float) $rentabFilial['rentabilidade_pct'] : 0.0;
        $comissaoTotal = array_sum(array_column($linhas, 'total'));

        $ranking = array_map(
            static fn ($l) => ['nome' => $l['nome'], 'valor' => $l['total'], 'formatado' => \App\Core\Viz::money($l['total'])],
            $linhas
        );
        usort($ranking, static fn ($a, $b) => $b['valor'] <=> $a['valor']);

        $this->render('dashboard/filial', [
            'periodo' => $periodo,
            'filiaisPermitidas' => $filiaisPermitidas,
            'filialId' => $filialId,
            'metaVenda' => $metaVenda,
            'metaRentab' => $metaRentab,
            'rentabRealizada' => $rentabRealizada,
            'realizado' => $realizado,
            'comissaoTotal' => $comissaoTotal,
            'totalFuncionarios' => count($linhas),
            'ranking' => $ranking,
            'oportunidades' => self::oportunidades($linhas, 5),
        ]);
    }

    /**
     * Achata o detalhe por categoria de várias pessoas numa lista única de
     * "faltam R$X para a próxima faixa", ordenada pela oportunidade mais perto de acontecer.
     *
     * @param array<int, array> $linhas
     * @return array<int, array{nome:string, categoria:string, falta:float, ganho:float, percentual:float, percentual_proximo:float}>
     */
    private static function oportunidades(array $linhas, int $limite): array
    {
        $achatado = [];
        foreach ($linhas as $l) {
            foreach ($l['detalhe_categorias'] as $d) {
                if ($d['proxima_faixa'] === null) {
                    continue;
                }
                $achatado[] = [
                    'nome' => $l['nome'],
                    'categoria' => $d['categoria'],
                    'falta' => $d['proxima_faixa']['falta'],
                    'ganho' => $d['proxima_faixa']['ganho'],
                    'percentual' => $d['percentual'],
                    'percentual_proximo' => $d['proxima_faixa']['percentual'],
                ];
            }
        }
        usort($achatado, static fn ($a, $b) => $a['falta'] <=> $b['falta']);

        return array_slice($achatado, 0, $limite);
    }

    private function pessoal(int $periodoId, array $periodo): void
    {
        $funcionario = Funcionario::find(Funcionario::idPorUsuario((int) Auth::id()));
        if ($funcionario === null) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $filialPrincipal = Funcionario::filialPrincipal((int) $funcionario['id']);
        if ($filialPrincipal === null) {
            $this->render('dashboard/sem_filial', []);
            return;
        }

        $linhas = ResumoCalculator::porFilial($filialPrincipal, $periodoId);
        usort($linhas, static fn ($a, $b) => $b['total'] <=> $a['total']);

        $minhaLinha = null;
        $posicao = null;
        foreach ($linhas as $i => $l) {
            if ($l['funcionario_id'] === (int) $funcionario['id']) {
                $minhaLinha = $l;
                $posicao = $i + 1;
                break;
            }
        }

        $metaIndividual = Meta::totalIndividual((int) $funcionario['id'], $periodoId);
        $realizadoIndividual = Venda::somaTotalFuncionario((int) $funcionario['id'], $periodoId);

        $this->render('dashboard/pessoal', [
            'periodo' => $periodo,
            'nome' => $funcionario['nome'],
            'linha' => $minhaLinha,
            'posicao' => $posicao,
            'totalNaFilial' => count($linhas),
            'metaIndividual' => $metaIndividual,
            'realizadoIndividual' => $realizadoIndividual,
        ]);
    }

}
