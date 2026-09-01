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
     * Realizado x meta de mix de uma categoria: barra do realizado numa escala compartilhada entre
     * categorias (pra magnitude ser comparável de linha pra linha) + marcador na posição da meta.
     * $maiorMelhor decide o lado bom (piso = quanto mais alto melhor, teto = quanto mais baixo).
     */
    public static function mixCategoriaRow(string $nome, float $realizadoPct, float $metaPct, bool $maiorMelhor, float $escalaMax): string
    {
        $atingimento = $maiorMelhor
            ? ($metaPct > 0 ? ($realizadoPct / $metaPct) * 100 : ($realizadoPct > 0 ? 100.0 : 0.0))
            : ($realizadoPct > 0 ? ($metaPct / $realizadoPct) * 100 : 100.0);
        [$classe, $cor, $tinta, $rotulo] = self::statusAtingimento($atingimento);

        $larguraReal = $escalaMax > 0 ? min(100.0, ($realizadoPct / $escalaMax) * 100) : 0.0;
        $posMeta = $escalaMax > 0 ? min(100.0, ($metaPct / $escalaMax) * 100) : 0.0;
        $simbolo = $maiorMelhor ? '≥' : '≤';

        $titulo = 'Realizado ' . self::pct($realizadoPct) . ' · Meta: ' . $simbolo . ' ' . self::pct($metaPct);

        $html = '<div class="mix-row" title="' . htmlspecialchars($titulo, ENT_QUOTES) . '">';
        $html .= '<div class="mix-topo">';
        $html .= '<span class="mix-nome">' . htmlspecialchars($nome, ENT_QUOTES) . '</span>';
        $html .= '<span class="mix-valores"><strong>' . self::pct($realizadoPct) . '</strong>'
            . ' <span class="mix-meta-txt">meta ' . $simbolo . ' ' . self::pct($metaPct) . '</span></span>';
        $html .= '<span class="status-tag ' . $classe . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</span>';
        $html .= '</div>';
        $html .= '<div class="mix-track" style="background:' . $tinta . '">';
        $html .= '<div class="mix-fill" style="width:' . $larguraReal . '%; background:' . $cor . '"></div>';
        $html .= '<div class="mix-marcador" style="left:' . $posMeta . '%"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Faixa piso→teto de um indicador individual (rentabilidade, desconto médio, ticket médio),
     * mostrando quantos pontos (%) da pontuação máxima do sub-pilar aquele valor já garante.
     * $maiorMelhor = false inverte a direção (ex.: desconto médio — valor baixo é bom).
     */
    public static function faixaGauge(string $label, ?float $valor, float $piso, float $teto, bool $maiorMelhor, string $sufixo = '%', string $prefixo = ''): string
    {
        if ($valor === null) {
            return '<div class="stat-tile"><span class="stat-label">' . htmlspecialchars($label, ENT_QUOTES) . '</span>'
                . '<span class="stat-value" style="color:var(--ink-faint)">—</span>'
                . '<span class="stat-sub">Ainda não lançado neste período</span></div>';
        }

        $amplitude = $teto - $piso;
        $pct = $amplitude > 0
            ? ($maiorMelhor ? ($valor - $piso) / $amplitude : ($teto - $valor) / $amplitude) * 100
            : 0.0;
        $pct = max(0.0, min(100.0, $pct));
        [, $cor, $tinta] = self::statusAtingimento($pct);

        $fmt = static fn (float $v) => $prefixo . number_format($v, 1, ',', '.') . $sufixo;
        $extremoRuim = $maiorMelhor ? $fmt($piso) : $fmt($teto);
        $extremoBom = $maiorMelhor ? $fmt($teto) : $fmt($piso);

        $html = '<div class="stat-tile"><span class="stat-label">' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
        $html .= '<span class="stat-value">' . htmlspecialchars($fmt($valor), ENT_QUOTES) . '</span>';
        $html .= '<div class="gauge-track" style="background:' . $tinta . '"><div class="gauge-fill" style="width:' . $pct . '%; background:' . $cor . '"></div></div>';
        $html .= '<div class="gauge-extremos"><span>' . htmlspecialchars($extremoRuim, ENT_QUOTES) . '</span><span>' . htmlspecialchars($extremoBom, ENT_QUOTES) . '</span></div>';
        $html .= '<span class="stat-sub">' . number_format($pct, 0) . '% da pontuação deste indicador</span>';

        return $html . '</div>';
    }

    /** @param array<string, bool> $checklist chaves c1_...c8_... => marcado ou não */
    public static function checklistList(array $checklist): string
    {
        $itens = [
            'c1_sem_falta_injustificada' => 'Sem falta injustificada',
            'c2_cumpriu_escala' => 'Cumpriu a escala',
            'c3_setor_organizado' => 'Setor organizado',
            'c4_ajudou_treinou_colega' => 'Ajudou/treinou colega',
            'c5_loja_bateu_meta_coletiva' => 'Loja bateu meta coletiva',
            'c6_venda_5_catalogos' => 'Venda de 5 catálogos',
            'c7_venda_30_a_vencer' => 'Venda de 30 a vencer',
            'c8_venda_30_linha_propria' => 'Venda de 30 linha própria',
        ];

        $html = '<ul class="checklist-status">';
        foreach ($itens as $chave => $rotulo) {
            $marcado = !empty($checklist[$chave]);
            $html .= '<li class="' . ($marcado ? 'ok' : 'pendente') . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</li>';
        }

        return $html . '</ul>';
    }

    /**
     * Gráfico de ritmo diário: trajetória acumulada (meta ideal x realizado) ao longo do mês,
     * ignorando domingo no cálculo da meta ideal (ver RitmoDiarioCalculator).
     *
     * @param array{
     *   meta_venda: float, meta_hoje: float, meta_restante: float, dias_uteis_restantes: int,
     *   pontos: array<int, array{dia:int, domingo:bool, hoje:bool, ideal_acumulado:float, realizado_acumulado: ?float}>
     * } $ritmo
     */
    public static function ritmoDiarioChart(array $ritmo): string
    {
        $pontos = $ritmo['pontos'];
        $n = count($pontos);
        if ($n === 0) {
            return '<p class="subtitle" style="margin:0">Sem dados de período pra montar o ritmo diário.</p>';
        }

        $larguraTotal = 640;
        $alturaTotal = 200;
        $padEsq = 8;
        $padDir = 8;
        $padTopo = 14;
        $padBase = 24;
        $plotW = $larguraTotal - $padEsq - $padDir;
        $plotH = $alturaTotal - $padTopo - $padBase;

        $maxY = max($ritmo['meta_venda'], 1.0);
        foreach ($pontos as $p) {
            if ($p['realizado_acumulado'] !== null) {
                $maxY = max($maxY, $p['realizado_acumulado']);
            }
        }

        $xFor = static fn (int $dia) => $n > 1 ? $padEsq + ($dia - 1) / ($n - 1) * $plotW : $padEsq + $plotW / 2;
        $yFor = static fn (float $v) => $padTopo + $plotH - min(1.0, $v / $maxY) * $plotH;
        $baseY = $yFor(0.0);
        $larguraDia = $n > 1 ? $plotW / ($n - 1) : $plotW;

        // Faixas de fundo nos domingos — só pra explicar visualmente por que a linha ideal "descansa" ali.
        $bandas = '';
        foreach ($pontos as $p) {
            if (!$p['domingo']) {
                continue;
            }
            $x = $xFor($p['dia']) - $larguraDia / 2;
            $bandas .= '<rect x="' . $x . '" y="' . $padTopo . '" width="' . $larguraDia . '" height="' . $plotH . '" fill="var(--bg)" />';
        }

        $ultimoReal = null;
        foreach ($pontos as $p) {
            if ($p['realizado_acumulado'] !== null) {
                $ultimoReal = $p;
            }
        }

        // Status da linha "realizado": compara o último ponto real com o ideal do mesmo dia.
        // Calculado ANTES dos marcadores pra colorir os círculos com a mesma cor da linha.
        $cor = 'var(--ink-soft)';
        if ($ultimoReal !== null) {
            $razao = $ultimoReal['ideal_acumulado'] > 0 ? $ultimoReal['realizado_acumulado'] / $ultimoReal['ideal_acumulado'] * 100 : 100.0;
            [, $cor] = self::statusAtingimento($razao);
        }

        $pontosIdeal = [];
        $pontosReal = [];
        $marcadoresReal = '';
        $xHoje = null;
        foreach ($pontos as $p) {
            $x = $xFor($p['dia']);
            $pontosIdeal[] = $x . ',' . $yFor($p['ideal_acumulado']);
            if ($p['hoje']) {
                $xHoje = $x;
            }
            if ($p['realizado_acumulado'] !== null) {
                $yReal = $yFor($p['realizado_acumulado']);
                $pontosReal[] = $x . ',' . $yReal;
                $titulo = 'Dia ' . $p['dia'] . ': ' . self::money($p['realizado_acumulado']) . ' acumulado';
                $marcadoresReal .= '<circle cx="' . $x . '" cy="' . $yReal . '" r="3" class="ritmo-marcador" stroke="' . $cor . '"><title>'
                    . htmlspecialchars($titulo, ENT_QUOTES) . '</title></circle>';
            }
        }

        $areaReal = '';
        if (count($pontosReal) > 1) {
            $primeiroX = explode(',', $pontosReal[0])[0];
            $ultimoX = explode(',', $pontosReal[count($pontosReal) - 1])[0];
            $areaReal = '<polygon points="' . $primeiroX . ',' . $baseY . ' ' . implode(' ', $pontosReal) . ' ' . $ultimoX . ',' . $baseY
                . '" fill="' . $cor . '" opacity="0.12" />';
        }

        $linhaHoje = $xHoje !== null
            ? '<line x1="' . $xHoje . '" y1="' . $padTopo . '" x2="' . $xHoje . '" y2="' . $baseY . '" stroke="var(--ink-faint)" stroke-width="1" stroke-dasharray="2 3" />'
            : '';

        $primeiroDia = (int) $pontos[0]['dia'];
        $ultimoDia = (int) $pontos[$n - 1]['dia'];
        $meioDia = $pontos[intdiv($n, 2)]['dia'];

        $html = '<div class="legend">'
            . '<span><span class="sw" style="background:var(--ink-faint)"></span>Meta ideal (sem domingo)</span>'
            . '<span><span class="sw" style="background:' . $cor . '"></span>Realizado acumulado</span>'
            . '</div>';

        $html .= '<svg viewBox="0 0 ' . $larguraTotal . ' ' . $alturaTotal . '" class="ritmo-chart" preserveAspectRatio="none">';
        $html .= $bandas;
        $html .= $linhaHoje;
        $html .= $areaReal;
        $html .= '<polyline points="' . implode(' ', $pontosIdeal) . '" fill="none" stroke="var(--ink-faint)" stroke-width="2" stroke-dasharray="5 4" stroke-linecap="round" />';
        if (count($pontosReal) > 1) {
            $html .= '<polyline points="' . implode(' ', $pontosReal) . '" fill="none" stroke="' . $cor . '" stroke-width="2" stroke-linecap="round" />';
        }
        $html .= $marcadoresReal;
        $html .= '<text x="' . $xFor($primeiroDia) . '" y="' . ($alturaTotal - 6) . '" class="ritmo-eixo">' . $primeiroDia . '</text>';
        $html .= '<text x="' . $xFor($meioDia) . '" y="' . ($alturaTotal - 6) . '" class="ritmo-eixo" text-anchor="middle">' . $meioDia . '</text>';
        $html .= '<text x="' . $xFor($ultimoDia) . '" y="' . ($alturaTotal - 6) . '" class="ritmo-eixo" text-anchor="end">' . $ultimoDia . '</text>';
        $html .= '</svg>';

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
