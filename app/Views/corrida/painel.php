<?php
/**
 * Painel admin da Corrida dos Campeões.
 *
 * @var array|null $edicao
 * @var array      $edicoes
 * @var array      $grupos
 * @var array      $funcionarios list de ['id','nome','filial_id']
 * @var array      $grade        [funcionario_id][grupo_id] => valor
 * @var array      $gruposRank
 * @var array      $rankingGeral
 * @var string     $abaGeral
 * @var array      $nomesFiliais
 * @var float      $totalPremiado
 */
use App\Core\Csrf;
use App\Core\Viz;
use App\Services\CorridaCalculator;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$fmt = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$dataBr = static fn (string $d) => $d ? implode('/', array_reverse(explode('-', $d))) : '';
$hoje = date('Y-m-d');
$anoPadrao = (int) date('Y');
$aberta = $edicao !== null && $edicao['status'] === 'aberta';
$fechada = $edicao !== null && $edicao['status'] === 'fechada';
?>
<div class="toolbar">
  <div>
    <h2>Corrida dos Campeões</h2>
    <p class="subtitle" style="margin:0">Bonificação trimestral. Todos os funcionários competem juntos, sem separar por filial.</p>
  </div>
  <?php if (!empty($edicoes)): ?>
  <form method="get" action="/corrida">
    <select name="edicao" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-weight:600;">
      <?php foreach ($edicoes as $e): ?>
        <option value="<?= (int) $e['id'] ?>" <?= $edicao !== null && (int) $e['id'] === (int) $edicao['id'] ? 'selected' : '' ?>>
          <?= (int) $e['trimestre'] ?>º tri / <?= (int) $e['ano'] ?><?= $e['nome'] ? ' — ' . htmlspecialchars($e['nome'], ENT_QUOTES) : '' ?> · <?= $e['status'] === 'fechada' ? 'fechada' : 'aberta' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<details style="margin-bottom:1.4rem;"<?= $edicao === null ? ' open' : '' ?>>
  <summary style="cursor:pointer; font-weight:600; color:var(--primary-ink);">+ Nova edição</summary>
  <form class="form-padrao" method="post" action="/corrida/edicao" style="margin-top:1rem;">
    <?= Csrf::field() ?>
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem;">
      <div><label>Trimestre (1 a 4)</label><input type="number" name="trimestre" min="1" max="4" value="3"></div>
      <div><label>Ano</label><input type="number" name="ano" value="<?= $anoPadrao ?>"></div>
      <div><label>Data de início</label><input type="date" name="data_inicio"></div>
      <div><label>Data de fim</label><input type="date" name="data_fim"></div>
    </div>
    <label>Rótulo (opcional)</label>
    <input type="text" name="nome" placeholder="ex.: Corrida dos Campeões — 3º trimestre">
    <div class="acoes-form"><button type="submit" class="btn">Criar edição</button></div>
  </form>
</details>

<?php if ($edicao === null): ?>
  <div class="card"><p class="subtitle" style="margin:0">Nenhuma edição cadastrada. Crie a primeira acima.</p></div>
<?php else: ?>

<div class="card">
  <div class="toolbar" style="margin-bottom:.4rem">
    <div>
      <h3 style="margin:0"><?= (int) $edicao['trimestre'] ?>º trimestre / <?= (int) $edicao['ano'] ?><?= $edicao['nome'] ? ' — ' . htmlspecialchars($edicao['nome'], ENT_QUOTES) : '' ?></h3>
      <p class="secao-sub" style="margin:.25rem 0 0">
        <?= $dataBr((string) $edicao['data_inicio']) ?> a <?= $dataBr((string) $edicao['data_fim']) ?> ·
        <span class="pill <?= $fechada ? 'status-aprovado' : 'status-ativo' ?>"><?= $fechada ? 'Fechada' : 'Aberta' ?></span>
        <?php if ($fechada): ?> · prêmio congelado: <strong><?= Viz::money($totalPremiado) ?></strong><?php endif; ?>
      </p>
    </div>
    <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
      <?php if ($aberta): ?>
        <form method="post" action="/corrida/edicao/<?= (int) $edicao['id'] ?>/fechar" onsubmit="return confirm('Fechar a edição? Os resultados serão congelados (dá para reabrir depois).');">
          <?= Csrf::field() ?><button type="submit" class="btn">Fechar edição</button>
        </form>
      <?php else: ?>
        <form method="post" action="/corrida/edicao/<?= (int) $edicao['id'] ?>/reabrir">
          <?= Csrf::field() ?><button type="submit" class="btn secundario">Reabrir</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($aberta): ?>
  <details style="margin-top:.6rem">
    <summary style="cursor:pointer; font-weight:600; color:var(--primary-ink);">Editar datas / rótulo / excluir</summary>
    <form class="form-padrao" method="post" action="/corrida/edicao/<?= (int) $edicao['id'] ?>" style="margin-top:1rem;">
      <?= Csrf::field() ?>
      <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem;">
        <div><label>Trimestre</label><input type="number" name="trimestre" min="1" max="4" value="<?= (int) $edicao['trimestre'] ?>"></div>
        <div><label>Ano</label><input type="number" name="ano" value="<?= (int) $edicao['ano'] ?>"></div>
        <div><label>Data de início</label><input type="date" name="data_inicio" value="<?= htmlspecialchars((string) $edicao['data_inicio'], ENT_QUOTES) ?>"></div>
        <div><label>Data de fim</label><input type="date" name="data_fim" value="<?= htmlspecialchars((string) $edicao['data_fim'], ENT_QUOTES) ?>"></div>
      </div>
      <label>Rótulo (opcional)</label>
      <input type="text" name="nome" value="<?= htmlspecialchars((string) ($edicao['nome'] ?? ''), ENT_QUOTES) ?>">
      <div class="acoes-form">
        <button type="submit" class="btn">Salvar</button>
      </div>
    </form>
    <form method="post" action="/corrida/edicao/<?= (int) $edicao['id'] ?>/excluir" onsubmit="return confirm('Excluir a edição inteira, com grupos e lançamentos? Não dá pra desfazer.');" style="margin-top:.8rem">
      <?= Csrf::field() ?><button type="submit" class="btn perigo pequeno">Excluir edição</button>
    </form>
  </details>
  <?php endif; ?>
</div>

<div class="secao">
  <h3>Grupos premiados</h3>
  <p class="secao-sub">Cada grupo tem um prêmio bruto rateado entre o top 5. A prévia mostra quanto cada posição leva se as 5 forem preenchidas.</p>

  <?php foreach ($grupos as $g): $previa = CorridaCalculator::previaRateio((float) $g['premio_bruto']); ?>
    <div class="card">
      <div style="display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap;">
        <form method="post" action="/corrida/grupo/<?= (int) $g['id'] ?>" style="display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap;">
          <?= Csrf::field() ?>
          <div><label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Nome do grupo</label><br>
            <input type="text" name="nome" value="<?= htmlspecialchars($g['nome'], ENT_QUOTES) ?>" <?= $aberta ? '' : 'disabled' ?> style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); min-width:16rem;"></div>
          <div><label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Prêmio bruto (R$)</label><br>
            <input type="text" name="premio_bruto" value="<?= $fmt($g['premio_bruto']) ?>" <?= $aberta ? '' : 'disabled' ?> style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); width:8rem;"></div>
          <?php if ($aberta): ?><button type="submit" class="btn pequeno">Salvar</button><?php endif; ?>
        </form>
        <?php if ($aberta): ?>
        <form method="post" action="/corrida/grupo/<?= (int) $g['id'] ?>/excluir" onsubmit="return confirm('Remover este grupo e seus lançamentos?');">
          <?= Csrf::field() ?><button type="submit" class="btn perigo pequeno">Remover</button>
        </form>
        <?php endif; ?>
      </div>
      <p class="secao-sub" style="margin:.6rem 0 0">
        Prévia do rateio:
        <?php foreach ($previa as $pos => $valor): ?>
          <strong><?= $pos ?>º</strong> <?= Viz::money($valor) ?><?= $pos < 5 ? ' · ' : '' ?>
        <?php endforeach; ?>
      </p>
    </div>
  <?php endforeach; ?>

  <?php if ($aberta): ?>
  <div class="card">
    <form method="post" action="/corrida/grupo" style="display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap;">
      <?= Csrf::field() ?>
      <input type="hidden" name="edicao_id" value="<?= (int) $edicao['id'] ?>">
      <div><label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Novo grupo</label><br>
        <input type="text" name="nome" placeholder="ex.: Similar/Genérico" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); min-width:16rem;"></div>
      <div><label style="font-size:.75rem; font-weight:700; color:var(--ink-soft)">Prêmio bruto (R$)</label><br>
        <input type="text" name="premio_bruto" placeholder="2000" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); width:8rem;"></div>
      <button type="submit" class="btn pequeno">Adicionar grupo</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($grupos)): ?>
<div class="secao">
  <h3>Grade de lançamento</h3>
  <p class="secao-sub">Valor <strong>acumulado</strong> vendido por funcionário em cada grupo. Atualize periodicamente — cada salvamento sobrescreve o total anterior.</p>

  <?php if (!$aberta): ?>
    <div class="callout dica"><span class="callout-label">Somente leitura</span>A edição está fechada. Reabra para lançar valores.</div>
  <?php endif; ?>

  <form method="post" action="/corrida/grade">
    <?= Csrf::field() ?>
    <input type="hidden" name="edicao_id" value="<?= (int) $edicao['id'] ?>">
    <div class="scrollx">
      <table class="faixas">
        <thead>
          <tr>
            <th>Funcionário</th>
            <th>Filial</th>
            <?php foreach ($grupos as $g): ?><th><?= htmlspecialchars($g['nome'], ENT_QUOTES) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($funcionarios as $f): $fid = (int) $f['id']; ?>
          <tr>
            <td style="white-space:nowrap"><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></td>
            <td style="white-space:nowrap; color:var(--ink-faint)"><?= htmlspecialchars($nomesFiliais[$f['filial_id']] ?? '—', ENT_QUOTES) ?></td>
            <?php foreach ($grupos as $g): $gid = (int) $g['id']; $v = $grade[$fid][$gid] ?? 0; ?>
              <td>
                <?php if ($aberta): ?>
                  <input type="text" name="valor[<?= $fid ?>][<?= $gid ?>]" value="<?= $fmt($v) ?>" style="width:6.5rem">
                <?php else: ?>
                  <?= $fmt($v) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($aberta): ?>
    <div class="acoes-form" style="margin-top:1.2rem"><button type="submit" class="btn">Salvar grade</button></div>
    <?php endif; ?>
  </form>
</div>
<?php endif; ?>

<?php require APP_DIR . '/Views/corrida/_rankings.php'; ?>

<?php endif; ?>
