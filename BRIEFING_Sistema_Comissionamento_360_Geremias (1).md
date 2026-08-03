# BRIEFING — Sistema de Comissionamento 360º · Farmácia Geremias
**Para uso em novo chat / sessão de desenvolvimento**
Versão 1.0 · Agosto 2026

---

## 1. Contexto da empresa

**Farmácia Geremias** — rede de farmácias com **7 filiais**:
Centro, Floresta, Frai 1, Frai 2, Laboratório, Pinheiro Preto e Saul.

**Situação financeira:** caixa apertado. Um dos principais problemas é estoque parado (~R$ 224 mil imobilizados) e **desconto excessivo no balcão** corroendo a margem (margem bruta ~33%, mas o desconto de balcão consome grande parte do resultado). O sistema de comissionamento é tratado como **instrumento financeiro**, não apenas de RH — ele deve atuar diretamente sobre desconto, mix de produtos e rentabilidade.

**Stakeholders principais:** Evandro (gestor do projeto), Beto (diretor, deu os feedbacks que moldaram as regras).

---

## 2. O modelo de comissionamento (regras de negócio completas)

### 2.1 Estrutura geral — três blocos

A remuneração variável é formada por:

```
Comissão por Categoria
  → Comissão-Base
  → × Multiplicador Meta 360º
  → = Comissão Ajustada
  → + Prêmio de Filial
  = TOTAL DO MÊS
```

### 2.2 Bloco 1 — Comissão por categoria (PROTEGIDA)

Cada vendedor comissiona por **linha de produto**, com faixas progressivas. A comissão-base é **protegida**: é o piso do mês, o multiplicador 360 só acrescenta, nunca subtrai o que já foi vendido. Isso é deliberado por dois motivos: motivação e segurança trabalhista (comissão sobre venda realizada é parcela salarial no Brasil).

**Categorias e faixas (tabela editável):**

| Categoria | Faixa 1 (1%) | Faixa 2 | Faixa 3 | Acima |
|---|---|---|---|---|
| Similar | até R$ 3.000 | até R$ 7.000 (3%) | até R$ 10.000 (4%) | 6% |
| Genérico | até R$ 4.000 | até R$ 5.800 (2%) | até R$ 8.000 (3%) | 5% |
| Perfumaria | até R$ 4.500 | até R$ 8.500 (3%) | até R$ 10.000 (4%) | 6% |
| Manipulação* | até R$ 5.000 | até R$ 8.500 (2%) | até R$ 15.000 (4%) | 6% |
| RX | até R$ 12.000 | até R$ 16.000 (2%) | até R$ 20.000 (3%) | 4% |
| Conveniência | até R$ 2.000 | até R$ 3.500 (2%) | até R$ 5.000 (4%) | 6% |
| Correlatos | até R$ 1.000 | até R$ 1.900 (2%) | até R$ 3.000 (3%) | 6% |

**Regra especial Manipulação + S/N (sem nota):**
O S/N (vendas sem nota / off-system) não passa no sistema, mas é pago como manipulação. A comissão da Manipulação é calculada sobre **Manipulação + S/N somados**, aplicando a faixa da Manipulação sobre o total.

**Comissão-Base = soma das comissões de todas as categorias.**

### 2.3 Bloco 2 — Meta 360º (multiplicador)

Pontuação de 0 a 100, com 4 pilares:

| Pilar | Peso | Como é medido |
|---|---|---|
| Resultado Individual | 40 pts | Atingimento % da meta individual do mês |
| Resultado da Filial | 30 pts | Atingimento % da meta da filial |
| Qualidade/Rentabilidade | 20 pts | Desconto médio + rentab. farmácia + rentab. funcionário |
| Equipe | 10 pts | Checklist objetivo (5 critérios, 2 pts cada) |

**Pilar Resultado Individual (0–40 pts) — escala:**
```
Atingimento ≥ 110% → 40 pts
Atingimento ≥ 100% → 38 pts
Atingimento ≥  90% → 34 pts
Atingimento ≥  80% → 28 pts
Atingimento ≥  70% → 20 pts
Abaixo de 70%      →  0 pts
```

**Pilar Resultado da Filial (0–30 pts) — escala:**
```
Atingimento ≥ 100% → 30 pts
Atingimento ≥  90% → 22 pts
Atingimento ≥  80% → 15 pts
Abaixo de 80%      →  0 pts
```

**Pilar Qualidade/Rentabilidade (0–20 pts) — três sub-componentes:**
- **Desconto médio (0–6 pts):** proporcional linear entre teto (0 pts) e piso (6 pts cheios). Parâmetros editáveis: desconto ≤ 12% = 6 pts; ≥ 25% = 0 pts. Fórmula: `MAX(0, 6 × (25% − desconto_real) / (25% − 12%))`
- **Rentabilidade da farmácia (0 ou 7 pts):** se a rentabilidade realizada da filial no mês ≥ meta de rentabilidade da filial (parâmetro, ex: 30%), 7 pts; senão 0.
- **Rentabilidade do funcionário (0 ou 7 pts):** se a rentabilidade da venda do funcionário no mês ≥ meta individual de rentabilidade (parâmetro, ex: 28%), 7 pts; senão 0.
- **Cap:** min(20, soma dos três)

**Pilar Equipe (0–10 pts) — checklist binário (2 pts cada):**
1. Sem falta injustificada
2. Cumpriu escala / cobriu colegas
3. Setor organizado / sem ruptura
4. Ajudou ou treinou colega
5. Loja bateu a meta coletiva

**Pontuação Final → Multiplicador:**

| Pontuação | Nível | Multiplicador |
|---|---|---|
| 0–59 | Em desenvolvimento | piso (modelo protegido: mult = 1,0 = comissão-base garantida) |
| 60–69 | Bronze | 0,50 |
| 70–79 | Prata | 0,75 |
| 80–89 | Platina | 1,00 |
| 90–94 | Diamante | 1,20 |
| 95–100 | Ouro | 1,50 |

**Modelo Protegido (obrigatório):** Comissão Ajustada = Comissão-Base × MAX(1, Multiplicador). A comissão vendida nunca cai abaixo da base.

### 2.4 Bloco 3 — Prêmio de filial

Valor fixo (referência: R$ 250) pago a **todos** os atendentes e gestores da filial quando a loja bate a meta do mês. Configurável por filial.

---

## 3. Corrida dos Campeões (módulo separado)

Programa **trimestral** e **competitivo entre todas as filiais** (rede toda). Diferente da comissão mensal: não mede atingimento de meta própria, mas **ranking relativo por valor de venda** em grupos de produtos específicos.

**Regras:**
- A cada trimestre são definidos os **grupos de produtos** em disputa (giram a cada edição).
- Os **5 melhores vendedores de cada grupo** (valor de venda total no trimestre) recebem prêmio fixo por colocação.
- Prêmios variam por grupo e por colocação (ex: R$ 400/250/175/150/100).
- Empate: mesma colocação para os empatados.

**Grupos de exemplo (trimestre atual):**
- Forthiben / Ativesse + Herbamed → prêmios: 350/250/200/150/100
- Botica + Amend + Dahuer → 350/250/200/150/100
- Similar + Genérico → 400/250/175/150/100
- Linha Própria Geremias → 400/250/175/125/100
- Nutra X + Macrophytus → 350/250/175/150/100

**Este módulo é separado da comissão mensal.** Cadências e regras diferentes.

---

## 4. Parametrização (tudo editável)

Os seguintes dados são configuráveis e **não devem ser hardcoded**:

- Tabela de faixas de comissão por categoria (valores e percentuais)
- Meta mensal de cada filial (R$)
- Meta mensal individual por funcionário por categoria (R$)
- Meta de rentabilidade da filial (%)
- Meta de rentabilidade individual (%)
- Teto e piso de desconto médio (%)
- Pontuação máxima por pilar
- Tabela de multiplicadores por faixa de pontos
- Valor do prêmio de filial (R$) — configurável por filial
- Grupos e prêmios da Corrida dos Campeões (por edição)

---

## 5. Fluxo operacional mensal

```
Início do mês
  → Configurar meta da filial e metas individuais por categoria

Durante o mês
  → Registrar vendas por funcionário e categoria (idealmente diário)
  → Registrar desconto médio e rentabilidade por funcionário

Fechamento do mês
  → Registrar rentabilidade realizada da filial
  → Preencher checklist de equipe (5 critérios)
  → Sistema calcula: comissão-base, pontuação 360, multiplicador, total
  → Gestor confere e aprova
  → Exportar para folha de pagamento
```

---

## 6. O que o sistema precisa fazer (requisitos funcionais)

1. **Cadastro de filiais** (7) e funcionários (multi-filial é possível)
2. **Cadastro de metas** mensais por filial e por funcionário/categoria
3. **Lançamento de vendas** por funcionário, categoria e data (diário ou por fechamento)
4. **Lançamento de indicadores** de desempenho: desconto médio, rentabilidade do funcionário, checklist de equipe
5. **Lançamento do resultado da filial**: venda realizada e rentabilidade
6. **Cálculo automático** de comissão-base, pontuação 360, multiplicador, prêmio e total
7. **Dashboard por filial**: atingimento de meta, ranking de funcionários, custo de comissão
8. **Módulo Corrida dos Campeões**: cadastro de edição trimestral, grupos, prêmios, lançamento de valores e geração automática do ranking
9. **Histórico**: meses e trimestres anteriores consultáveis
10. **Exportação**: relatório de fechamento por funcionário pronto para folha
11. **Controle de acesso**: pelo menos dois níveis (gestor de filial vs. administrador geral)

---

## 7. Stack definida

- **Backend:** PHP
- **Banco de dados:** a definir (MySQL/MariaDB são o caminho natural)
- **Frontend:** a definir (pode ser PHP puro com Bootstrap, ou separação com JS)
- **Ambiente:** a definir (servidor próprio? hospedagem compartilhada? Docker?)

---

## 8. Artefatos já produzidos (contexto anterior)

Os seguintes documentos e planilhas já foram gerados e validados com o cliente:

| Artefato | Descrição |
|---|---|
| `Simulador_Comissao_360_Geremias.xlsx` | Simulador com SIMULADOR, PARÂMETROS e COMO USAR. Já inclui Manipulação+S/N e os dois índices de rentabilidade. Serve como referência de cálculo e validação das regras. |
| `Corrida_dos_Campeoes_Geremias.xlsx` | Planilha da Corrida trimestral com ranking automático e distribuição de prêmios. |
| `Politica_Comissionamento_360_Geremias.docx` | Documento formal para a Diretoria justificando e explicando a política. |
| `politica_comissionamento_geremias.md` | Resumo da política em linguagem acessível para apresentar à equipe. |

---

## 9. Decisões de design já tomadas

- ✅ Modelo **protegido** (comissão-base nunca zerada pelo multiplicador)
- ✅ S/N entra **somado à Manipulação** para fins de comissão
- ✅ Salário fixo e remuneração total **fora** do sistema — a planilha/sistema trata só comissão
- ✅ Rentabilidade entra como **liga/desliga** (atingiu meta → pts cheios; não atingiu → 0)
- ✅ Equipe como **checklist binário** (5 critérios × 2 pts)
- ✅ Corrida dos Campeões é **módulo separado** da comissão mensal
- ⬜ Validação jurídica das parcelas (comissão vs. prêmio) — pendente com contabilidade/RH
- ⬜ Calibragem final das faixas de comissão por categoria com dados reais (faixas atuais foram desenhadas para volume de filial, não por vendedor — precisam ser ajustadas com 1 mês de dados reais)

---

## 10. Ponto de partida sugerido para o próximo chat

1. Definir stack completa (banco, frontend, ambiente de deploy)
2. Modelar o banco de dados (entidades: filial, funcionário, período, meta, lançamento, indicadores, parâmetros, corrida)
3. Desenhar a arquitetura da aplicação PHP
4. Começar pelo módulo de cadastro e cálculo de comissão (core), deixar Corrida para depois
