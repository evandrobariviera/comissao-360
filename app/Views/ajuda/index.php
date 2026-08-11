<div class="toolbar">
  <div>
    <h2>Ajuda</h2>
    <p class="subtitle">Manual do administrador — configuração, operação mensal e como ler os dashboards.</p>
  </div>
</div>

<div class="card ajuda">

  <ul class="ajuda-toc">
    <li><a href="#ajuda-visao-geral">1 · Visão geral</a></li>
    <li><a href="#ajuda-config">2 · Configuração inicial</a></li>
    <li><a href="#ajuda-operacao">3 · Operação mensal</a></li>
    <li><a href="#ajuda-calculo">4 · Como funciona o cálculo</a></li>
    <li><a href="#ajuda-dashboards">5 · Como ler os Dashboards</a></li>
    <li><a href="#ajuda-checklist">6 · Checklist de armadilhas silenciosas</a></li>
    <li><a href="#ajuda-faq">7 · Perguntas frequentes e Glossário</a></li>
  </ul>

  <section id="ajuda-visao-geral">
    <h3>1. Visão geral</h3>

    <p>O Comissão 360 calcula, todo mês, quanto cada funcionário das 7 filiais deve receber de comissão — a partir das vendas lançadas, das metas definidas e de alguns indicadores de qualidade.</p>

    <div class="formula">
      <span class="formula-label">Fórmula-resumo</span>
      Total do mês = (Comissão pelas Vendas × Bônus de Desempenho) + Prêmio de Filial
    </div>

    <h4>Os três papéis de acesso</h4>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Papel</th><th>O que vê</th><th>O que edita</th></tr></thead>
        <tbody>
          <tr><td><span class="badge-papel">Admin</span></td><td>Tudo, de todas as filiais</td><td>Filiais, Usuários, Categorias, Metas, Vendas, Indicadores — e é o único que aprova o Fechamento</td></tr>
          <tr><td><span class="badge-papel">Gerente</span></td><td>Só a(s) filial(is) dele</td><td>Vendas e Indicadores da própria filial; só <strong>visualiza</strong> Metas e Fechamento</td></tr>
          <tr><td><span class="badge-papel">Funcionário</span></td><td>Só os próprios números</td><td>Nada de lançamento — só troca a própria senha/foto e consulta suas vendas e metas</td></tr>
        </tbody>
      </table>
    </div>

    <h4>O "Período" — como o sistema organiza o mês</h4>
    <p>Todo mês é um "Período". Você <strong>não precisa criar</strong> nada — ele abre sozinho no dia 1, com status <strong>Aberto</strong>. O ciclo é: Metas → Vendas + Indicadores (ao longo do mês) → Fechamento → Aprovação.</p>

    <div class="callout armadilha">
      <span class="callout-label">⚠ A aprovação do fechamento é definitiva</span>
      Quando o fechamento é aprovado, o período vira <strong>Aprovado</strong> e trava a rede inteira: ninguém mais lança venda, edita meta ou indicador daquele mês, em nenhuma filial. Não existe botão de "reabrir". Confira tudo antes de aprovar — veja o checklist da seção 3 ou 6.
    </div>

    <div class="callout dica">
      <span class="callout-label">💡 Dica</span>
      Só existem os status "Aberto" e "Aprovado" na prática — o status "Fechado" existe no banco mas nunca é usado.
    </div>

    <h4>O menu que cada papel vê</h4>
    <ul>
      <li><strong>Admin</strong>: Filiais · Usuários · Categorias · Metas · Vendas · Indicadores · Fechamento</li>
      <li><strong>Gerente</strong>: Metas · Lançar vendas · Indicadores · Fechamento</li>
      <li><strong>Funcionário</strong>: Minhas vendas · Minhas metas</li>
    </ul>
  </section>

  <section id="ajuda-config">
    <h3>2. Configuração inicial (fazer uma vez)</h3>
    <p>A ordem abaixo <strong>não é sugestão — é obrigatória</strong>: Filiais → Categorias (com faixas) → Usuários.</p>

    <h4>Passo 1 — Filiais</h4>
    <p><strong>Nome</strong> (como aparece nas telas) + <strong>Código</strong> (identificador curto e único). Filiais de hoje: Centro, Floresta, Frai 1, Frai 2, Laboratório, Pinheiro Preto e Saul. Tudo (usuário, meta, venda, indicador) está amarrado a uma filial — por isso é o primeiro cadastro.</p>

    <h4>Passo 2 — Categorias e faixas de comissão</h4>
    <p>Categorias hoje: Similar, Genérico, Perfumaria, Manipulação, RX, Conveniência e Correlatos. Cadastrar uma categoria tem <strong>duas partes que precisam andar juntas</strong>: dados básicos (nome + ordem) e as faixas de comissão (até 6 linhas, cada uma com um limite em R$ e um percentual).</p>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Categoria sem faixa = comissão zero, sem aviso</span>
      Se você criar a categoria e não voltar para cadastrar as faixas, ela fica ativa e aceita vendas normalmente — mas toda venda lançada nela gera <strong>0% de comissão</strong>, sem nenhum aviso na tela. Nunca deixe uma categoria ativa sem pelo menos uma faixa.
    </div>

    <p>O percentual da faixa vale sobre o <strong>valor total vendido</strong> na categoria — não é como Imposto de Renda (exemplo completo na seção 4).</p>

    <p><strong>Composição de categoria (opcional):</strong> uma categoria pode "somar" o valor de outra categoria ou de vendas marcadas "sem nota" antes de decidir a faixa. Hoje isso já está configurado para a Manipulação (soma as vendas sem nota).</p>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Desativar categoria com vendas do mês aberto</span>
      Se você desativar uma categoria que já tem vendas lançadas no mês ainda aberto, a comissão dessas vendas some do cálculo do mês corrente (mesmo com o valor continuando no histórico). Evite fazer isso no meio de um mês em andamento.
    </div>

    <h4>Passo 3 — Usuários</h4>
    <p>Nome, cargo, e-mail (login), senha inicial (mín. 8 caracteres), papel de acesso, e vínculo obrigatório com ao menos uma filial.</p>

    <p><strong>Filial principal:</strong> quando vinculado a mais de uma filial, marque qual é a principal — ela decide de qual meta/prêmio/rentabilidade a pessoa participa no fechamento.</p>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Sem filial principal marcada, o sistema escolhe sozinho</span>
      Se ninguém marcar, o sistema escolhe uma filial automaticamente — que pode não ser a que você pretendia para prêmio/rentabilidade. Sempre marque manualmente. No pior caso (vínculo incoerente), a pessoa simplesmente <strong>não aparece no fechamento da rede</strong>, sem aviso.
    </div>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Desativar usuário não retira a pessoa do cálculo de comissão</span>
      Desativar só bloqueia o <strong>login</strong> — a pessoa continua contando normalmente no fechamento daquele mês. Hoje não existe ação para removê-la do cálculo.
    </div>

    <h4>Checklist de fim de configuração</h4>
    <ul class="checklist">
      <li>As 7 filiais estão cadastradas e com nome/código corretos.</li>
      <li>Toda categoria ativa tem pelo menos uma faixa de comissão cadastrada.</li>
      <li>Todo usuário tem pelo menos uma filial vinculada.</li>
      <li>Todo usuário vinculado a mais de uma filial tem a filial principal marcada manualmente.</li>
    </ul>
  </section>

  <section id="ajuda-operacao">
    <h3>3. Operação mensal</h3>
    <p>O Período abre sozinho — você só preenche as 4 telas abaixo, nesta ordem, ao longo do mês.</p>

    <h4>Passo 1 — Metas <span class="badge-papel">Só Admin edita</span></h4>
    <p><strong>Meta da filial:</strong> meta de venda, meta de rentabilidade e o valor do prêmio (é aqui que você define o valor, não é fixo no sistema). <strong>Meta individual:</strong> um valor por funcionário × categoria.</p>

    <h4>Passo 2 — Vendas</h4>
    <p>Cada venda: funcionário (da filial selecionada), categoria, data (dentro do mês aberto), valor, e o campo "Venda sem nota (S/N)" — marque só quando for o caso. Só é possível criar ou excluir, nunca editar; e só enquanto o período estiver Aberto.</p>

    <h4>Passo 3 — Indicadores</h4>
    <p>Três blocos: desconto médio + rentabilidade por funcionário (quanto <strong>menor</strong> o desconto, mais pontos); rentabilidade da filial; e o checklist de equipe (5 itens, 2 pontos cada): sem falta injustificada, cumpriu escala, setor organizado, ajudou/treinou colega, loja bateu meta coletiva.</p>

    <h4>Passo 4 — Fechamento <span class="badge-papel">Só Admin aprova</span></h4>
    <p>A tela mostra o cálculo em tempo real — nada é gravado até a aprovação.</p>

    <div class="callout critico">
      <span class="callout-label">🛑 Pare e confira antes de aprovar</span>
      <p style="margin:.4rem 0 0"><strong>1.</strong> Dado faltando não bloqueia a aprovação — vira zero silenciosamente (meta, venda ou indicador ausente é tratado como "não atingiu", sem nenhum aviso).</p>
      <p style="margin:.4rem 0 0"><strong>2.</strong> A aprovação é definitiva e vale para a rede inteira de uma vez — não existe botão de desfazer.</p>
    </div>

    <ul class="checklist">
      <li>Todas as filiais têm meta de venda e rentabilidade cadastrada?</li>
      <li>Todas as vendas do mês, de todas as filiais, já foram lançadas?</li>
      <li>Todos os funcionários têm desconto médio e rentabilidade lançados?</li>
      <li>O checklist de equipe foi preenchido em todas as filiais?</li>
      <li>Todos os nomes que deveriam aparecer no fechamento realmente aparecem?</li>
      <li>Alguma categoria nova ficou sem faixa cadastrada?</li>
    </ul>

    <p>Depois de aprovar: Vendas, Metas e Indicadores ficam travados naquele mês, os valores ficam gravados oficialmente, e no dia 1 seguinte um novo Período abre sozinho.</p>
  </section>

  <section id="ajuda-calculo">
    <h3>4. Como funciona o cálculo</h3>

    <h4>Comissão pelas Vendas</h4>
    <p><strong>Não funciona como Imposto de Renda.</strong> O percentual da faixa em que a pessoa caiu vale sobre o <strong>valor total vendido</strong> na categoria — não só sobre o que passa do limite.</p>

    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Categoria Similar — vendeu até...</th><th>Percentual sobre o total</th></tr></thead>
        <tbody>
          <tr><td>R$ 3.000</td><td>1%</td></tr>
          <tr><td>R$ 7.000</td><td>3%</td></tr>
          <tr><td>R$ 10.000</td><td>4%</td></tr>
          <tr><td>Acima de R$ 10.000</td><td>6%</td></tr>
        </tbody>
      </table>
    </div>

    <div class="exemplo">
      <h4>R$ 2 que valeram R$ 70</h4>
      <p>Vendeu R$ 6.999 → 3% = R$ 209,97. Vendesse R$ 2 a mais (R$ 7.001) → passa pra faixa de 4% → R$ 280,04. <strong>R$ 2 a mais de venda valeram <span class="resultado">R$ 70</span> a mais de comissão.</strong> É por isso que o dashboard mostra "faltam R$X para a próxima faixa".</p>
    </div>

    <h4>Bônus de Desempenho (Pontuação 360)</h4>
    <p>Um boletim de 0 a 100 pontos em 4 matérias: Resultado Individual (até 40), Resultado da Filial (até 30), Qualidade/Rentabilidade (até 20), Equipe (até 10). A soma vira um nível e um multiplicador:</p>

    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Pontos</th><th>Nível</th><th>Multiplicador</th></tr></thead>
        <tbody>
          <tr><td>0–59</td><td>Em desenvolvimento</td><td>1,00×</td></tr>
          <tr><td>60–69</td><td>Bronze</td><td>0,50×</td></tr>
          <tr><td>70–79</td><td>Prata</td><td>0,75×</td></tr>
          <tr><td>80–89</td><td>Platina</td><td>1,00×</td></tr>
          <tr><td>90–94</td><td>Diamante</td><td>1,20×</td></tr>
          <tr><td>95–100</td><td>Ouro</td><td>1,50×</td></tr>
        </tbody>
      </table>
    </div>

    <div class="callout dica">
      <span class="callout-label">💡 A regra mais importante</span>
      <strong>Ninguém perde comissão por pontuação baixa.</strong> Uma trava garante que o multiplicador nunca fica abaixo de 1,00× na prática — Bronze e Prata nunca reduzem a comissão. Só Diamante e Ouro aumentam.
    </div>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Não existe tela para ajustar os parâmetros de Qualidade</span>
      Os valores do pilar Qualidade (piso/teto de desconto, meta de rentabilidade) ficam no banco de dados — mudar isso hoje exige suporte técnico direto no banco.
    </div>

    <h4>Prêmio de Filial</h4>
    <p>Valor fixo, tudo ou nada: definido pelo admin por filial em Metas. Se a filial bate a meta de venda, todo mundo dela recebe o valor cheio; se não bate, ninguém recebe nada.</p>

    <div class="exemplo">
      <h4>Exemplo completo — a Ana, um mês</h4>
      <p>Comissão pelas Vendas: R$ 509,97. Pontuação: 75 pts → Prata → multiplicador protegido 1,00×. Prêmio de Filial: R$ 150,00 (filial bateu a meta).</p>
      <p><strong>Total = (509,97 × 1,00) + 150,00 = <span class="resultado">R$ 659,97</span></strong></p>
    </div>
  </section>

  <section id="ajuda-dashboards">
    <h3>5. Como ler os Dashboards</h3>
    <p>A tela <strong>Dashboard</strong> muda conforme o papel: <strong>Painel de Rede</strong> (admin, as 7 filiais), <strong>Painel de Filial</strong> (gerente, só a(s) dele) e <strong>Painel Pessoal</strong> (funcionário, só o próprio desempenho).</p>
    <p>Elementos comuns: KPIs no topo (comissão prevista, venda vs. meta), atingimento de meta, ranking, oportunidades ("faltam R$X para a próxima faixa") e distribuição por nível.</p>
    <p><strong>Minha Área</strong> (funcionário): Minhas vendas e Minhas metas — telas de consulta, sem edição. <strong>Minha Conta</strong> (todos): trocar a própria senha e foto.</p>
  </section>

  <section id="ajuda-checklist">
    <h3>6. Checklist consolidado: armadilhas silenciosas</h3>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>#</th><th>O que acontece</th><th>Onde conferir</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>Categoria ativa sem faixa gera 0% de comissão</td><td>Categorias</td></tr>
          <tr><td>2</td><td>Desativar categoria com vendas do mês aberto some com a comissão delas</td><td>Categorias / Vendas</td></tr>
          <tr><td>3</td><td>Sem filial principal marcada, o sistema escolhe sozinho</td><td>Usuários</td></tr>
          <tr><td>4</td><td>Usuário desativado continua contando no cálculo de comissão</td><td>Usuários</td></tr>
          <tr><td>5</td><td>Funcionário sem filial principal resolvível some do fechamento, sem aviso</td><td>Usuários / Fechamento</td></tr>
          <tr><td>6</td><td>Aprovar fechamento com dados faltando não é bloqueado — vira zero</td><td>Fechamento</td></tr>
          <tr><td>7</td><td>Fechamento aprovado não pode ser revertido</td><td>Fechamento</td></tr>
          <tr><td>8</td><td>Só existem os status "Aberto" e "Aprovado" na prática</td><td>Fechamento</td></tr>
          <tr><td>9</td><td>Só os parâmetros de Qualidade têm efeito, e não têm tela</td><td>—</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section id="ajuda-faq">
    <h3>7. Perguntas frequentes e Glossário</h3>

    <p><strong>Posso editar uma venda de um mês já aprovado?</strong><br>Não — vendas, metas e indicadores de um mês aprovado ficam travados em todas as filiais.</p>
    <p><strong>Por que a comissão de uma categoria deu zero para todo mundo?</strong><br>O motivo mais comum é a categoria estar sem faixa cadastrada — confira em Categorias.</p>
    <p><strong>O que significa "venda sem nota (S/N)"?</strong><br>Marcação especial usada hoje pela categoria Manipulação, que soma essas vendas ao decidir a faixa dela.</p>
    <p><strong>Existe uma "Corrida dos Campeões"?</strong><br>Aparece em documentos de planejamento, mas ainda não existe tela ou funcionalidade pronta — é só uma estrutura reservada no banco. Não prometa isso para a equipe ainda.</p>
    <p><strong>Um gerente pode aprovar o fechamento?</strong><br>Não — só o Admin tem o botão de aprovar.</p>

    <h4>Glossário</h4>
    <ul>
      <li><strong>Período</strong> — o "mês" dentro do sistema, de Aberto até Aprovado.</li>
      <li><strong>Fechamento</strong> — a tela que calcula e (quando aprovada) trava os valores oficiais do mês.</li>
      <li><strong>Faixa de comissão</strong> — cada linha da tabela de uma categoria, com o percentual conforme o valor vendido.</li>
      <li><strong>Multiplicador</strong> — ajusta a comissão-base conforme a pontuação (protegido para nunca ficar abaixo de 1,00×).</li>
      <li><strong>Nível</strong> — rótulo (Em desenvolvimento, Bronze, Prata, Platina, Diamante, Ouro) da faixa de pontuação.</li>
      <li><strong>Filial principal</strong> — a filial "dona" de uma pessoa vinculada a mais de uma.</li>
      <li><strong>Venda sem nota (S/N)</strong> — marcação usada na composição da categoria Manipulação.</li>
      <li><strong>Composição de categoria</strong> — regra que soma o valor de outra categoria/flag ao decidir a faixa de comissão.</li>
    </ul>
  </section>

</div>
