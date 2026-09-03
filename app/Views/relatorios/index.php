<?php
/**
 * Seção de relatórios comparativos — filtros + tabela genérica (qualquer relatório do catálogo).
 *
 * @var array      $catalogo
 * @var string     $rel
 * @var array      $meta
 * @var array      $filtros
 * @var array      $periodos          antigo -> recente
 * @var array      $filiais
 * @var int        $filialId
 * @var string     $metrica
 * @var array      $metricasComissao  chave => rótulo
 * @var array      $metricasIndicador chave => rótulo
 * @var int|null   $de
 * @var int|null   $ate
 * @var array|null $dados
 */
use App\Core\Viz;

$nomesMes = [1=>'jan',2=>'fev',3=>'mar',4=>'abr',5=>'mai',6=>'jun',7=>'jul',8=>'ago',9=>'set',10=>'out',11=>'nov',12=>'dez'];
$rotuloPeriodo = static fn (array $p): string => $nomesMes[(int) $p['mes']] . '/' . $p['ano'];

// Agrupa o catálogo por família para o <select>.
$porFamilia = [];
foreach ($catalogo as $chave => $m) {
    $porFamilia[$m['familia']][$chave] = $m['titulo'];
}

$temFiltroFilial = in_array('filial', $filtros, true) || in_array('filial_obrigatoria', $filtros, true);
$permiteRede = in_array('filial', $filtros, true);

$fmtValor = static function (?float $v, string $formato): string {
    if ($v === null) {
        return '<span style="color:var(--ink-faint)">—</span>';
    }
    return match ($formato) {
        'money' => Viz::money($v),
        'pct' => number_format($v, 1, ',', '.') . '%',
        'decimal' => number_format($v, 1, ',', '.'),
        default => number_format($v, 0, ',', '.'),
    };
};

$fmtDelta = static function (?float $d, string $formato, string $direcao): string {
    if ($d === null) {
        return '<span style="color:var(--ink-faint)">—</span>';
    }
    if (abs($d) < 0.005) {
        return '<span style="color:var(--ink-faint)">·</span>';
    }
    $bom = $direcao === 'menor_melhor' ? $d < 0 : $d > 0;
    $cor = $bom ? 'var(--good)' : 'var(--bad)';
    $sinal = $d > 0 ? '+' : '−';
    $abs = abs($d);
    $txt = match ($formato) {
        'money' => $sinal . Viz::money($abs),
        'pct' => $sinal . number_format($abs, 1, ',', '.') . ' p.p.',
        'decimal' => $sinal . number_format($abs, 1, ',', '.'),
        default => $sinal . number_format($abs, 0, ',', '.'),
    };
    return '<span style="color:' . $cor . '; font-weight:600">' . $txt . '</span>';
};

// Δ de uma série (na ordem dos períodos): [mês a mês (último vs penúltimo), período (último vs primeiro)]
$deltas = static function (array $valores) {
    $seq = array_values($valores);
    $n = count($seq);
    $ultimo = $seq[$n - 1] ?? null;
    $penultimo = $n >= 2 ? $seq[$n - 2] : null;
    $primeiro = $seq[0] ?? null;
    $dMes = ($ultimo !== null && $penultimo !== null) ? $ultimo - $penultimo : null;
    $dPer = ($n >= 2 && $ultimo !== null && $primeiro !== null) ? $ultimo - $primeiro : null;
    return [$dMes, $dPer];
};

// Query string p/ o link de CSV (mantém filtros atuais).
$qs = http_build_query(array_filter([
    'rel' => $rel,
    'de' => $de,
    'ate' => $ate,
    'filial' => $temFiltroFilial ? $filialId : null,
    'metrica' => $metrica !== '' ? $metrica : null,
    'csv' => 1,
]));
?>
<div class="toolbar">
  <div>
    <h2>Relatórios</h2>
    <p class="subtitle" style="margin:0">Comparativos mês a mês — rede, filial, categoria e funcionário.</p>
  </div>
  <?php if ($dados !== null && !empty($dados['linhas'])): ?>
    <a class="btn secundario" href="/relatorios?<?= htmlspecialchars($qs, ENT_QUOTES) ?>">Exportar CSV</a>
  <?php endif; ?>
</div>

<form method="get" action="/relatorios" class="card" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Relatório</label><br>
    <select name="rel" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); min-width:18rem;">
      <?php foreach ($porFamilia as $familia => $itens): ?>
        <optgroup label="<?= htmlspecialchars($familia, ENT_QUOTES) ?>">
          <?php foreach ($itens as $chave => $titulo): ?>
            <option value="<?= htmlspecialchars($chave, ENT_QUOTES) ?>" <?= $chave === $rel ? 'selected' : '' ?>><?= htmlspecialchars($titulo, ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </optgroup>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">De</label><br>
    <select name="de" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm);">
      <?php foreach ($periodos as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $de ? 'selected' : '' ?>><?= $rotuloPeriodo($p) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Até</label><br>
    <select name="ate" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm);">
      <?php foreach ($periodos as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $ate ? 'selected' : '' ?>><?= $rotuloPeriodo($p) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if ($temFiltroFilial): ?>
  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Filial</label><br>
    <select name="filial" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm);">
      <?php if ($permiteRede): ?><option value="0" <?= $filialId === 0 ? 'selected' : '' ?>>Rede (todas)</option><?php endif; ?>
      <?php foreach ($filiais as $f): ?>
        <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $filialId ? 'selected' : '' ?>><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <?php if (in_array('metrica_comissao', $filtros, true)): ?>
  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Métrica</label><br>
    <select name="metrica" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm);">
      <?php foreach ($metricasComissao as $chave => $rotulo): ?>
        <option value="<?= htmlspecialchars($chave, ENT_QUOTES) ?>" <?= $chave === $metrica ? 'selected' : '' ?>><?= htmlspecialchars($rotulo, ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <?php if (in_array('metrica_indicador', $filtros, true)): ?>
  <div>
    <label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Métrica</label><br>
    <select name="metrica" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm);">
      <?php foreach ($metricasIndicador as $chave => $rotulo): ?>
        <option value="<?= htmlspecialchars($chave, ENT_QUOTES) ?>" <?= $chave === $metrica ? 'selected' : '' ?>><?= htmlspecialchars($rotulo, ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <noscript><button type="submit" class="btn pequeno">Aplicar</button></noscript>
</form>

<?php if ($dados === null): ?>
  <div class="card"><p class="subtitle" style="margin:0">Nenhum período cadastrado ainda.</p></div>
<?php else: ?>

<div class="secao">
  <h3><?= htmlspecialchars($dados['titulo'], ENT_QUOTES) ?></h3>
  <?php if (!empty($dados['nota'])): ?><p class="secao-sub"><?= htmlspecialchars($dados['nota'], ENT_QUOTES) ?></p><?php endif; ?>

  <?php $multi = count($dados['periodos']) > 1; ?>
  <?php if (empty($dados['linhas'])): ?>
    <div class="card"><p class="subtitle" style="margin:0">Sem dados para o período selecionado.</p></div>
  <?php else: ?>
  <div class="scrollx">
    <table class="lista">
      <thead>
        <tr>
          <th><?= htmlspecialchars($dados['colDimensao'], ENT_QUOTES) ?></th>
          <?php foreach ($dados['periodos'] as $p): ?>
            <th style="text-align:right"><?= htmlspecialchars($p['rotulo'], ENT_QUOTES) ?></th>
          <?php endforeach; ?>
          <?php if ($multi): ?>
            <th style="text-align:right">Δ mês</th>
            <th style="text-align:right">Δ período</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dados['linhas'] as $l): [$dMes, $dPer] = $deltas($l['valores']); ?>
        <tr>
          <td style="white-space:nowrap"><?= htmlspecialchars($l['rotulo'], ENT_QUOTES) ?></td>
          <?php foreach ($dados['periodos'] as $p): ?>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtValor($l['valores'][$p['id']] ?? null, $dados['formato']) ?></td>
          <?php endforeach; ?>
          <?php if ($multi): ?>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtDelta($dMes, $dados['formato'], $dados['direcao']) ?></td>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtDelta($dPer, $dados['formato'], $dados['direcao']) ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if ($dados['total'] !== null): [$dMes, $dPer] = $deltas($dados['total']['valores']); ?>
      <tfoot>
        <tr style="border-top:2px solid var(--line); font-weight:700;">
          <td style="white-space:nowrap"><?= htmlspecialchars($dados['total']['rotulo'], ENT_QUOTES) ?></td>
          <?php foreach ($dados['periodos'] as $p): ?>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtValor($dados['total']['valores'][$p['id']] ?? null, $dados['formato']) ?></td>
          <?php endforeach; ?>
          <?php if ($multi): ?>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtDelta($dMes, $dados['formato'], $dados['direcao']) ?></td>
            <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $fmtDelta($dPer, $dados['formato'], $dados['direcao']) ?></td>
          <?php endif; ?>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>
