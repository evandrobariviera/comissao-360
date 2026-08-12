<?php

declare(strict_types=1);

namespace App\Core;

/** Componentes visuais reaproveitados pelos 3 dashboards — só marcação, sem regra de negócio. */
final class Viz
{
    public static function money(float $v): string
    {
        return 'R$ ' . number_format($v, 2, ',', '.');
    }

    public static function pct(float $v, int $casas = 1): string
    {
        return number_format($v, $casas, ',', '.') . '%';
    }

    public static function statTile(string $label, string $value, ?string $sub = null): string
    {
        $html = '<div class="stat-tile"><span class="stat-label">' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
        $html .= '<span class="stat-value">' . htmlspecialchars($value, ENT_QUOTES) . '</span>';
        if ($sub !== null) {
            $html .= '<span class="stat-sub">' . htmlspecialchars($sub, ENT_QUOTES) . '</span>';
        }

        return $html . '</div>';
    }

    /** Stat tile de percentual com barrinha de comparação embutida (realizado vs. meta), tipo rentabilidade. */
    public static function statTilePct(string $label, float $realizado, float $meta): string
    {
        $atingimento = $meta > 0 ? ($realizado / $meta) * 100 : 0.0;
        [, $cor, $tinta] = self::statusAtingimento($atingimento);
        $largura = min(100.0, max(0.0, $atingimento));
        $falta = max(0.0, $meta - $realizado);

        $sub = 'meta ' . self::pct($meta) . ($falta > 0.05
            ? ' · faltam ' . number_format($falta, 1, ',', '.') . ' p.p.'
            : ' · meta batida');

        $html = '<div class="stat-tile"><span class="stat-label">' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
        $html .= '<span class="stat-value">' . htmlspecialchars(self::pct($realizado), ENT_QUOTES) . '</span>';
        $html .= '<div class="stat-bar-track" style="background:' . $tinta . '"><div class="stat-bar-fill" style="width:' . $largura . '%; background:' . $cor . '"></div></div>';
        $html .= '<span class="stat-sub">' . htmlspecialchars($sub, ENT_QUOTES) . '</span>';

        return $html . '</div>';
    }

    /** @return array{0:string,1:string,2:string,3:string} [classe, cor, tinta, rótulo] */
    private static function statusAtingimento(float $atingimentoPct): array
    {
        if ($atingimentoPct >= 100) {
            return ['status-good', 'var(--good)', 'var(--good-tint)', 'Bateu a meta'];
        }
        if ($atingimentoPct >= 80) {
            return ['status-warn', 'var(--warn)', 'var(--warn-tint)', 'Quase lá'];
        }

        return ['status-bad', 'var(--bad)', 'var(--bad-tint)', 'Abaixo da meta'];
    }

    /** Meter: uma razão (realizado/meta) contra o limite de 100%. */
    public static function meterRow(string $nome, float $realizado, float $meta): string
    {
        $atingimento = $meta > 0 ? ($realizado / $meta) * 100 : 0.0;
        [$classe, $cor, $tinta, $rotulo] = self::statusAtingimento($atingimento);
        $larguraVisual = min(100.0, max(0.0, $atingimento));

        $html = '<div class="meter-row" title="' . htmlspecialchars(self::money($realizado) . ' de ' . self::money($meta), ENT_QUOTES) . '">';
        $html .= '<div class="meter-nome"><span>' . htmlspecialchars($nome, ENT_QUOTES) . '</span>';
        $html .= '<span class="status-tag ' . $classe . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</span></div>';
        $html .= '<div class="meter-track" style="background:' . $tinta . '"><div class="meter-fill" style="width:' . $larguraVisual . '%; background:' . $cor . '"></div></div>';
        $html .= '<div class="meter-pct">' . self::pct($atingimento) . '</div>';

        return $html . '</div>';
    }

    /** @param array<int, array{nome:string, valor:float, formatado:string}> $linhas já ordenadas (maior primeiro) */
    public static function ranking(array $linhas): string
    {
        if (empty($linhas)) {
            return '<p class="subtitle">Sem dados ainda.</p>';
        }
        $max = max(array_column($linhas, 'valor')) ?: 1.0;

        $html = '<div class="rank-list">';
        foreach ($linhas as $l) {
            $largura = $max > 0 ? min(100.0, ($l['valor'] / $max) * 100) : 0.0;
            $html .= '<div class="rank-row">';
            $html .= '<span class="rank-name">' . htmlspecialchars($l['nome'], ENT_QUOTES) . '</span>';
            $html .= '<div class="rank-track"><div class="rank-fill" style="width:' . $largura . '%"></div></div>';
            $html .= '<span class="rank-value">' . htmlspecialchars($l['formatado'], ENT_QUOTES) . '</span>';
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    /** @param array<int, array{nome:string, count:int}> $linhas em ordem fixa (pior → melhor nível) */
    public static function distribuicaoNiveis(array $linhas): string
    {
        $max = max(array_column($linhas, 'count')) ?: 1;

        $html = '';
        foreach ($linhas as $l) {
            $largura = $max > 0 ? min(100.0, ($l['count'] / $max) * 100) : 0.0;
            $html .= '<div class="tier-row">';
            $html .= '<span>' . htmlspecialchars($l['nome'], ENT_QUOTES) . '</span>';
            $html .= '<div class="tier-track"><div class="tier-fill" style="width:' . $largura . '%"></div></div>';
            $html .= '<span class="tier-count">' . $l['count'] . '</span>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function legendaPilares(): string
    {
        $itens = [
            ['var(--chart-individual)', 'Individual'],
            ['var(--chart-filial)', 'Filial'],
            ['var(--chart-qualidade)', 'Qualidade'],
            ['var(--chart-equipe)', 'Equipe'],
        ];
        $html = '<div class="legend">';
        foreach ($itens as [$cor, $rotulo]) {
            $html .= '<span><span class="sw" style="background:' . $cor . '"></span>' . $rotulo . '</span>';
        }

        return $html . '</div>';
    }

    /** @param array{pontos_individual:float,pontos_filial:float,pontos_qualidade:float,pontos_equipe:float,pontuacao_total:float,nivel:string} $p */
    public static function pilaresBar(array $p): string
    {
        $segs = [
            ['var(--chart-individual)', $p['pontos_individual']],
            ['var(--chart-filial)', $p['pontos_filial']],
            ['var(--chart-qualidade)', $p['pontos_qualidade']],
            ['var(--chart-equipe)', $p['pontos_equipe']],
        ];
        $titulo = sprintf(
            'Individual %.1f/40 · Filial %.1f/30 · Qualidade %.1f/20 · Equipe %.1f/10',
            $p['pontos_individual'],
            $p['pontos_filial'],
            $p['pontos_qualidade'],
            $p['pontos_equipe']
        );

        $html = '<div class="pilares-bar" title="' . htmlspecialchars($titulo, ENT_QUOTES) . '">';
        foreach ($segs as [$cor, $valor]) {
            $html .= '<div class="seg" style="width:' . $valor . '%; background:' . $cor . '"></div>';
        }

        return $html . '</div>';
    }

    /**
     * "Faltam R$X para a próxima faixa" — para o dashboard do próprio funcionário.
     * @param array<int, array{categoria:string, valor:float, percentual:float, proxima_faixa: null|array{falta:float, percentual:float, ganho:float}}> $detalhe
     */
    public static function oportunidadesFuncionario(array $detalhe): string
    {
        $linhas = array_values(array_filter($detalhe, static fn ($d) => $d['proxima_faixa'] !== null));
        if (empty($linhas)) {
            return '<p class="subtitle" style="margin:0">Todas as categorias já estão na faixa máxima — sem próximo degrau a perseguir agora.</p>';
        }
        usort($linhas, static fn ($a, $b) => $a['proxima_faixa']['falta'] <=> $b['proxima_faixa']['falta']);

        $html = '';
        foreach ($linhas as $d) {
            $prox = $d['proxima_faixa'];
            $limite = $d['valor'] + $prox['falta'];
            $progresso = $limite > 0 ? min(100.0, ($d['valor'] / $limite) * 100) : 0.0;

            $html .= '<div class="oport-row"><div class="oport-topo">';
            $html .= '<span class="oport-nome">' . htmlspecialchars($d['categoria'], ENT_QUOTES) . '</span>';
            $html .= '<span class="oport-ganho">+' . self::money($prox['ganho']) . '</span>';
            $html .= '</div><p class="oport-texto">Faltam <strong>' . self::money($prox['falta']) . '</strong> para pular de '
                . self::pct($d['percentual'], 0) . ' para ' . self::pct($prox['percentual'], 0) . '</p>';
            $html .= '<div class="oport-track"><div class="oport-fill" style="width:' . $progresso . '%"></div></div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Mesma ideia, agregada por várias pessoas — para o dashboard de gerente/rede.
     * @param array<int, array{nome:string, categoria:string, falta:float, ganho:float, percentual:float, percentual_proximo:float}> $linhas já ordenadas
     */
    public static function oportunidadesEquipe(array $linhas): string
    {
        if (empty($linhas)) {
            return '<p class="subtitle" style="margin:0">Nenhuma oportunidade de próxima faixa no momento.</p>';
        }

        $html = '';
        foreach ($linhas as $l) {
            $html .= '<div class="oport-row"><div class="oport-topo">';
            $html .= '<span class="oport-nome">' . htmlspecialchars($l['categoria'], ENT_QUOTES)
                . ' <span class="oport-quem">— ' . htmlspecialchars($l['nome'], ENT_QUOTES) . '</span></span>';
            $html .= '<span class="oport-ganho">+' . self::money($l['ganho']) . '</span>';
            $html .= '</div><p class="oport-texto">Faltam <strong>' . self::money($l['falta']) . '</strong> para pular de '
                . self::pct($l['percentual'], 0) . ' para ' . self::pct($l['percentual_proximo'], 0) . '</p>';
            $html .= '</div>';
        }

        return $html;
    }
}
