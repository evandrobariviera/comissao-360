-- Corrida dos Campeões — ajuste do esqueleto para a v1 (2026-09-02)
-- Decisões: rateio por pesos lineares 5-4-3-2-1 com denominador fixo 15 (posição
-- vazia não é paga); lançamento central pelo admin (grade funcionário x grupo,
-- valor acumulado sobrescrevível); ranking geral só de exibição (trimestre/semestre/ano).

SET FOREIGN_KEY_CHECKS = 0;

-- Edição: rótulo opcional + rastro de criação/fechamento (espelha fechamento_filial).
ALTER TABLE corrida_edicao
  ADD COLUMN nome        VARCHAR(120) NULL AFTER ano,
  ADD COLUMN criado_por  INT UNSIGNED NULL AFTER status,
  ADD COLUMN fechada_em  DATETIME     NULL AFTER criado_por,
  ADD COLUMN fechada_por INT UNSIGNED NULL AFTER fechada_em,
  ADD CONSTRAINT fk_ce_criador  FOREIGN KEY (criado_por)  REFERENCES usuario(id),
  ADD CONSTRAINT fk_ce_fechador FOREIGN KEY (fechada_por) REFERENCES usuario(id);

-- Grupo (linha/classe premiada): prêmio bruto ("pool" a ratear) + ordem de exibição.
ALTER TABLE corrida_grupo
  ADD COLUMN premio_bruto DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER nome,
  ADD COLUMN ordem        INT           NOT NULL DEFAULT 0 AFTER premio_bruto,
  DROP FOREIGN KEY fk_cg_edicao,
  ADD CONSTRAINT fk_cg_edicao FOREIGN KEY (edicao_id) REFERENCES corrida_edicao(id) ON DELETE CASCADE;

-- Com pesos lineares fixos, o valor por colocação é calculado — não configurado.
DROP TABLE IF EXISTS corrida_premio_faixa;

-- Lançamento (grade funcionário x grupo, valor acumulado, sobrescrito a cada atualização).
ALTER TABLE corrida_lancamento
  ADD COLUMN atualizado_em  DATETIME     NULL AFTER valor_vendido,
  ADD COLUMN atualizado_por INT UNSIGNED NULL AFTER atualizado_em,
  ADD CONSTRAINT fk_cl_atualizador FOREIGN KEY (atualizado_por) REFERENCES usuario(id),
  DROP FOREIGN KEY fk_cl_grupo,
  ADD CONSTRAINT fk_cl_grupo FOREIGN KEY (grupo_id) REFERENCES corrida_grupo(id) ON DELETE CASCADE;

-- Snapshot gravado no fechamento da edição (mesma filosofia de comissao_calculada):
-- congela colocação, valor e prêmio de cada premiado por grupo.
CREATE TABLE corrida_resultado (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  edicao_id      INT UNSIGNED NOT NULL,
  grupo_id       INT UNSIGNED NOT NULL,
  funcionario_id INT UNSIGNED NOT NULL,
  colocacao      TINYINT UNSIGNED NOT NULL,
  valor_vendido  DECIMAL(12,2) NOT NULL,
  premio         DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_corrida_resultado (grupo_id, funcionario_id),
  KEY ix_corrida_resultado_edicao (edicao_id),
  CONSTRAINT fk_cr_edicao FOREIGN KEY (edicao_id) REFERENCES corrida_edicao(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_grupo  FOREIGN KEY (grupo_id)  REFERENCES corrida_grupo(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cr_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionario(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
