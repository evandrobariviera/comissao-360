<div class="toolbar">
  <div>
    <h2>Ajuda</h2>
    <p class="subtitle">Como o sistema calcula a comissão, e como operá-lo mês a mês.</p>
  </div>
</div>

<div class="card ajuda">

  <ul class="ajuda-toc">
    <li><a href="#ajuda-visao-geral">1 · Visão geral</a></li>
    <li><a href="#ajuda-config">2 · Configuração inicial</a></li>
    <li><a href="#ajuda-operacao">3 · Operação mês a mês</a></li>
    <li><a href="#ajuda-calculo">4 · Como funciona o cálculo — as 3 camadas</a></li>
    <li><a href="#ajuda-relatorios">5 · Relatórios</a></li>
    <li><a href="#ajuda-corrida">6 · Corrida dos Campeões</a></li>
    <li><a href="#ajuda-paineis">7 · Como ler os Painéis</a></li>
    <li><a href="#ajuda-armadilhas">8 · Armadilhas silenciosas</a></li>
    <li><a href="#ajuda-faq">9 · Perguntas frequentes e Glossário</a></li>
  </ul>

  <section id="ajuda-visao-geral">
    <h3>1. Visão geral</h3>

    <p>O sistema calcula, todo mês, quanto cada funcionário das 7 filiais deve receber de comissão — a partir das vendas lançadas, das metas definidas e de indicadores de qualidade de atendimento.</p>

    <div class="formula">
      <span class="formula-label">Fórmula-resumo</span>
      Total do mês = (Comissão pelas vendas × Fator de desempenho) + Prêmio de filial
    </div>

    <h4>Os três papéis de acesso</h4>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Papel</th><th>O que vê</th><th>O que edita</th></tr></thead>
        <tbody>
          <tr><td><span class="badge-papel">Admin</span></td><td>Tudo, de todas as filiais</td><td>Configuração (Filiais, Usuários, Regras de comissão), Metas, Vendas, Qualidade — e é o único que aprova e reabre o Fechamento</td></tr>
          <tr><td><span class="badge-papel">Gerente</span></td><td>A rede toda nos Relatórios e na Corrida; nas telas de lançamento, só a(s) filial(is) dele</td><td>Vendas e Qualidade da própria filial; <strong>visualiza</strong> Metas e Fechamento</td></tr>
          <tr><td><span class="badge-papel">Funcionário</span></td><td>Só os próprios números (e o ranking da Corrida)</td><td>Nada de lançamento — só a própria senha/foto</td></tr>
        </tbody>
      </table>
    </div>

    <h4>O "Período" — como o sistema organiza o mês</h4>
    <p>Todo mês é um "Período". Você <strong>não precisa criar</strong> nada — ele abre sozinho no dia 1. O ciclo é: Metas → Vendas + Qualidade (ao longo do mês) → Fechamento (aprovado por filial).</p>
    <p>O seletor de mês no canto superior direito só aparece nas telas que trabalham por mês (Painel, Metas, Vendas, Qualidade, Fechamento). A escolha vale até você trocar de novo.</p>

    <h4>O menu de cada papel</h4>
    <ul>
      <li><strong>Admin</strong>: Painel · Metas · Vendas · Qualidade · Fechamento · Relatórios · Corrida dos Campeões — e, no canto, <strong>Configuração</strong> (Filiais · Usuários · Regras de comissão).</li>
      <li><strong>Gerente</strong>: Painel da filial · Meu desempenho · Metas · Lançar vendas · Qualidade · Fechamento · Relatórios · Corrida dos Campeões.</li>
      <li><strong>Funcionário</strong>: Meu painel · Minhas vendas · Minhas metas · Corrida dos Campeões.</li>
    </ul>
  </section>

  <section id="ajuda-config">
    <h3>2. Configuração inicial (fazer uma vez)</h3>
    <p>Tudo em <strong>Configuração</strong> (link no canto superior direito). A ordem <strong>é obrigatória</strong>: Filiais → Regras de comissão → Usuários.</p>

    <h4>Passo 1 — Filiais</h4>
    <p><strong>Nome</strong> + <strong>Código</strong> (curto e único). Filiais de hoje: Centro, Floresta, Frai 1, Frai 2, Laboratório, Pinheiro Preto e Saul. Tudo (usuário, meta, venda, indicador) fica amarrado a uma filial — por isso é o primeiro cadastro.</p>

    <h4>Passo 2 — Regras de comissão</h4>
    <p>Uma tela só, com três abas — as três camadas do cálculo (detalhe na seção 4):</p>
    <ul>
      <li><strong>Faixas por categoria</strong> — cada categoria (Similar, Genérico, Perfumaria, Manipulação, RX, Conveniência, Correlatos) tem sua tabela de faixas: um limite em R$ e um percentual por linha, até 6 linhas. A composição especial (ex.: a Manipulação somar as vendas "sem nota") também é aqui, na edição da categoria.</li>
      <li><strong>Meta 360</strong> — a régua da rede para o fator de desempenho: pisos e tetos de Qualidade (desconto, rentabilidade, ticket médio) e os pesos dos 4 pilares. Dá para sobrescrever o piso/teto numa filial específica, no bloco de baixo.</li>
      <li><strong>Prêmio de filial e mix</strong> — o valor de referência do prêmio (o valor que de fato vale para cada filial é definido em Metas) e a meta de mix de vendas por categoria (usada só nos relatórios).</li>
    </ul>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Categoria ativa sem faixa = comissão zero, sem aviso</span>
      Se você criar a categoria e não cadastrar as faixas, ela fica ativa e aceita vendas — mas toda venda nela gera <strong>0% de comissão</strong>, sem nenhum aviso.
    </div>
    <div class="callout armadilha">
      <span class="callout-label">⚠ Desativar categoria com vendas do mês aberto</span>
      A comissão dessas vendas some do cálculo do mês corrente (o valor continua no histórico). Evite fazer isso no meio de um mês em andamento.
    </div>

    <h4>Passo 3 — Usuários</h4>
    <p>Nome, cargo, e-mail (login), senha inicial (mín. 8 caracteres), papel e vínculo com ao menos uma filial.</p>
    <p><strong>Filial principal:</strong> quando a pessoa está em mais de uma filial, marque a principal — ela decide de qual meta, prêmio e rentabilidade a pessoa participa no fechamento.</p>

    <div class="callout armadilha">
      <span class="callout-label">⚠ Sem filial principal marcada, o sistema escolhe sozinho</span>
      E pode não ser a que você pretendia para prêmio/rentabilidade. No pior caso (vínculo incoerente), a pessoa <strong>não aparece no fechamento</strong>, sem aviso.
    </div>
    <div class="callout armadilha">
      <span class="callout-label">⚠ Desativar usuário não o retira do cálculo</span>
      Desativar só bloqueia o <strong>login</strong> — a pessoa continua contando no fechamento daquele mês.
    </div>

    <h4>Checklist de fim de configuração</h4>
    <ul class="checklist">
      <li>As 7 filiais estão cadastradas.</li>
      <li>Toda categoria ativa tem ao menos uma faixa de comissão.</li>
      <li>A Meta 360 (pesos e pisos/tetos) foi conferida em Regras de comissão.</li>
      <li>Todo usuário tem ao menos uma filial vinculada, e quem está em mais de uma tem a principal marcada.</li>
    </ul>
  </section>

  <section id="ajuda-operacao">
    <h3>3. Operação mês a mês</h3>
    <p>O Período abre sozinho. Você preenche estas quatro telas, nesta ordem, ao longo do mês.</p>

    <h4>Passo 1 — Metas <span class="badge-papel">Só Admin edita</span></h4>
    <p><strong>Meta da filial:</strong> meta de venda, meta de rentabilidade (comparativo do painel) e o valor do prêmio de filial. <strong>Meta individual:</strong> um valor por funcionário × categoria.</p>

    <h4>Passo 2 — Vendas</h4>
    <p>Dois blocos na mesma tela:</p>
    <ul>
      <li><strong>Venda bruta da filial</strong> — lançamento diário (dia + valor), que <strong>soma</strong> ao total do mês. É esse total, não a soma dos funcionários, que conta como "realizado" para a meta e o prêmio da filial. Se alguma categoria tem meta de mix, você lança também o acumulado do mês por categoria aqui.</li>
      <li><strong>Realizado por funcionário/categoria</strong> — a grade onde o gerente digita o total já vendido no mês por pessoa e categoria (sobrescreve o valor anterior). É a base da comissão pelas vendas.</li>
    </ul>
    <p>Marque "Venda sem nota (S/N)" só na Manipulação, quando for o caso — o sistema soma essas vendas ao decidir a faixa da Manipulação.</p>

    <h4>Passo 3 — Qualidade</h4>
    <p>Por funcionário: <strong>desconto médio</strong> (quanto menor, mais pontos), <strong>rentabilidade</strong> e <strong>ticket médio</strong>. Por filial: <strong>rentabilidade da filial</strong> e o <strong>checklist de equipe</strong> (8 itens: sem falta injustificada, cumpriu escala, setor organizado, ajudou/treinou colega, loja bateu meta coletiva, venda de 5 catálogos, venda de 30 a vencer, venda de 30 linha própria).</p>

    <h4>Passo 4 — Fechamento <span class="badge-papel">Só Admin aprova</span></h4>
    <p>A tela mostra o cálculo em tempo real, <strong>filial por filial</strong>. Nada é gravado até você aprovar aquela filial.</p>

    <div class="callout critico">
      <span class="callout-label">🛑 Antes de aprovar uma filial</span>
      <p style="margin:.4rem 0 0"><strong>1.</strong> Dado faltando não bloqueia — vira zero silenciosamente (meta, venda ou indicador ausente é tratado como "não atingiu", sem aviso).</p>
      <p style="margin:.4rem 0 0"><strong>2.</strong> Aprovar grava o valor oficial daquela filial e trava os lançamentos dela naquele mês. <strong>Só o admin reabre</strong> — e reabrir apaga o valor gravado, voltando ao cálculo ao vivo.</p>
    </div>

    <ul class="checklist">
      <li>A filial tem meta de venda e de rentabilidade cadastrada?</li>
      <li>Todas as vendas do mês dela já foram lançadas (grade e venda bruta)?</li>
      <li>Todos os funcionários dela têm desconto médio e rentabilidade lançados?</li>
      <li>O checklist de equipe dela foi preenchido?</li>
      <li>Todos os nomes que deveriam aparecer no fechamento dela aparecem?</li>
    </ul>

    <p>O fechamento é <strong>por filial</strong>: você pode aprovar Centro hoje e Saul só na semana que vem. O mês (Período) não tem um "aprovar tudo" — cada filial fecha no seu tempo.</p>
  </section>

  <section id="ajuda-calculo">
    <h3>4. Como funciona o cálculo — as 3 camadas</h3>

    <h4>Camada 1 — Comissão pelas vendas</h4>
    <p><strong>Não funciona como Imposto de Renda.</strong> O percentual da faixa alcançada vale sobre o valor <strong>total</strong> vendido na categoria — não só sobre o que passa do limite.</p>

    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Categoria Similar — vendeu no mês, até…</th><th>Percentual sobre o total</th></tr></thead>
        <tbody>
          <tr><td>R$ 3.000</td><td>1%</td></tr>
          <tr><td>R$ 7.000</td><td>3%</td></tr>
          <tr><td>R$ 10.000</td><td>4%</td></tr>
          <tr><td>acima de R$ 10.000</td><td>6%</td></tr>
        </tbody>
      </table>
    </div>

    <div class="exemplo">
      <h4>R$ 2 que valeram R$ 70</h4>
      <p>Vendeu R$ 6.999 em Similar → 3% = R$ 209,97. Vendesse R$ 7.001 → passa pra faixa de 4% <em>sobre o total</em> → R$ 280,04. <strong>Dois reais a mais de venda valeram <span class="resultado">R$ 70</span> a mais de comissão.</strong> É por isso que os Painéis mostram "faltam R$ X para a próxima faixa" em cada categoria.</p>
    </div>

    <h4>Camada 2 — Fator de desempenho (Meta 360)</h4>
    <p>Um boletim de 0 a 100 pontos em 4 pilares, com pesos diferentes:</p>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Pilar</th><th>Peso</th><th>Como pontua</th></tr></thead>
        <tbody>
          <tr><td>Resultado individual</td><td>40</td><td>Quanto a pessoa atingiu da própria meta de venda</td></tr>
          <tr><td>Resultado da filial</td><td>30</td><td>Quanto a filial atingiu da meta de venda bruta</td></tr>
          <tr><td>Qualidade</td><td>20</td><td>4 medidores de 5 pts: desconto médio, rentabilidade da filial, rentabilidade da pessoa e ticket médio — numa régua de piso a teto</td></tr>
          <tr><td>Equipe</td><td>10</td><td>Os 8 itens do checklist (1,25 pt cada)</td></tr>
        </tbody>
      </table>
    </div>

    <p>A soma dos 4 pilares vira um nível e um multiplicador:</p>
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
      <strong>Ninguém perde comissão por pontuação baixa.</strong> O multiplicador é protegido: na prática nunca fica abaixo de 1,00×. Bronze e Prata não reduzem nada — só Diamante e Ouro aumentam.
    </div>

    <p>Os pisos, tetos e pesos ficam em <strong>Configuração → Regras de comissão → Meta 360</strong>, com override opcional por filial.</p>

    <h4>Camada 3 — Prêmio de filial</h4>
    <p>Valor fixo, tudo ou nada: se a venda bruta da filial no mês <strong>bate</strong> a meta, todo mundo da filial recebe o valor cheio; se não bate, ninguém recebe. O valor é definido por filial em Metas.</p>

    <div class="exemplo">
      <h4>Exemplo completo — a Ana, um mês</h4>
      <p>Comissão pelas vendas: R$ 509,97. Boletim: 75 pts → Prata → multiplicador protegido 1,00×. Prêmio de filial: R$ 150,00 (a filial bateu a meta).</p>
      <p><strong>Total = (509,97 × 1,00) + 150,00 = <span class="resultado">R$ 659,97</span></strong></p>
    </div>
  </section>

  <section id="ajuda-relatorios">
    <h3>5. Relatórios</h3>
    <p>Comparativos mês a mês, em tabela, para <strong>admin e gerente</strong> (o gerente vê a rede inteira aqui). Escolha o relatório, o intervalo de meses e, quando faz sentido, uma filial ou uma métrica. Cada tabela pode ser <strong>exportada em CSV</strong>.</p>
    <ul>
      <li><strong>Vendas</strong> — venda bruta por filial, venda por categoria, realizado × meta (%), mix de vendas por categoria (%). Leem os dados ao vivo; funcionam para qualquer mês.</li>
      <li><strong>Comissão &amp; Pontuação</strong> — comissão paga por filial e por funcionário, pontuação 360 média, distribuição de níveis. Só entram filiais com o fechamento <strong>aprovado</strong> no mês; mês em aberto aparece vazio.</li>
      <li><strong>Qualidade</strong> — ticket médio, desconto médio, rentabilidade por filial, e o checklist de equipe.</li>
    </ul>
  </section>

  <section id="ajuda-corrida">
    <h3>6. Corrida dos Campeões</h3>
    <p>Uma bonificação <strong>trimestral</strong>, separada do ciclo mensal, com seu próprio seletor de edição. Todos os funcionários das 7 filiais competem juntos, sem separar por loja.</p>
    <ul>
      <li>Cada edição define <strong>grupos</strong> de produtos a premiar (ex.: Similar/Genérico, linha própria, uma combinação de laboratórios) — variam a cada trimestre.</li>
      <li>O admin lança periodicamente o valor acumulado vendido por funcionário em cada grupo. Em cada grupo, os <strong>5 primeiros</strong> recebem um prêmio bruto rateado por peso decrescente (1º = 5/15, 2º = 4/15, … 5º = 1/15; posição vazia não é paga).</li>
      <li>Produtos específicos podem ter um <strong>bônus por unidade/caixa</strong>, pago a todo funcionário que vendeu, sem depender de posição. Se o produto estiver ligado a um grupo, o valor dele também conta no ranking daquele grupo.</li>
      <li>Fechar a edição congela os resultados; reabrir volta ao cálculo ao vivo. Gerentes e funcionários veem o ranking, mas só o admin lança.</li>
    </ul>
  </section>

  <section id="ajuda-paineis">
    <h3>7. Como ler os Painéis</h3>
    <p>O <strong>Painel</strong> muda conforme o papel:</p>
    <ul>
      <li><strong>Admin</strong> — visão de rede: as 7 filiais, comissão prevista, atingimento de meta, ranking, distribuição por nível.</li>
      <li><strong>Gerente</strong> — "Painel da filial" (estratégico, a equipe dele) e "Meu desempenho" (os próprios números, já que o gerente também vende).</li>
      <li><strong>Funcionário</strong> — "Meu painel": comissão prevista, ritmo de venda no mês, "faltam R$ X para a próxima faixa" por categoria, e a própria qualidade contra a régua vigente.</li>
    </ul>
    <p><strong>Minhas vendas</strong> e <strong>Minhas metas</strong> (funcionário) são telas de consulta. <strong>Minha conta</strong> (todos): trocar a própria senha e foto.</p>
  </section>

  <section id="ajuda-armadilhas">
    <h3>8. Armadilhas silenciosas</h3>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>#</th><th>O que acontece</th><th>Onde conferir</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>Categoria ativa sem faixa gera 0% de comissão, sem aviso</td><td>Regras de comissão → Faixas</td></tr>
          <tr><td>2</td><td>Desativar categoria com vendas do mês aberto some com a comissão delas</td><td>Regras de comissão / Vendas</td></tr>
          <tr><td>3</td><td>Sem filial principal marcada, o sistema escolhe sozinho</td><td>Configuração → Usuários</td></tr>
          <tr><td>4</td><td>Usuário desativado continua contando no cálculo daquele mês</td><td>Configuração → Usuários</td></tr>
          <tr><td>5</td><td>Funcionário sem filial principal resolvível some do fechamento, sem aviso</td><td>Usuários / Fechamento</td></tr>
          <tr><td>6</td><td>Aprovar o fechamento de uma filial com dados faltando não é bloqueado — vira zero</td><td>Fechamento</td></tr>
          <tr><td>7</td><td>Reabrir uma filial apaga o valor que tinha sido gravado como oficial</td><td>Fechamento</td></tr>
          <tr><td>8</td><td>O "realizado" da meta e do prêmio de filial é a venda bruta lançada, não a soma dos funcionários</td><td>Vendas → Venda bruta da filial</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section id="ajuda-faq">
    <h3>9. Perguntas frequentes e Glossário</h3>

    <p><strong>Posso editar uma venda de uma filial já aprovada no mês?</strong><br>Não enquanto ela estiver aprovada. O admin pode reabrir a filial — o que apaga o valor gravado e volta ao cálculo ao vivo.</p>
    <p><strong>Preciso "fechar o mês" inteiro de uma vez?</strong><br>Não. O fechamento é por filial; cada uma é aprovada no seu tempo. Um novo Período abre sozinho no dia 1 seguinte.</p>
    <p><strong>Por que a comissão de uma categoria deu zero para todo mundo?</strong><br>Quase sempre é a categoria sem faixa cadastrada — confira em Regras de comissão → Faixas.</p>
    <p><strong>Onde mudo os pesos dos pilares ou o piso/teto de desconto?</strong><br>Configuração → Regras de comissão → aba Meta 360.</p>
    <p><strong>O que significa "venda sem nota (S/N)"?</strong><br>Marcação da Manipulação: essas vendas são somadas ao decidir a faixa da Manipulação (contam em dobro para efeito de comissão).</p>
    <p><strong>A Corrida dos Campeões entra no total do mês?</strong><br>Não — é uma bonificação trimestral paga à parte, com regras próprias.</p>
    <p><strong>Um gerente pode aprovar o fechamento?</strong><br>Não — só o admin aprova e reabre.</p>

    <h4>Glossário</h4>
    <ul>
      <li><strong>Período</strong> — o "mês" dentro do sistema; abre sozinho no dia 1.</li>
      <li><strong>Fechamento</strong> — o cálculo que, ao ser aprovado <em>por filial</em>, grava os valores oficiais e trava os lançamentos daquela filial no mês.</li>
      <li><strong>Faixa de comissão</strong> — cada linha da tabela de uma categoria: um limite em R$ e o percentual que vale sobre o total vendido.</li>
      <li><strong>Meta 360</strong> — o boletim de 0 a 100 pontos (4 pilares) que gera o fator de desempenho.</li>
      <li><strong>Multiplicador protegido</strong> — o fator de desempenho, garantido a nunca ficar abaixo de 1,00× na prática.</li>
      <li><strong>Nível</strong> — o rótulo da faixa de pontuação: Em desenvolvimento, Bronze, Prata, Platina, Diamante, Ouro.</li>
      <li><strong>Venda bruta da filial</strong> — o total vendido no mês pela loja inteira, lançado dia a dia; é o "realizado" da meta e do prêmio de filial.</li>
      <li><strong>Filial principal</strong> — a filial "dona" de uma pessoa vinculada a mais de uma.</li>
      <li><strong>Composição de categoria</strong> — regra que soma o valor de outra categoria (ou das vendas S/N) ao decidir a faixa de comissão.</li>
      <li><strong>Corrida dos Campeões</strong> — campanha trimestral de bonificação por linha de produto, paga à parte da comissão mensal.</li>
    </ul>
  </section>

</div>
