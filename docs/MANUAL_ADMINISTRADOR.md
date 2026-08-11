# Manual do Administrador — Comissão 360

*Farmácia Geremias · Sistema de comissionamento das 7 filiais*

Este manual explica, em linguagem simples, como configurar o sistema pela primeira vez, como operar ele todo mês, e como interpretar os números que aparecem nos dashboards. Não é preciso entender de programação para seguir este guia — só é preciso entender o negócio da farmácia, que você já entende melhor do que ninguém.

---

## Parte 0 — Como usar este manual

### Convenções usadas aqui

Ao longo do texto você vai ver três tipos de caixa:

> **⚠️ Armadilha silenciosa**
> Isso marca um comportamento em que o sistema **não avisa nada na tela** — ele simplesmente calcula errado, zera um valor, ou toma uma decisão sozinho. São os pontos mais importantes deste manual, porque são erros que você só percebe olhando o resultado final (a comissão de alguém), não uma mensagem de erro.

> **💡 Dica**
> Uma sugestão prática de como usar o sistema no dia a dia.

Sempre que este manual disser `Menu > Tela`, é para você seguir esse caminho dentro do sistema — por exemplo, `Filiais > Nova filial` quer dizer "clique em Filiais no menu, depois no botão de nova filial".

### Mapa rápido — "eu quero..."

| Eu quero... | Vá para |
|---|---|
| Configurar o sistema pela primeira vez | Parte 2 |
| Saber o que fazer todo mês | Parte 3 |
| Entender por que a comissão de alguém deu esse valor | Parte 4 |
| Entender os números do dashboard | Parte 5 |
| Conferir tudo antes de aprovar o fechamento do mês | Parte 6 |
| Uma dúvida rápida / não sei o que significa um termo | Parte 7 |

---

## Parte 1 — Visão geral

### 1.1 O que o sistema faz

O Comissão 360 calcula, todo mês, quanto cada funcionário das 7 filiais deve receber de comissão — a partir das vendas lançadas, das metas definidas e de alguns indicadores de qualidade. A fórmula completa está na Parte 4, mas resumindo:

> **Total do mês de uma pessoa = (Comissão pelas Vendas × Bônus de Desempenho) + Prêmio de Filial**

### 1.2 Os três papéis de acesso

Cada pessoa que usa o sistema tem um papel, definido no cadastro dela (`Usuários`). O papel decide o que ela vê e o que ela pode editar:

| Papel | O que vê | O que edita |
|---|---|---|
| **Admin** (você) | Tudo, de todas as filiais | Filiais, Usuários, Categorias, Metas, Vendas, Indicadores, e é o único que aprova o Fechamento |
| **Gerente** | Só a(s) filial(is) dele | Vendas e Indicadores da própria filial; só **visualiza** Metas e Fechamento (não edita) |
| **Funcionário** | Só os próprios números | Nada de lançamento — só troca a própria senha/foto e consulta suas vendas e metas |

### 1.3 O "Período" — como o sistema organiza o mês

Todo mês é um "Período" dentro do sistema. Você **não precisa criar** o período — ele abre sozinho, automaticamente, assim que alguém acessa o sistema no dia 1 do mês. Ele começa com status **Aberto**, e o ciclo do mês é:

```
Metas  →  Vendas + Indicadores (ao longo do mês)  →  Fechamento  →  Aprovação
```

> **⚠️ Armadilha silenciosa — a aprovação do fechamento é definitiva**
> Quando você aprova o fechamento (Parte 3.5), o período muda para **Aprovado** e trava a rede inteira: ninguém mais consegue lançar venda, editar meta ou indicador daquele mês, em nenhuma filial. **Não existe botão de "reabrir" ou "desfazer" a aprovação.** Se algo estiver errado, a única saída é ajustar no mês seguinte. Por isso vale a pena conferir tudo antes de clicar em aprovar — use o checklist da Parte 3.5 ou da Parte 6.

> **💡 Dica**
> Tecnicamente existe um terceiro status chamado "Fechado" guardado no banco de dados, mas ele nunca é usado pelo sistema hoje — na prática, um período está sempre **Aberto** ou **Aprovado**. Se você ouvir falar desse terceiro status em algum lugar, pode ignorar.

### 1.4 O menu que cada papel vê

- **Admin**: Filiais · Usuários · Categorias · Metas · Vendas · Indicadores · Fechamento
- **Gerente**: Metas · Lançar vendas · Indicadores · Fechamento
- **Funcionário**: Minhas vendas · Minhas metas

Todos os papéis também têm acesso a **Dashboard** (a tela inicial, com os números do mês) e **Minha conta** (trocar senha e foto), no topo da tela.

---

## Parte 2 — Configuração inicial (fazer uma vez)

Esta parte você faz uma única vez, antes do primeiro mês de operação (as 7 filiais já vêm cadastradas no sistema, mas vale conferir tudo). A ordem abaixo **não é sugestão — é obrigatória**, porque cada cadastro depende do anterior:

```
1. Filiais  →  2. Categorias (com faixas)  →  3. Usuários
```

### 2.1 Passo 1 — Cadastrar as Filiais

`Menu > Filiais`

Cada filial precisa de:
- **Nome** — como ela aparece nas telas (ex.: "Centro", "Frai 1").
- **Código** — um identificador curto e único, usado internamente pelo sistema (ex.: "centro", "frai1"). Não pode repetir entre filiais.

Filiais cadastradas hoje: Centro, Floresta, Frai 1, Frai 2, Laboratório, Pinheiro Preto e Saul.

Filiais são a base de tudo: todo usuário, toda meta, toda venda, todo indicador está sempre amarrado a uma filial. Por isso é o primeiro cadastro.

Uma filial pode ser **desativada** depois (botão na listagem), o que a esconde das telas do dia a dia sem apagar nada do histórico. É seguro desativar uma filial que fechou, por exemplo — os dados antigos continuam intactos e consultáveis.

### 2.2 Passo 2 — Cadastrar as Categorias (com faixas de comissão)

`Menu > Categorias`

Categoria é a "linha de produto" pela qual a comissão é calculada — hoje o sistema tem 7: **Similar, Genérico, Perfumaria, Manipulação, RX, Conveniência e Correlatos**.

Cadastrar uma categoria é um processo de **duas partes que precisam ser feitas juntas**, não dois passos separados no tempo:

**Parte 1 — dados básicos**: nome da categoria e ordem de exibição (só decide a ordem que ela aparece nas listas, não afeta cálculo nenhum).

**Parte 2 — faixas de comissão**: uma tabela de até 6 faixas, cada uma com um valor-limite de venda no mês (em R$) e um percentual de comissão.

> **⚠️ Armadilha silenciosa — categoria sem faixa = comissão zero, sem aviso**
> Se você criar uma categoria e **não voltar para cadastrar as faixas dela**, a categoria fica ativa e disponível para lançar vendas normalmente — só que qualquer venda lançada nela vai gerar **0% de comissão**, e o sistema não avisa isso em lugar nenhum. Trate os dois passos como um só: **nunca deixe uma categoria ativa sem pelo menos uma faixa cadastrada.**

Um detalhe importante sobre como a faixa funciona (explicado com detalhe e exemplo na Parte 4.2): o percentual da faixa vale sobre o **valor total vendido** na categoria naquele mês, não é como Imposto de Renda (não é só sobre o que passa do limite).

**Composição de categoria (opcional)** — em até 2 linhas, você pode fazer uma categoria "somar" o valor vendido de outra categoria, ou o total de vendas marcadas como "venda sem nota" (S/N), antes de decidir em qual faixa ela cai. O sistema já vem configurado assim para a Manipulação: o valor considerado para a faixa de comissão da Manipulação é **Manipulação + tudo que foi marcado como venda sem nota** no lançamento de vendas. Use essa opção se precisar reproduzir uma regra parecida para outra categoria.

> **⚠️ Armadilha silenciosa — desativar categoria com vendas do mês some com a comissão**
> Se você desativar uma categoria que já tem vendas lançadas **dentro do mês ainda aberto**, essas vendas somem do cálculo de comissão daquele mês (a comissão delas passa a ser zero), mesmo que o valor continue registrado no histórico de vendas. Evite desativar uma categoria no meio de um mês em andamento — prefira fazer isso só depois que o mês já foi aprovado.

**Checklist de categoria pronta**: nome preenchido, pelo menos uma faixa cadastrada, e (se for o caso) composição configurada.

### 2.3 Passo 3 — Cadastrar os Usuários (a equipe)

`Menu > Usuários`

Cada usuário precisa de:
- **Nome** e **cargo** (cargo é só informativo, não afeta cálculo).
- **E-mail** — é o login da pessoa no sistema.
- **Senha inicial** — mínimo 8 caracteres; a pessoa pode trocar depois em "Minha conta".
- **Papel de acesso**: Admin, Gerente ou Funcionário (ver diferenças na Parte 1.2).
- **Filial(is) vinculada(s)** — obrigatório marcar pelo menos uma. Uma pessoa pode estar vinculada a mais de uma filial (por exemplo, alguém que circula entre Frai 1 e Frai 2).

**Filial principal**: quando a pessoa está vinculada a mais de uma filial, você precisa marcar qual delas é a **principal**. É essa filial que decide, no fechamento do mês, de qual meta de filial, rentabilidade e prêmio a pessoa participa.

> **⚠️ Armadilha silenciosa — sem filial principal marcada, o sistema escolhe sozinho**
> Se ninguém marcar a filial principal de uma pessoa vinculada a mais de uma filial, o sistema escolhe automaticamente uma delas (a de cadastro mais antigo entre as vinculadas) — que pode não ser a que você pretendia para fins de prêmio e rentabilidade. **Sempre marque a filial principal manualmente**, nunca deixe o sistema decidir por você.
>
> No pior caso — uma pessoa sem nenhuma filial vinculada, ou com um vínculo que ficou incoerente — ela simplesmente **não aparece no fechamento da rede**, sem nenhum aviso. Se um nome que deveria estar no fechamento sumiu, esse é o primeiro lugar a checar.

> **⚠️ Armadilha silenciosa — desativar usuário não retira a pessoa do cálculo de comissão**
> O botão de desativar um usuário (na listagem de Usuários) só bloqueia o **login** da pessoa — ela continua sendo contabilizada normalmente no cálculo de comissão do mês, como se estivesse ativa. Se alguém foi desligado da empresa no meio do mês, desativar o usuário impede que ela acesse o sistema, mas **não** a remove do fechamento daquele mês (hoje não existe uma ação no sistema que faça isso).

### 2.4 Checklist de fim de configuração inicial

Antes de liberar o primeiro mês de operação, confira:

- [ ] As 7 filiais estão cadastradas e com nome/código corretos.
- [ ] Toda categoria ativa tem pelo menos uma faixa de comissão cadastrada.
- [ ] Todo usuário tem pelo menos uma filial vinculada.
- [ ] Todo usuário vinculado a mais de uma filial tem a filial principal marcada manualmente.

---

## Parte 3 — Operação mensal (o que fazer todo mês)

O Período do mês abre sozinho — você não cria nada, só preenche as 4 telas abaixo, nesta ordem, ao longo do mês.

### 3.1 Visão geral do fluxo

| Etapa | Quem faz | Quando |
|---|---|---|
| 1. Metas | Só Admin | Início do mês |
| 2. Vendas | Admin ou Gerente | Ao longo do mês, conforme as vendas acontecem |
| 3. Indicadores | Admin ou Gerente | Ao longo do mês / no fechamento |
| 4. Fechamento (prévia + aprovação) | Prévia: Admin ou Gerente. Aprovação: só Admin | Fim do mês |

### 3.2 Passo 1 — Definir as Metas

`Menu > Metas`

Só o **Admin** pode editar essa tela (o gerente só visualiza). Duas coisas são definidas aqui, por filial e por período:

**Meta da filial:**
- **Meta de venda** (R$) — quanto a filial precisa vender no mês. É comparada com o realizado para o Prêmio de Filial e para o pilar "Resultado da Filial" da pontuação.
- **Meta de rentabilidade** (%) — comparada depois com a rentabilidade real lançada em Indicadores.
- **Valor do prêmio** (R$) — é aqui que **você define** quanto cada funcionário da filial ganha se ela bater a meta de venda (não é um valor fixo do sistema, é você quem escolhe, filial por filial, todo mês).

**Meta individual por funcionário:** uma grade com uma célula de valor (R$) por funcionário × por categoria. A soma dessas células é a "meta individual total" da pessoa, usada no pilar "Resultado Individual" da pontuação.

> **💡 Dica**
> Defina as metas logo no início do mês — sem elas, o pilar "Resultado da Filial" e o "Resultado Individual" da pontuação ficam sempre zerados (o sistema não tem referência para comparar o que foi vendido).

### 3.3 Passo 2 — Lançar as Vendas

`Menu > Vendas` (Admin) ou `Menu > Lançar vendas` (Gerente)

Cada venda lançada tem: **funcionário** (precisa pertencer à filial selecionada), **categoria**, **data** (dentro do mês do período aberto), **valor** e o campo **"Venda sem nota (S/N)"**.

Marque "venda sem nota" só quando for o caso — hoje esse campo é usado pela categoria Manipulação, que soma essas vendas ao decidir a faixa de comissão dela (ver Parte 2.2).

Um gerente só lança vendas na(s) filial(is) dele; o admin lança em qualquer filial.

> **⚠️ Lembrete da Parte 2.2**: se você desativar uma categoria que já tem vendas lançadas neste mês ainda aberto, a comissão dessas vendas some do cálculo do mês corrente.

Não existe edição de venda — só lançar ou excluir. Para corrigir um lançamento errado, exclua e lance de novo (só é possível enquanto o período estiver Aberto).

### 3.4 Passo 3 — Lançar os Indicadores

`Menu > Indicadores`

Três blocos, por filial/mês:

1. **Desconto médio e rentabilidade, por funcionário** — o percentual médio de desconto que a pessoa concedeu nas vendas do mês, e a rentabilidade dela no mês. Quanto **menor** o desconto médio, mais pontos no pilar Qualidade (ver Parte 4.3).
2. **Rentabilidade da filial** — percentual realizado no mês, comparado com a meta de rentabilidade definida em Metas.
3. **Checklist de equipe** (vale igual para todos os funcionários da filial naquele mês) — 5 itens, cada um "sim/não":
   - Sem falta injustificada
   - Cumpriu a escala
   - Setor organizado
   - Ajudou ou treinou um colega
   - A loja bateu a meta coletiva

Esses indicadores alimentam os pilares "Qualidade/Rentabilidade" e "Equipe" da pontuação (Parte 4.3). Pode reeditar quantas vezes quiser enquanto o mês estiver aberto.

### 3.5 Passo 4 — Conferir e aprovar o Fechamento

`Menu > Fechamento`

Esta tela mostra, **em tempo real**, o cálculo completo de cada funcionário da filial (comissão, pontuação, multiplicador, prêmio e total) — nada ainda está gravado, é só uma prévia enquanto o período estiver Aberto.

> **⚠️ PARE E CONFIRA ANTES DE APROVAR**
> Duas coisas importantes sobre o botão "Aprovar fechamento":
>
> 1. **Dado faltando não bloqueia a aprovação — ele simplesmente vira zero.** Se uma filial ficou sem meta cadastrada, ou um funcionário ficou sem indicador lançado, ou faltou lançar alguma venda, o sistema **não avisa nada** — ele trata a ausência como "não atingiu" e calcula com o valor mínimo, silenciosamente.
> 2. **A aprovação é definitiva e vale para a rede inteira** (todas as filiais de uma vez, não só a que você está vendo na tela). Depois de aprovado, não existe botão de desfazer.
>
> Por isso, use o checklist abaixo **antes** de clicar em aprovar — só o Admin tem esse botão.

**Checklist antes de aprovar:**
- [ ] Todas as filiais têm meta de venda e rentabilidade cadastrada?
- [ ] Todas as vendas do mês, de todas as filiais, já foram lançadas?
- [ ] Todos os funcionários têm desconto médio e rentabilidade lançados em Indicadores?
- [ ] O checklist de equipe foi preenchido em todas as filiais?
- [ ] Todos os nomes que deveriam aparecer no fechamento realmente aparecem (um nome ausente pode ser a armadilha de "filial principal" da Parte 2.3)?
- [ ] Alguma categoria nova ficou sem faixa cadastrada (armadilha da Parte 2.2)?

### 3.6 Depois de aprovar

O período muda para **Aprovado**. A partir daí:
- Vendas, Metas e Indicadores ficam travados para aquele mês em todas as filiais — nenhuma edição é possível.
- Os valores calculados ficam gravados oficialmente (é o valor que deve ser usado para pagamento).
- No dia 1 do mês seguinte, um novo Período abre sozinho e o ciclo recomeça.

---

## Parte 4 — Como funciona o cálculo da comissão

A fórmula completa:

> **Total do mês = (Comissão pelas Vendas × Bônus de Desempenho) + Prêmio de Filial**

### 4.1 Bloco 1 — Comissão pelas Vendas (faixas por categoria)

**Isto não funciona como Imposto de Renda.** No IR, só a parte do seu salário que passa de uma faixa paga a alíquota maior. Aqui **não**: o percentual da faixa em que a pessoa caiu vale sobre o **valor total vendido** naquela categoria no mês.

Exemplo real, com a categoria **Similar**:

| Vendeu até... | Percentual sobre o total |
|---|---|
| R$ 3.000 | 1% |
| R$ 7.000 | 3% |
| R$ 10.000 | 4% |
| Acima de R$ 10.000 | 6% |

Se uma pessoa vendeu **R$ 6.999** em Similar no mês, ela caiu na faixa de até R$ 7.000 → comissão = 6.999 × 3% = **R$ 209,97**.

Se ela tivesse vendido só **mais R$ 2** (R$ 7.001), ela passa para a faixa seguinte (até R$ 10.000, 4%) → comissão = 7.001 × 4% = **R$ 280,04**.

Ou seja: **R$ 2 a mais de venda valeram R$ 70 a mais de comissão**, porque o percentual novo passou a valer sobre o total, não só sobre o excedente. É exatamente por isso que o dashboard mostra a informação **"faltam R$X para a próxima faixa"** (Parte 5.2) — é um gancho concreto e real para empurrar a última venda do mês.

### 4.2 Bloco 2 — Bônus de Desempenho (Pontuação 360)

Pense nisso como um boletim de 0 a 100 pontos, dividido em 4 matérias:

| Pilar | Pontos máximos | O que mede |
|---|---|---|
| Resultado Individual | 40 | Venda da pessoa vs. a meta individual dela |
| Resultado da Filial | 30 | Venda da filial vs. a meta da filial |
| Qualidade/Rentabilidade | 20 | Desconto médio + rentabilidade da pessoa + rentabilidade da filial |
| Equipe | 10 | Checklist de equipe (5 itens × 2 pontos) |

A soma dos 4 pilares (0 a 100) define o **nível** da pessoa naquele mês, e o nível define um **multiplicador** que ajusta a comissão do Bloco 1:

| Pontuação | Nível | Multiplicador |
|---|---|---|
| 0 a 59 | Em desenvolvimento | 1,00× |
| 60 a 69 | Bronze | 0,50× |
| 70 a 79 | Prata | 0,75× |
| 80 a 89 | Platina | 1,00× |
| 90 a 94 | Diamante | 1,20× |
| 95 a 100 | Ouro | 1,50× |

> **A regra mais importante deste bloco: ninguém perde comissão por pontuação baixa.** O sistema tem uma trava de proteção que garante que o multiplicador nunca fica abaixo de 1,00× na prática — mesmo que a tabela acima mostre 0,50× para Bronze e 0,75× para Prata, esses dois níveis nunca reduzem a comissão de ninguém. **Só os níveis Diamante e Ouro aumentam** a comissão (1,20× e 1,50×); todos os outros pagam a comissão cheia (1,00×). Vale deixar isso claro com a equipe: a pontuação é sempre um bônus possível, nunca um desconto.

**O único pilar ajustável é o Qualidade/Rentabilidade** — os outros 3 (Individual, Filial, Equipe) usam regras fixas do sistema. Dentro do pilar Qualidade, hoje os valores são: desconto médio até 12% dá pontuação máxima (6 pts), desconto de 25% ou mais zera essa parte; rentabilidade da filial na meta dá 7 pts fixos; rentabilidade individual ≥ 28% dá 7 pts fixos.

> **⚠️ Armadilha silenciosa — não existe tela para ajustar esses parâmetros**
> Os valores acima (12%, 25%, 28%, e os pontos de cada critério) ficam guardados no banco de dados do sistema, mas **não existe uma tela no Comissão 360 para alterá-los**. Mudar isso hoje exige mexer direto no banco de dados — não é uma tarefa do dia a dia, é algo raro que precisa de suporte técnico. Não procure essa tela, ela ainda não existe.

### 4.3 Bloco 3 — Prêmio de Filial

O mais simples dos três: **valor fixo, tudo ou nada**. Você define o valor do prêmio por filial na tela de Metas (Parte 3.2). Se a filial bater a meta de venda do mês, **todo mundo** daquela filial recebe o valor cheio do prêmio. Se não bater, **ninguém** recebe nada — não existe meio-termo nem proporcionalidade.

### 4.4 Exemplo completo, do início ao fim

Vamos supor uma funcionária fictícia, a Ana, no mês:

1. **Comissão pelas Vendas**: Ana vendeu R$ 6.999 em Similar (3% = R$ 209,97) e valores em outras categorias que somam mais R$ 300,00 de comissão-base. Total do Bloco 1: **R$ 509,97**.
2. **Bônus de Desempenho**: Ana bateu 105% da meta individual (34 pts), a filial dela bateu 95% da meta (22 pts), o desconto médio dela ficou em 15% e a rentabilidade bateu a meta (juntos, 11 pts no pilar Qualidade), e a filial cumpriu 4 dos 5 itens do checklist de equipe (8 pts). Total: 34+22+11+8 = **75 pontos → nível Prata**. Pela trava de proteção, o multiplicador aplicado é **1,00×** (não o 0,75× que a tabela bruta mostraria).
3. **Prêmio de Filial**: a filial da Ana bateu a meta de venda, e o prêmio definido para ela foi R$ 150,00 → Ana recebe os **R$ 150,00** cheios.

**Total do mês da Ana = (509,97 × 1,00) + 150,00 = R$ 659,97**

---

## Parte 5 — Como ler os Dashboards

### 5.1 Os três painéis

A tela `Dashboard` muda de acordo com o papel de quem está logado:

- **Painel de Rede** (Admin) — visão consolidada das 7 filiais.
- **Painel de Filial** (Gerente) — visão só da(s) filial(is) dele.
- **Painel Pessoal** (Funcionário) — visão só do próprio desempenho.

### 5.2 O que aparece nos três

- **KPIs no topo**: comissão prevista, venda realizada vs. meta, % de atingimento.
- **Atingimento de meta**: barra de progresso comparando realizado com a meta.
- **Ranking**: quem mais vendeu/gerou comissão (na rede, na filial, ou a posição da própria pessoa entre os colegas).
- **Oportunidades / "Faltam R$X para a próxima faixa"**: as situações mais próximas de virar de faixa (ver o exemplo da Parte 4.1) — ordenadas pela oportunidade mais fácil de fechar primeiro.
- **Distribuição por nível**: quantas pessoas estão em cada nível (Em desenvolvimento, Bronze, Prata, Platina, Diamante, Ouro).

### 5.3 Minha Área (só funcionário)

`Menu > Minhas vendas` e `Menu > Minhas metas` — telas de **consulta**, sem nenhuma edição. O funcionário nunca lança vendas ou metas por conta própria; isso é sempre feito pelo gerente ou admin.

### 5.4 Minha Conta (todos os papéis)

`Topo da tela > Minha conta` — qualquer pessoa logada pode, sozinha, sem depender do admin:
- Trocar a própria senha.
- Trocar a própria foto de perfil.

---

## Parte 6 — Checklist consolidado: armadilhas silenciosas

Use esta tabela para uma revisão rápida, principalmente antes de aprovar um fechamento.

| # | O que acontece | Onde conferir | Detalhes |
|---|---|---|---|
| 1 | Categoria ativa sem faixa cadastrada gera 0% de comissão | `Categorias` | Parte 2.2 |
| 2 | Desativar categoria com vendas do mês aberto some com a comissão delas | `Categorias` / `Vendas` | Partes 2.2 e 3.3 |
| 3 | Sem filial principal marcada, o sistema escolhe sozinho | `Usuários` | Parte 2.3 |
| 4 | Usuário desativado continua contando no cálculo de comissão | `Usuários` | Parte 2.3 |
| 5 | Funcionário sem filial principal resolvível some do fechamento, sem aviso | `Usuários` / `Fechamento` | Parte 2.3 |
| 6 | Aprovar fechamento com dados faltando não é bloqueado — vira zero silenciosamente | `Fechamento` | Parte 3.5 |
| 7 | Fechamento aprovado não pode ser revertido | `Fechamento` | Partes 1.3 e 3.5 |
| 8 | Só existem os status "Aberto" e "Aprovado" na prática | `Fechamento` | Parte 1.3 |
| 9 | Só os parâmetros do pilar Qualidade têm efeito no cálculo, e não têm tela própria | — | Parte 4.2 |

---

## Parte 7 — Perguntas frequentes e Glossário

### Perguntas frequentes

**Posso editar uma venda de um mês já aprovado?**
Não. Depois que o fechamento é aprovado, vendas, metas e indicadores daquele mês ficam travados em todas as filiais, sem exceção.

**Por que a comissão de uma categoria deu zero para todo mundo?**
O motivo mais comum é a categoria estar sem nenhuma faixa de comissão cadastrada (Parte 2.2) — confira em `Categorias`.

**O que significa "venda sem nota (S/N)"?**
É uma marcação especial no lançamento de venda, usada hoje pela categoria Manipulação, que soma essas vendas ao decidir em qual faixa de comissão a pessoa cai (Parte 2.2).

**Existe uma "Corrida dos Campeões"?**
Esse módulo aparece mencionado em documentos de planejamento do projeto, mas **ainda não existe nenhuma tela ou funcionalidade pronta** para ele no sistema hoje — é só uma estrutura reservada no banco de dados, sem uso. Não prometa isso para a equipe ainda.

**Um gerente pode aprovar o fechamento?**
Não — só o Admin tem o botão de aprovar. O gerente vê a mesma prévia de cálculo, mas não consegue confirmar.

### Glossário

- **Período**: o "mês" dentro do sistema — abre sozinho no dia 1, e vai de Aberto até Aprovado.
- **Fechamento**: a tela que calcula e (quando aprovada) trava os valores oficiais do mês.
- **Faixa de comissão**: cada linha da tabela de uma categoria, que define o percentual de comissão conforme o valor total vendido.
- **Multiplicador**: o número (0,50× a 1,50×, protegido para nunca ficar abaixo de 1,00× na prática) que ajusta a comissão-base de acordo com a pontuação da pessoa.
- **Nível**: o rótulo (Em desenvolvimento, Bronze, Prata, Platina, Diamante, Ouro) correspondente à faixa de pontuação da pessoa no mês.
- **Filial principal**: a filial "dona" de uma pessoa vinculada a mais de uma, para fins de meta/prêmio/rentabilidade.
- **Venda sem nota (S/N)**: marcação especial de venda, usada na composição da categoria Manipulação.
- **Composição de categoria**: regra que soma o valor vendido de outra categoria (ou de vendas "sem nota") ao decidir a faixa de comissão de uma categoria.
