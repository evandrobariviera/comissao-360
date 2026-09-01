<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $fechamento */
/** @var array<int, array> $statusPorFilial */
/** @var array $linhas */
use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Usuario;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$estaAprovado = $fechamento['status'] === 'aprovado';

$money = static fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$pct = static fn ($v) => number_format((float) $v, 1, ',', '.') . '%';

$nomeFilialAtual = '';
foreach ($filiaisPermitidas as $f) {
    if ((int) $f['id'] === $filialId) {
        $nomeFilialAtual = $f['nome'];
        break;
    }
}
?>
<div class="toolbar">
  <div>
    <h2>Fechamento do mês</h2>
    <p class="subtitle">
      Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>.
      Cada filial fecha (e pode ser reaberta) de forma independente das outras.
    </p>
  </div>
</div>

<?php if (count($filiaisPermitidas) > 1): ?>
<div class="scrollx" style="margin-bottom:1.2rem">
  <table class="lista">
    <thead><tr><th>Filial</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($filiaisPermitidas as $f): $fid = (int) $f['id']; $st = $statusPorFilial[$fid]['status'] ?? 'aberto'; ?>
      <tr>
        <td><a href="/fechamento?filial_id=<?= $fid ?>"><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></a></td>
        <td><span class="pill <?= $st === 'aprovado' ? 'status-ativo' : '' ?>"><?= $st === 'aprovado' ? 'Aprovado' : 'Aberto' ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

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

<p class="subtitle">
  <?= htmlspecialchars($nomeFilialAtual, ENT_QUOTES) ?> —
  <span class="pill <?= $estaAprovado ? 'status-ativo' : '' ?>"><?= $estaAprovado ? 'Aprovado' : 'Aberto' ?></span>
  <?php if ($estaAprovado && !empty($fechamento['aprovado_em'])): $aprovador = Usuario::find((int) $fechamento['aprovado_por']); ?>
    <span class="subtitle" style="margin:0">em <?= (new DateTime($fechamento['aprovado_em']))->format('d/m/Y \à\s H:i') ?><?= $aprovador !== null ? ' por ' . htmlspecialchars($aprovador['email'], ENT_QUOTES) : '' ?></span>
  <?php endif; ?>
</p>

<?php if ($estaAprovado): ?>
<div class="callout dica"><span class="callout-label">Fechado</span>O fechamento desta filial já foi aprovado — os valores abaixo são o que foi gravado como oficial. Vendas, Metas e Indicadores desta filial ficaram travados até reabrir.</div>
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
  <?= $estaAprovado
      ? 'Valores gravados na aprovação — não recalculam mais sozinhos. Reabra o fechamento pra corrigir algo e aprovar de novo depois.'
      : 'Cálculo em tempo real a partir dos lançamentos e indicadores atuais — nada aqui está gravado ainda. Só a aprovação do fechamento grava o valor oficial do mês.' ?>
</div>

<?php if (!$estaAprovado && in_array(Auth::papel(), [Auth::PAPEL_ADMIN, Auth::PAPEL_GERENTE], true)): ?>
  <form method="post" action="/fechamento/aprovar" style="margin-top:1.4rem"
        onsubmit="return confirm('Aprovar o fechamento de <?= htmlspecialchars($nomeFilialAtual, ENT_QUOTES) ?> em <?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?>? Isso grava o valor oficial desta filial e trava novos lançamentos de venda/metas/indicadores dela neste período. Só um admin consegue reabrir depois.');">
    <?= Csrf::field() ?>
    <input type="hidden" name="filial_id" value="<?= $filialId ?>">
    <button type="submit" class="btn">Aprovar fechamento de <?= htmlspecialchars($nomeFilialAtual, ENT_QUOTES) ?></button>
  </form>
<?php elseif ($estaAprovado && Auth::papel() === Auth::PAPEL_ADMIN): ?>
  <form method="post" action="/fechamento/reabrir" style="margin-top:1.4rem"
        onsubmit="return confirm('Reabrir o fechamento de <?= htmlspecialchars($nomeFilialAtual, ENT_QUOTES) ?> em <?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?>? Os lançamentos voltam a ficar editáveis pra essa filial até aprovar de novo.');">
    <?= Csrf::field() ?>
    <input type="hidden" name="filial_id" value="<?= $filialId ?>">
    <button type="submit" class="btn secundario">Reabrir fechamento de <?= htmlspecialchars($nomeFilialAtual, ENT_QUOTES) ?></button>
  </form>
<?php elseif ($estaAprovado): ?>
  <p class="subtitle" style="margin-top:1.4rem">Fechamento aprovado — só um administrador pode reabrir.</p>
<?php endif; ?>
<?php endif; ?>
