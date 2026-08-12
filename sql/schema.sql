-- Comissão 360 · Farmácia Geremias
-- Schema MySQL/MariaDB — ver documento de arquitetura para o dicionário completo.
-- Convenção: tudo em utf8mb4, InnoDB, timestamps em UTC.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- NÚCLEO — filiais, usuários, funcionários
-- ============================================================

CREATE TABLE filial (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(80)  NOT NULL,
  codigo        VARCHAR(20)  NOT NULL,
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_filial_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE usuario (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(150) NOT NULL,
  senha_hash    VARCHAR(255) NOT NULL,
  papel         ENUM('admin','gerente','funcionario') NOT NULL,
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_login  DATETIME     NULL,
  tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_ate DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_email (email)
) ENGINE=InnoDB;

CREATE TABLE funcionario (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT UNSIGNED NOT NULL,
  nome          VARCHAR(150) NOT NULL,
  cargo         VARCHAR(80)  NULL,
  avatar_path   VARCHAR(255) NULL,
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_funcionario_usuario (usuario_id),
  CONSTRAINT fk_funcionario_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB;

CREATE TABLE funcionario_filial (
  funcionario_id INT UNSIGNED NOT NULL,
  filial_id      INT UNSIGNED NOT NULL,
  principal      TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (funcionario_id, filial_id),
  CONSTRAINT fk_ff_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id),
  CONSTRAINT fk_ff_filial FOREIGN KEY (filial_id) REFERENCES filial(id)
) ENGINE=InnoDB;

-- ============================================================
-- NÚCLEO — categorias e faixas de comissão (Bloco 1)
-- ============================================================

CREATE TABLE categoria (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome   VARCHAR(60) NOT NULL,
  ordem  INT NOT NULL DEFAULT 0,
  ativo  TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categoria_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE faixa_comissao (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id  INT UNSIGNED NOT NULL,
  ordem         TINYINT UNSIGNED NOT NULL,       -- 1,2,3... a última faixa de cada categoria tem limite_ate = NULL ("acima")
  limite_ate    DECIMAL(12,2) NULL,
  percentual    DECIMAL(5,2)  NOT NULL,           -- ex.: 3.00 = 3%
  UNIQUE KEY uq_faixa_categoria_ordem (categoria_id, ordem),
  CONSTRAINT fk_faixa_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id)
) ENGINE=InnoDB;

-- Nível 3 de parametrização: uma categoria "empresta" valor de outra
-- categoria ou de uma marcação de venda antes de cair na faixa.
-- Generaliza a regra Manipulação+S/N do briefing sem precisar de código.
CREATE TABLE categoria_composicao (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_regra_id  INT UNSIGNED NOT NULL,      -- categoria cuja faixa será aplicada
  fonte_tipo          ENUM('categoria','flag_venda') NOT NULL,
  fonte_categoria_id  INT UNSIGNED NULL,          -- usado quando fonte_tipo = 'categoria'
  fonte_flag          VARCHAR(40) NULL,           -- usado quando fonte_tipo = 'flag_venda' (ex.: 'eh_sn')
  CONSTRAINT fk_cc_categoria_regra FOREIGN KEY (categoria_regra_id) REFERENCES categoria(id),
  CONSTRAINT fk_cc_categoria_fonte FOREIGN KEY (fonte_categoria_id) REFERENCES categoria(id)
) ENGINE=InnoDB;

-- ============================================================
-- NÚCLEO — período, metas, lançamentos, indicadores (Blocos 1-3)
-- ============================================================

CREATE TABLE periodo (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ano           SMALLINT UNSIGNED NOT NULL,
  mes           TINYINT UNSIGNED NOT NULL,
  status        ENUM('aberto','fechado','aprovado') NOT NULL DEFAULT 'aberto',
  aprovado_por  INT UNSIGNED NULL,
  aprovado_em   DATETIME NULL,
  UNIQUE KEY uq_periodo_ano_mes (ano, mes),
  CONSTRAINT fk_periodo_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuario(id)
) ENGINE=InnoDB;

CREATE TABLE meta_filial (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id          INT UNSIGNED NOT NULL,
  filial_id           INT UNSIGNED NOT NULL,
  meta_venda          DECIMAL(12,2) NOT NULL DEFAULT 0,
  meta_rentabilidade  DECIMAL(5,2)  NOT NULL DEFAULT 0,
  valor_premio        DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Venda bruta do mês alimentada manualmente pelo gerente (inclui vendas que não
  -- passam pela grade por funcionário/categoria). É o "realizado" usado nos
  -- medidores de meta e no prêmio de filial (Bloco 3) — não é derivado de soma.
  venda_bruta_realizada       DECIMAL(12,2) NOT NULL DEFAULT 0,
  venda_bruta_atualizado_em   DATETIME NULL,
  venda_bruta_atualizado_por  INT UNSIGNED NULL,
  UNIQUE KEY uq_meta_filial (periodo_id, filial_id),
  CONSTRAINT fk_mf_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_mf_filial FOREIGN KEY (filial_id) REFERENCES filial(id),
  CONSTRAINT fk_mf_venda_bruta_usuario FOREIGN KEY (venda_bruta_atualizado_por) REFERENCES usuario(id)
) ENGINE=InnoDB;

CREATE TABLE meta_funcionario (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id    INT UNSIGNED NOT NULL,
  funcionario_id INT UNSIGNED NOT NULL,
  categoria_id  INT UNSIGNED NOT NULL,
  meta_venda    DECIMAL(12,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_meta_funcionario (periodo_id, funcionario_id, categoria_id),
  CONSTRAINT fk_mfu_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_mfu_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id),
  CONSTRAINT fk_mfu_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id)
) ENGINE=InnoDB;

-- Cada linha é um AJUSTE (diferença entre o total novo e o total anterior no mês),
-- gerado quando o gerente atualiza a grade por funcionário/categoria em /vendas —
-- não é lançamento de venda individual por dia. `data` marca quando o ajuste foi
-- feito. A soma de todos os ajustes do período é o total realizado da categoria.
CREATE TABLE venda_lancamento (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id    INT UNSIGNED NOT NULL,
  funcionario_id INT UNSIGNED NOT NULL,
  filial_id     INT UNSIGNED NOT NULL,
  categoria_id  INT UNSIGNED NOT NULL,
  data          DATE NOT NULL,
  valor         DECIMAL(12,2) NOT NULL,
  eh_sn         TINYINT(1) NOT NULL DEFAULT 0,     -- venda "sem nota", só ocorre em Manipulação
  criado_por    INT UNSIGNED NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_venda_periodo_funcionario (periodo_id, funcionario_id),
  KEY ix_venda_periodo_categoria (periodo_id, categoria_id),
  CONSTRAINT fk_venda_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_venda_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id),
  CONSTRAINT fk_venda_filial FOREIGN KEY (filial_id) REFERENCES filial(id),
  CONSTRAINT fk_venda_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id),
  CONSTRAINT fk_venda_usuario FOREIGN KEY (criado_por) REFERENCES usuario(id)
) ENGINE=InnoDB;

CREATE TABLE indicador_funcionario (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id        INT UNSIGNED NOT NULL,
  funcionario_id    INT UNSIGNED NOT NULL,
  desconto_medio    DECIMAL(5,2) NOT NULL DEFAULT 0,
  rentabilidade_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_indicador_funcionario (periodo_id, funcionario_id),
  CONSTRAINT fk_ind_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_ind_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id)
) ENGINE=InnoDB;

CREATE TABLE rentabilidade_filial (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id        INT UNSIGNED NOT NULL,
  filial_id         INT UNSIGNED NOT NULL,
  rentabilidade_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_rentab_filial (periodo_id, filial_id),
  CONSTRAINT fk_rf_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_rf_filial FOREIGN KEY (filial_id) REFERENCES filial(id)
) ENGINE=InnoDB;

CREATE TABLE checklist_equipe (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id  INT UNSIGNED NOT NULL,
  filial_id   INT UNSIGNED NOT NULL,
  c1_sem_falta_injustificada     TINYINT(1) NOT NULL DEFAULT 0,
  c2_cumpriu_escala              TINYINT(1) NOT NULL DEFAULT 0,
  c3_setor_organizado            TINYINT(1) NOT NULL DEFAULT 0,
  c4_ajudou_treinou_colega       TINYINT(1) NOT NULL DEFAULT 0,
  c5_loja_bateu_meta_coletiva    TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_checklist_filial (periodo_id, filial_id),
  CONSTRAINT fk_chk_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_chk_filial FOREIGN KEY (filial_id) REFERENCES filial(id)
) ENGINE=InnoDB;

-- ============================================================
-- NÚCLEO — parametrização do Bloco 2 (Meta 360º) e resultado
-- ============================================================

CREATE TABLE parametro (
  chave      VARCHAR(60) PRIMARY KEY,
  valor      VARCHAR(255) NOT NULL,
  descricao  VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE multiplicador_faixa (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pontos_de     TINYINT UNSIGNED NOT NULL,
  pontos_ate    TINYINT UNSIGNED NOT NULL,
  nivel         VARCHAR(40) NOT NULL,
  multiplicador DECIMAL(4,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE comissao_calculada (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  periodo_id        INT UNSIGNED NOT NULL,
  funcionario_id    INT UNSIGNED NOT NULL,
  filial_id         INT UNSIGNED NOT NULL,
  comissao_base     DECIMAL(12,2) NOT NULL,
  pontuacao_360     DECIMAL(5,2)  NOT NULL,
  multiplicador     DECIMAL(4,2)  NOT NULL,
  comissao_ajustada DECIMAL(12,2) NOT NULL,
  premio_filial     DECIMAL(10,2) NOT NULL DEFAULT 0,
  total             DECIMAL(12,2) NOT NULL,
  status            ENUM('rascunho','fechado','aprovado') NOT NULL DEFAULT 'rascunho',
  aprovado_por      INT UNSIGNED NULL,
  aprovado_em       DATETIME NULL,
  calculado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comissao_periodo_funcionario (periodo_id, funcionario_id),
  CONSTRAINT fk_cc2_periodo FOREIGN KEY (periodo_id) REFERENCES periodo(id),
  CONSTRAINT fk_cc2_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id),
  CONSTRAINT fk_cc2_filial FOREIGN KEY (filial_id) REFERENCES filial(id),
  CONSTRAINT fk_cc2_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuario(id)
) ENGINE=InnoDB;

-- ============================================================
-- MÓDULO — Corrida dos Campeões (trimestral, separado do núcleo)
-- ============================================================

CREATE TABLE corrida_edicao (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  trimestre     TINYINT UNSIGNED NOT NULL,
  ano           SMALLINT UNSIGNED NOT NULL,
  data_inicio   DATE NOT NULL,
  data_fim      DATE NOT NULL,
  status        ENUM('planejamento','aberta','fechada') NOT NULL DEFAULT 'planejamento',
  UNIQUE KEY uq_corrida_trimestre_ano (trimestre, ano)
) ENGINE=InnoDB;

CREATE TABLE corrida_grupo (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  edicao_id   INT UNSIGNED NOT NULL,
  nome        VARCHAR(120) NOT NULL,
  CONSTRAINT fk_cg_edicao FOREIGN KEY (edicao_id) REFERENCES corrida_edicao(id)
) ENGINE=InnoDB;

CREATE TABLE corrida_premio_faixa (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id    INT UNSIGNED NOT NULL,
  colocacao   TINYINT UNSIGNED NOT NULL,  -- 1 a 5
  valor       DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_premio_grupo_colocacao (grupo_id, colocacao),
  CONSTRAINT fk_cpf_grupo FOREIGN KEY (grupo_id) REFERENCES corrida_grupo(id)
) ENGINE=InnoDB;

CREATE TABLE corrida_lancamento (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id        INT UNSIGNED NOT NULL,
  funcionario_id  INT UNSIGNED NOT NULL,
  valor_vendido   DECIMAL(12,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_corrida_lanc (grupo_id, funcionario_id),
  CONSTRAINT fk_cl_grupo FOREIGN KEY (grupo_id) REFERENCES corrida_grupo(id),
  CONSTRAINT fk_cl_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id)
) ENGINE=InnoDB;

-- ============================================================
-- AUDITORIA
-- ============================================================

CREATE TABLE log_auditoria (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NULL,
  acao        VARCHAR(80) NOT NULL,
  entidade    VARCHAR(60) NOT NULL,
  entidade_id INT UNSIGNED NULL,
  detalhes    TEXT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_log_entidade (entidade, entidade_id),
  CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED — dados reais do briefing (não é dado de exemplo)
-- ============================================================

INSERT INTO filial (nome, codigo) VALUES
  ('Centro', 'centro'),
  ('Floresta', 'floresta'),
  ('Frai 1', 'frai1'),
  ('Frai 2', 'frai2'),
  ('Laboratório', 'laboratorio'),
  ('Pinheiro Preto', 'pinheiro-preto'),
  ('Saul', 'saul');

-- Usuário admin inicial. Senha temporária: Geremias@2026 — troque no primeiro login.
INSERT INTO usuario (email, senha_hash, papel) VALUES
  ('evandro.bariviera@gmail.com', '$2y$12$uTWy.queIIBDeldWk2BFIugCx1fuVikhJw231pbUGzcJbx9u91LPW', 'admin');

INSERT INTO categoria (nome, ordem) VALUES
  ('Similar', 1),
  ('Genérico', 2),
  ('Perfumaria', 3),
  ('Manipulação', 4),
  ('RX', 5),
  ('Conveniência', 6),
  ('Correlatos', 7);

-- Faixas de comissão (briefing §2.2) — o percentual da faixa incide sobre o
-- valor TOTAL vendido na categoria (confirmado na planilha simuladora), não é progressivo por degrau.
INSERT INTO faixa_comissao (categoria_id, ordem, limite_ate, percentual)
SELECT id, 1, 3000.00, 1.00 FROM categoria WHERE nome = 'Similar'
UNION ALL SELECT id, 2, 7000.00, 3.00 FROM categoria WHERE nome = 'Similar'
UNION ALL SELECT id, 3, 10000.00, 4.00 FROM categoria WHERE nome = 'Similar'
UNION ALL SELECT id, 4, NULL, 6.00 FROM categoria WHERE nome = 'Similar'

UNION ALL SELECT id, 1, 4000.00, 1.00 FROM categoria WHERE nome = 'Genérico'
UNION ALL SELECT id, 2, 5800.00, 2.00 FROM categoria WHERE nome = 'Genérico'
UNION ALL SELECT id, 3, 8000.00, 3.00 FROM categoria WHERE nome = 'Genérico'
UNION ALL SELECT id, 4, NULL, 5.00 FROM categoria WHERE nome = 'Genérico'

UNION ALL SELECT id, 1, 4500.00, 1.00 FROM categoria WHERE nome = 'Perfumaria'
UNION ALL SELECT id, 2, 8500.00, 3.00 FROM categoria WHERE nome = 'Perfumaria'
UNION ALL SELECT id, 3, 10000.00, 4.00 FROM categoria WHERE nome = 'Perfumaria'
UNION ALL SELECT id, 4, NULL, 6.00 FROM categoria WHERE nome = 'Perfumaria'

UNION ALL SELECT id, 1, 5000.00, 1.00 FROM categoria WHERE nome = 'Manipulação'
UNION ALL SELECT id, 2, 8500.00, 2.00 FROM categoria WHERE nome = 'Manipulação'
UNION ALL SELECT id, 3, 15000.00, 4.00 FROM categoria WHERE nome = 'Manipulação'
UNION ALL SELECT id, 4, NULL, 6.00 FROM categoria WHERE nome = 'Manipulação'

UNION ALL SELECT id, 1, 12000.00, 1.00 FROM categoria WHERE nome = 'RX'
UNION ALL SELECT id, 2, 16000.00, 2.00 FROM categoria WHERE nome = 'RX'
UNION ALL SELECT id, 3, 20000.00, 3.00 FROM categoria WHERE nome = 'RX'
UNION ALL SELECT id, 4, NULL, 4.00 FROM categoria WHERE nome = 'RX'

UNION ALL SELECT id, 1, 2000.00, 1.00 FROM categoria WHERE nome = 'Conveniência'
UNION ALL SELECT id, 2, 3500.00, 2.00 FROM categoria WHERE nome = 'Conveniência'
UNION ALL SELECT id, 3, 5000.00, 4.00 FROM categoria WHERE nome = 'Conveniência'
UNION ALL SELECT id, 4, NULL, 6.00 FROM categoria WHERE nome = 'Conveniência'

UNION ALL SELECT id, 1, 1000.00, 1.00 FROM categoria WHERE nome = 'Correlatos'
UNION ALL SELECT id, 2, 1900.00, 2.00 FROM categoria WHERE nome = 'Correlatos'
UNION ALL SELECT id, 3, 3000.00, 3.00 FROM categoria WHERE nome = 'Correlatos'
UNION ALL SELECT id, 4, NULL, 6.00 FROM categoria WHERE nome = 'Correlatos';

-- Regra especial do briefing: Manipulação comissiona sobre Manipulação + S/N somados.
INSERT INTO categoria_composicao (categoria_regra_id, fonte_tipo, fonte_flag)
SELECT id, 'flag_venda', 'eh_sn' FROM categoria WHERE nome = 'Manipulação';

-- Parâmetros do Bloco 2 — Meta 360º (briefing §2.3)
INSERT INTO parametro (chave, valor, descricao) VALUES
  ('desconto_piso_pct', '12', 'Desconto médio (%) que dá a pontuação cheia do sub-pilar desconto'),
  ('desconto_teto_pct', '25', 'Desconto médio (%) que zera o sub-pilar desconto'),
  ('desconto_pts_max', '6', 'Pontos máximos do sub-pilar desconto médio'),
  ('rentab_filial_pts', '7', 'Pontos do sub-pilar rentabilidade da filial (liga/desliga)'),
  ('rentab_funcionario_pts', '7', 'Pontos do sub-pilar rentabilidade do funcionário (liga/desliga)'),
  ('meta_rentab_individual_pct', '28', 'Meta de rentabilidade individual (%) — parâmetro global, usado no sub-pilar rentabilidade do funcionário'),
  ('peso_individual_max', '40', 'Pontuação máxima do pilar Resultado Individual'),
  ('peso_filial_max', '30', 'Pontuação máxima do pilar Resultado da Filial'),
  ('peso_qualidade_max', '20', 'Pontuação máxima do pilar Qualidade/Rentabilidade'),
  ('peso_equipe_max', '10', 'Pontuação máxima do pilar Equipe'),
  ('premio_filial_padrao', '250.00', 'Valor de referência do prêmio de filial (editável por filial em meta_filial)');

-- Tabela de multiplicador (briefing §2.3)
INSERT INTO multiplicador_faixa (pontos_de, pontos_ate, nivel, multiplicador) VALUES
  (0, 59, 'Em desenvolvimento', 1.00),
  (60, 69, 'Bronze', 0.50),
  (70, 79, 'Prata', 0.75),
  (80, 89, 'Platina', 1.00),
  (90, 94, 'Diamante', 1.20),
  (95, 100, 'Ouro', 1.50);
