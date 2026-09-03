<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Filial;
use App\Models\Periodo;
use App\Models\Relatorio;

/**
 * Seção de relatórios comparativos (mês a mês). Visível para admin e gerente — e o gerente
 * vê a rede inteira aqui (diferente das telas de lançamento, que são escopadas à filial dele).
 */
final class RelatorioController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE);

        $catalogo = Relatorio::catalogo();
        $periodos = array_reverse(Periodo::listar()); // antigo -> recente
        $filiais = Filial::ativas();

        $rel = (string) $this->input('rel', '');
        if (!isset($catalogo[$rel])) {
            $rel = array_key_first($catalogo);
        }
        $meta = $catalogo[$rel];
        $filtros = $meta['filtros'];

        $intervalo = $this->resolverIntervalo($periodos);
        $filialId = $this->resolverFilial($filiais, $filtros);
        $metrica = $this->resolverMetrica($filtros);

        $dados = $intervalo === []
            ? null
            : Relatorio::gerar($rel, $intervalo, $filialId, $metrica);

        if ($dados !== null && $this->input('csv') !== null) {
            $this->enviarCsv($rel, $dados);
            return;
        }

        $this->render('relatorios/index', [
            'catalogo' => $catalogo,
            'rel' => $rel,
            'meta' => $meta,
            'filtros' => $filtros,
            'periodos' => $periodos,
            'filiais' => $filiais,
            'filialId' => $filialId,
            'metrica' => $metrica,
            'metricasComissao' => Relatorio::metricasComissao(),
            'metricasIndicador' => Relatorio::metricasIndicador(),
            'de' => $intervalo[0]['id'] ?? null,
            'ate' => $intervalo === [] ? null : $intervalo[count($intervalo) - 1]['id'],
            'dados' => $dados,
        ]);
    }

    /**
     * Períodos entre `de` e `ate` (ids), inclusivos, na ordem antigo -> recente.
     * Sem seleção válida: todos os períodos.
     * @param list<array<string,mixed>> $periodos já ordenados antigo -> recente
     * @return list<array<string,mixed>>
     */
    private function resolverIntervalo(array $periodos): array
    {
        if (empty($periodos)) {
            return [];
        }

        $ordem = [];
        foreach ($periodos as $i => $p) {
            $ordem[(int) $p['id']] = $i;
        }

        $de = $ordem[(int) $this->input('de', 0)] ?? 0;
        $ate = $ordem[(int) $this->input('ate', 0)] ?? (count($periodos) - 1);
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        return array_slice($periodos, $de, $ate - $de + 1);
    }

    /**
     * @param list<array<string,mixed>> $filiais
     * @param list<string> $filtros
     */
    private function resolverFilial(array $filiais, array $filtros): int
    {
        $ids = array_map(static fn (array $f): int => (int) $f['id'], $filiais);
        $pedida = (int) $this->input('filial', -1);

        if (in_array('filial_obrigatoria', $filtros, true)) {
            return in_array($pedida, $ids, true) ? $pedida : (int) ($ids[0] ?? 0);
        }
        if (in_array('filial', $filtros, true)) {
            return in_array($pedida, $ids, true) ? $pedida : 0; // 0 = rede
        }

        return 0;
    }

    /** @param list<string> $filtros */
    private function resolverMetrica(array $filtros): string
    {
        $pedida = (string) $this->input('metrica', '');

        if (in_array('metrica_comissao', $filtros, true)) {
            return array_key_exists($pedida, Relatorio::metricasComissao()) ? $pedida : 'total';
        }
        if (in_array('metrica_indicador', $filtros, true)) {
            return array_key_exists($pedida, Relatorio::metricasIndicador()) ? $pedida : 'ticket_medio';
        }

        return '';
    }

    private function enviarCsv(string $rel, array $dados): void
    {
        $nome = 'relatorio_' . $rel . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM p/ Excel

        $cab = [$dados['colDimensao']];
        foreach ($dados['periodos'] as $p) {
            $cab[] = $p['rotulo'];
        }
        fputcsv($out, $cab, ';', '"', '\\');

        $fmt = static function (?float $v) use ($dados): string {
            if ($v === null) {
                return '';
            }
            return match ($dados['formato']) {
                'money', 'decimal' => number_format($v, 2, ',', ''),
                'pct' => number_format($v, 1, ',', '') . '%',
                default => (string) (int) round($v),
            };
        };

        foreach ($dados['linhas'] as $l) {
            $linha = [$l['rotulo']];
            foreach ($dados['periodos'] as $p) {
                $linha[] = $fmt($l['valores'][$p['id']] ?? null);
            }
            fputcsv($out, $linha, ';', '"', '\\');
        }

        if ($dados['total'] !== null) {
            $linha = [$dados['total']['rotulo']];
            foreach ($dados['periodos'] as $p) {
                $linha[] = $fmt($dados['total']['valores'][$p['id']] ?? null);
            }
            fputcsv($out, $linha, ';', '"', '\\');
        }

        fclose($out);
    }
}
