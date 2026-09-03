-- Corrida dos Campeões — parte 2: produtos com bônus por unidade (2026-09-02)
--
-- Regras (confirmadas com o cliente):
--  - Catálogo de produtos reutilizável entre edições + participação configurada por edição
--    (R$ por unidade e grupo vinculado mudam a cada edição, editáveis a qualquer momento).
--  - Produto vinculado a um grupo: o valor vendido SOMA ao valor digitado na grade daquele
--    grupo (entra no ranking). Produto solto: não mexe em ranking nenhum.
--  - Bônus por unidade = quantidade x R$/unidade, pago a TODO funcionário que vendeu,
--    independente de posição — para produtos vinculados e soltos.

SET FOREIGN_KEY_CHECKS = 0;

-- Catálogo global de produtos.
CREATE TABLE corrida_produto (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(120) NOT NULL,
  unidade_rotulo VARCHAR(20)  NOT NULL DEFAULT 'unidade',  -- "caixa", "frasco", "unidade"...
  ativo          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_corrida_produto_nome (nome)
) ENGINE=InnoDB;

-- Participação de um produto numa edição.
CREATE TABLE corrida_edicao_produto (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  edicao_id      INT UNSIGNED NOT NULL,
  produto_id     INT UNSIGNED NOT NULL,
  grupo_id       INT UNSIGNED NULL,               -- NULL = produto "solto" (só bônus, não conta pro ranking)
  bonus_unidade  DECIMAL(10,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_cep (edicao_id, produto_id),
  KEY ix_cep_grupo (grupo_id),
  CONSTRAINT fk_cep_edicao  FOREIGN KEY (edicao_id)  REFERENCES corrida_edicao(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cep_produto FOREIGN KEY (produto_id) REFERENCES corrida_produto(id),
  CONSTRAINT fk_cep_grupo   FOREIGN KEY (grupo_id)   REFERENCES corrida_grupo(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- Grade: quanto cada funcionário vendeu de cada produto participante (acumulado, sobrescrito).
CREATE TABLE corrida_lancamento_produto (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  edicao_produto_id INT UNSIGNED NOT NULL,
  funcionario_id    INT UNSIGNED NOT NULL,
  quantidade        DECIMAL(12,2) NOT NULL DEFAULT 0,
  valor             DECIMAL(12,2) NOT NULL DEFAULT 0,  -- R$ vendido; obrigatório na tela quando o produto tem grupo
  atualizado_em     DATETIME NULL,
  atualizado_por    INT UNSIGNED NULL,
  UNIQUE KEY uq_clp (edicao_produto_id, funcionario_id),
  CONSTRAINT fk_clp_ep          FOREIGN KEY (edicao_produto_id) REFERENCES corrida_edicao_produto(id) ON DELETE CASCADE,
  CONSTRAINT fk_clp_funcionario FOREIGN KEY (funcionario_id)    REFERENCES funcionario(id),
  CONSTRAINT fk_clp_atualizador FOREIGN KEY (atualizado_por)    REFERENCES usuario(id)
) ENGINE=InnoDB;

-- Snapshot do bônus por unidade no fechamento da edição (total por funcionário).
CREATE TABLE corrida_resultado_bonus (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  edicao_id        INT UNSIGNED NOT NULL,
  funcionario_id   INT UNSIGNED NOT NULL,
  quantidade_total DECIMAL(12,2) NOT NULL,
  valor_bonus      DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_crb (edicao_id, funcionario_id),
  CONSTRAINT fk_crb_edicao      FOREIGN KEY (edicao_id)      REFERENCES corrida_edicao(id) ON DELETE CASCADE,
  CONSTRAINT fk_crb_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
