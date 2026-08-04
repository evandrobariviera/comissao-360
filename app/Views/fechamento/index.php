<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $linhas */
use App\Core\Auth;
use App\Core\Csrf;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$statusRotulo = ['aberto' => 'Aberto', 'fechado' => 'Fechado', 'aprovado' => 'Aprovado'];
$statusPill = ['aberto' => 'status-ativo', 'fechado' => 'status-inativo', 'aprovado' => 'status-ativo'];

$money = static fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$pct = static fn ($v) => number_format((float) $v, 1, ',', '.') . '%';
?>
<div class="toolbar">
  <div>
    <h2>Fechamento do mês</h2>
    <p class="subtitle">
      Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>
      <span class="pill <?= $statusPill[$periodo['status']] ?? '' ?>"><?= $statusRotulo[$periodo['status']] ?? $periodo['status'] ?></span>
    </p>
  </div>
</div>

<?php if (count($filiaisPermitidas) > 1): ?>
<form method="get" action="/fechamento" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
  <label for="filial_id">Filial</label>
  <select id="filial_id" name="filial_id" onchange="this.form.submit()">
    <?php foreach ($filiaisPermitidas as $f): ?>
      <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $filialId ? 'selected' : '' ?>><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php else: ?>
<p class="subtitle">Filial: <strong><?= htmlspecialchars($filiaisPermitidas[0]['nome'], ENT_QUOTES) ?></strong></p>
<?php endif; ?>

<?php if (empty($linhas)): ?>
<div class="card"><p>Nenhum funcionário tem esta filial como <b>principal</b> ainda. Funcionários vinculados a mais de uma filial (ex.: Frai 1 + Frai 2) entram no fechamento só da sua filial principal — ajustável em <a href="/usuarios">Usuários</a>.</p></div>
<?php else: ?>
<div class="scrollx" style="overflow-x:auto">
<table class="lista">
  <thead>
    <tr><th>Funcionário</th><th>Comissão-base</th><th>Pontuação 360</th><th>Multiplicador</th><th>Prêmio filial</th><th>Total do mês</th></tr>
  </thead>
  <tbody>
    <?php foreach ($linhas as $l): $p = $l['pontuacao']; ?>
    <tr>
      <td>
        <details>
          <summary style="cursor:pointer; font-weight:600"><?= htmlspecialchars($l['nome'], ENT_QUOTES) ?></summary>
          <div style="margin-top:.6rem; font-size:.82rem; color:var(--ink-soft)">
            <p style="margin:.2rem 0"><b>Comissão por categoria:</b></p>
            <table class="faixas" style="margin-bottom:.6rem">
              <thead><tr><th>Categoria</th><th>Valor vendido</th><th>Faixa</th><th>Comissão</th></tr></thead>
              <tbody>
              <?php foreach ($l['detalhe_categorias'] as $d): ?>
                <tr>
                  <td><?= htmlspecialchars($d['categoria'], ENT_QUOTES) ?></td>
                  <td><?= $money($d['valor']) ?></td>
                  <td><?= $pct($d['percentual']) ?></td>
                  <td><?= $money($d['comissao']) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <p style="margin:.2rem 0"><b>Pontuação 360:</b>
              Individual <?= number_format($p['pontos_individual'], 1, ',', '.') ?>/40 ·
              Filial <?= number_format($p['pontos_filial'], 1, ',', '.') ?>/30 ·
              Qualidade <?= number_format($p['pontos_qualidade'], 1, ',', '.') ?>/20 ·
              Equipe <?= number_format($p['pontos_equipe'], 1, ',', '.') ?>/10
            </p>
          </div>
        </details>
      </td>
      <td><?= $money($l['comissao_base']) ?></td>
      <td><?= number_format($p['pontuacao_total'], 1, ',', '.') ?> pts — <?= htmlspecialchars($p['nivel'], ENT_QUOTES) ?></td>
      <td><?= number_format($p['multiplicador_protegido'], 2, ',', '.') ?>×</td>
      <td><?= $money($l['premio_filial']) ?></td>
      <td><b><?= $money($l['total']) ?></b></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<div class="note" style="margin-top:1rem">
  Cálculo em tempo real a partir dos lançamentos e indicadores atuais — nada aqui está gravado ainda. Só a aprovação do fechamento grava o valor oficial do mês.
</div>

<?php if (Auth::papel() === Auth::PAPEL_ADMIN): ?>
  <?php if ($periodo['status'] !== 'aprovado'): ?>
  <form method="post" action="/fechamento/aprovar" style="margin-top:1.4rem"
        onsubmit="return confirm('Aprovar o fechamento de <?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?>? Isso grava o valor oficial de TODAS as filiais e trava novos lançamentos de venda/indicadores neste período.');">
    <?= Csrf::field() ?>
    <button type="submit" class="btn">Aprovar fechamento do mês (rede toda)</button>
  </form>
  <?php else: ?>
  <p class="subtitle" style="margin-top:1.4rem">Período aprovado — lançamentos travados.</p>
  <?php endif; ?>
<?php endif; ?>
<?php endif; ?>
