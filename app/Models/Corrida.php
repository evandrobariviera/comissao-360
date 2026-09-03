<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\CorridaCalculator;
use PDO;
use Throwable;

/**
 * Módulo Corrida dos Campeões — edições trimestrais, grupos premiados, grade de
 * lançamento (funcionário x grupo) e snapshot de resultados no fechamento.
 *
 * Desacoplado do `periodo` mensal: a edição tem suas próprias datas e seu próprio
 * seletor nas telas de /corrida (não usa Periodo::ativo()).
 */
final class Corrida
{
    // ----------------------------------------------------------------
    // Edições
    // ----------------------------------------------------------------

    /** @return list<array<string,mixed>> mais recente primeiro */
    public static function edicoes(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM corrida_edicao ORDER BY ano DESC, trimestre DESC'
        )->fetchAll();
    }

    public static function edicao(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM corrida_edicao WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Edição que as telas abrem por padrão: a mais recente com status 'aberta', senão a mais recente. */
    public static function edicaoPadrao(): ?array
    {
        $aberta = Database::pdo()->query(
            "SELECT * FROM corrida_edicao WHERE status = 'aberta' ORDER BY ano DESC, trimestre DESC LIMIT 1"
        )->fetch();
        if ($aberta !== false) {
            return $aberta;
        }

        $qualquer = Database::pdo()->query(
            'SELECT * FROM corrida_edicao ORDER BY ano DESC, trimestre DESC LIMIT 1'
        )->fetch();

        return $qualquer === false ? null : $qualquer;
    }

    public static function criarEdicao(
        int $trimestre,
        int $ano,
        ?string $nome,
        string $dataInicio,
        string $dataFim,
        int $criadoPor
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO corrida_edicao (trimestre, ano, nome, data_inicio, data_fim, status, criado_por)
             VALUES (:trimestre, :ano, :nome, :data_inicio, :data_fim, :status, :criado_por)'
        );
        $stmt->execute([
            'trimestre' => $trimestre,
            'ano' => $ano,
            'nome' => $nome !== '' ? $nome : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'status' => 'aberta',
            'criado_por' => $criadoPor,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function atualizarEdicao(
        int $id,
        int $trimestre,
        int $ano,
        ?string $nome,
        string $dataInicio,
        string $dataFim
    ): void {
        Database::pdo()->prepare(
            'UPDATE corrida_edicao
                SET trimestre = :trimestre, ano = :ano, nome = :nome,
                    data_inicio = :data_inicio, data_fim = :data_fim
              WHERE id = :id'
        )->execute([
            'trimestre' => $trimestre,
            'ano' => $ano,
            'nome' => $nome !== '' ? $nome : null,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'id' => $id,
        ]);
    }

    /** Só remove edição não fechada — o cascade limpa grupos, lançamentos e resultados. */
    public static function excluirEdicao(int $id): void
    {
        Database::pdo()
            ->prepare("DELETE FROM corrida_edicao WHERE id = :id AND status <> 'fechada'")
            ->execute(['id' => $id]);
    }

    public static function estaAberta(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT status FROM corrida_edicao WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() === 'aberta';
    }

    /**
     * Fecha a edição: calcula o ranking/rateio de cada grupo e grava o snapshot
     * em corrida_resultado, depois trava o status. Idempotente por transação.
     */
    public static function fecharEdicao(int $id, int $usuarioId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM corrida_resultado WHERE edicao_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM corrida_resultado_bonus WHERE edicao_id = :id')->execute(['id' => $id]);

            $insert = $pdo->prepare(
                'INSERT INTO corrida_resultado
                    (edicao_id, grupo_id, funcionario_id, colocacao, valor_vendido, premio)
                 VALUES (:edicao_id, :grupo_id, :funcionario_id, :colocacao, :valor_vendido, :premio)'
            );

            foreach (self::grupos($id) as $grupo) {
                $ranking = CorridaCalculator::rankingGrupo(
                    self::lancamentosDoGrupo((int) $grupo['id']),
                    (float) $grupo['premio_bruto']
                );
                foreach ($ranking as $linha) {
                    if (!$linha['premiado']) {
                        continue;
                    }
                    $insert->execute([
                        'edicao_id' => $id,
                        'grupo_id' => (int) $grupo['id'],
                        'funcionario_id' => $linha['funcionario_id'],
                        'colocacao' => $linha['colocacao'],
                        'valor_vendido' => $linha['valor_vendido'],
                        'premio' => $linha['premio'],
                    ]);
                }
            }

            // Snapshot do bônus por unidade (pago a todo mundo que vendeu, sem depender de posição).
            $insBonus = $pdo->prepare(
                'INSERT INTO corrida_resultado_bonus
                    (edicao_id, funcionario_id, quantidade_total, valor_bonus)
                 VALUES (:edicao_id, :funcionario_id, :quantidade_total, :valor_bonus)'
            );
            foreach (self::bonusPorFuncionario($id) as $linha) {
                if ($linha['valor_bonus'] <= 0.0) {
                    continue;
                }
                $insBonus->execute([
                    'edicao_id' => $id,
                    'funcionario_id' => $linha['funcionario_id'],
                    'quantidade_total' => $linha['quantidade_total'],
                    'valor_bonus' => $linha['valor_bonus'],
                ]);
            }

            $pdo->prepare(
                "UPDATE corrida_edicao
                    SET status = 'fechada', fechada_em = NOW(), fechada_por = :usuario_id
                  WHERE id = :id"
            )->execute(['usuario_id' => $usuarioId, 'id' => $id]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Reabre a edição: apaga o snapshot; o ranking volta a ser calculado ao vivo. */
    public static function reabrirEdicao(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM corrida_resultado WHERE edicao_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM corrida_resultado_bonus WHERE edicao_id = :id')->execute(['id' => $id]);
            $pdo->prepare(
                "UPDATE corrida_edicao SET status = 'aberta', fechada_em = NULL, fechada_por = NULL WHERE id = :id"
            )->execute(['id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // Grupos
    // ----------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public static function grupos(int $edicaoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM corrida_grupo WHERE edicao_id = :edicao_id ORDER BY ordem, id'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        return $stmt->fetchAll();
    }

    public static function grupo(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM corrida_grupo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function criarGrupo(int $edicaoId, string $nome, float $premioBruto): int
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM corrida_grupo WHERE edicao_id = :e');
        $stmt->execute(['e' => $edicaoId]);
        $ordem = (int) $stmt->fetchColumn();

        $stmt = Database::pdo()->prepare(
            'INSERT INTO corrida_grupo (edicao_id, nome, premio_bruto, ordem)
             VALUES (:edicao_id, :nome, :premio_bruto, :ordem)'
        );
        $stmt->execute([
            'edicao_id' => $edicaoId,
            'nome' => $nome,
            'premio_bruto' => $premioBruto,
            'ordem' => $ordem,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function atualizarGrupo(int $id, string $nome, float $premioBruto): void
    {
        Database::pdo()->prepare(
            'UPDATE corrida_grupo SET nome = :nome, premio_bruto = :premio_bruto WHERE id = :id'
        )->execute(['nome' => $nome, 'premio_bruto' => $premioBruto, 'id' => $id]);
    }

    public static function excluirGrupo(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM corrida_grupo WHERE id = :id')->execute(['id' => $id]);
    }

    // ----------------------------------------------------------------
    // Grade de lançamento (funcionário x grupo)
    // ----------------------------------------------------------------

    /**
     * Grade [funcionario_id][grupo_id] => valor_vendido, com 0.0 para célula ainda não lançada.
     *
     * @param list<int> $funcionarioIds
     * @param list<int> $grupoIds
     * @return array<int, array<int, float>>
     */
    public static function grade(array $funcionarioIds, array $grupoIds, int $edicaoId): array
    {
        $mapa = [];
        foreach ($funcionarioIds as $fid) {
            foreach ($grupoIds as $gid) {
                $mapa[$fid][$gid] = 0.0;
            }
        }
        if (empty($grupoIds)) {
            return $mapa;
        }

        $placeholders = implode(',', array_fill(0, count($grupoIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT funcionario_id, grupo_id, valor_vendido
               FROM corrida_lancamento
              WHERE grupo_id IN ($placeholders)"
        );
        $stmt->execute($grupoIds);
        foreach ($stmt->fetchAll() as $row) {
            $fid = (int) $row['funcionario_id'];
            $gid = (int) $row['grupo_id'];
            if (isset($mapa[$fid][$gid])) {
                $mapa[$fid][$gid] = (float) $row['valor_vendido'];
            }
        }

        return $mapa;
    }

    /**
     * Sobrescreve a grade inteira (mecânica igual à grade por funcionário em /vendas):
     * cada célula é o total acumulado atual, não um lançamento incremental.
     *
     * @param array<int, array<int, float>> $valores [funcionario_id][grupo_id] => valor
     */
    public static function salvarGrade(array $valores, int $usuarioId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO corrida_lancamento (grupo_id, funcionario_id, valor_vendido, atualizado_em, atualizado_por)
                 VALUES (:grupo_id, :funcionario_id, :valor, NOW(), :usuario_id)
                 ON DUPLICATE KEY UPDATE
                    valor_vendido = VALUES(valor_vendido),
                    atualizado_em = VALUES(atualizado_em),
                    atualizado_por = VALUES(atualizado_por)'
            );
            foreach ($valores as $funcionarioId => $porGrupo) {
                foreach ($porGrupo as $grupoId => $valor) {
                    $stmt->execute([
                        'grupo_id' => (int) $grupoId,
                        'funcionario_id' => (int) $funcionarioId,
                        'valor' => (float) $valor,
                        'usuario_id' => $usuarioId,
                    ]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // Consultas para ranking (ao vivo)
    // ----------------------------------------------------------------

    /** Subconsulta que resolve a filial principal do funcionário (fallback: a primeira vinculada). */
    private const FILIAL_SQL = "COALESCE(
        (SELECT filial_id FROM funcionario_filial WHERE funcionario_id = f.id AND principal = 1 LIMIT 1),
        (SELECT filial_id FROM funcionario_filial WHERE funcionario_id = f.id ORDER BY filial_id LIMIT 1)
    )";

    /**
     * Valor que conta pro ranking de um grupo, por funcionário: a grade do grupo MAIS o valor
     * vendido dos produtos vinculados àquele grupo (ver [[corrida-campeoes]] parte 2).
     * @return list<array{funcionario_id:int, nome:string, filial_id:?int, valor_vendido:float}>
     */
    public static function lancamentosDoGrupo(int $grupoId): array
    {
        $pdo = Database::pdo();
        $acc = [];

        $add = static function (array &$acc, array $r): void {
            $fid = (int) $r['funcionario_id'];
            if (!isset($acc[$fid])) {
                $acc[$fid] = [
                    'funcionario_id' => $fid,
                    'nome' => (string) $r['nome'],
                    'filial_id' => $r['filial_id'] !== null ? (int) $r['filial_id'] : null,
                    'valor_vendido' => 0.0,
                ];
            }
            $acc[$fid]['valor_vendido'] += (float) $r['valor'];
        };

        $stmt = $pdo->prepare(
            'SELECT cl.funcionario_id, f.nome, cl.valor_vendido AS valor, ' . self::FILIAL_SQL . ' AS filial_id
               FROM corrida_lancamento cl
               JOIN funcionario f ON f.id = cl.funcionario_id
              WHERE cl.grupo_id = :grupo_id'
        );
        $stmt->execute(['grupo_id' => $grupoId]);
        foreach ($stmt->fetchAll() as $r) {
            $add($acc, $r);
        }

        $stmt = $pdo->prepare(
            'SELECT clp.funcionario_id, f.nome, clp.valor AS valor, ' . self::FILIAL_SQL . ' AS filial_id
               FROM corrida_lancamento_produto clp
               JOIN corrida_edicao_produto cep ON cep.id = clp.edicao_produto_id
               JOIN funcionario f ON f.id = clp.funcionario_id
              WHERE cep.grupo_id = :grupo_id'
        );
        $stmt->execute(['grupo_id' => $grupoId]);
        foreach ($stmt->fetchAll() as $r) {
            $add($acc, $r);
        }

        return array_values($acc);
    }

    /**
     * Valor acumulado por funcionário nas edições dadas (para o ranking geral): soma de todos
     * os grupos + valor dos produtos VINCULADOS a grupo. Produto solto não entra em ranking.
     * @param list<int> $edicaoIds
     * @return list<array{funcionario_id:int, nome:string, filial_id:?int, valor_vendido:float}>
     */
    public static function lancamentosDasEdicoes(array $edicaoIds): array
    {
        $edicaoIds = array_values(array_filter(array_map('intval', $edicaoIds)));
        if (empty($edicaoIds)) {
            return [];
        }

        $pdo = Database::pdo();
        $placeholders = implode(',', array_fill(0, count($edicaoIds), '?'));
        $acc = [];

        $add = static function (array &$acc, array $r): void {
            $fid = (int) $r['funcionario_id'];
            if (!isset($acc[$fid])) {
                $acc[$fid] = [
                    'funcionario_id' => $fid,
                    'nome' => (string) $r['nome'],
                    'filial_id' => $r['filial_id'] !== null ? (int) $r['filial_id'] : null,
                    'valor_vendido' => 0.0,
                ];
            }
            $acc[$fid]['valor_vendido'] += (float) $r['valor'];
        };

        $stmt = $pdo->prepare(
            "SELECT cl.funcionario_id, f.nome, cl.valor_vendido AS valor, " . self::FILIAL_SQL . " AS filial_id
               FROM corrida_lancamento cl
               JOIN corrida_grupo cg ON cg.id = cl.grupo_id
               JOIN funcionario f ON f.id = cl.funcionario_id
              WHERE cg.edicao_id IN ($placeholders)"
        );
        $stmt->execute($edicaoIds);
        foreach ($stmt->fetchAll() as $r) {
            $add($acc, $r);
        }

        $stmt = $pdo->prepare(
            "SELECT clp.funcionario_id, f.nome, clp.valor AS valor, " . self::FILIAL_SQL . " AS filial_id
               FROM corrida_lancamento_produto clp
               JOIN corrida_edicao_produto cep ON cep.id = clp.edicao_produto_id
               JOIN funcionario f ON f.id = clp.funcionario_id
              WHERE cep.edicao_id IN ($placeholders) AND cep.grupo_id IS NOT NULL"
        );
        $stmt->execute($edicaoIds);
        foreach ($stmt->fetchAll() as $r) {
            $add($acc, $r);
        }

        return array_values($acc);
    }

    // ----------------------------------------------------------------
    // Produtos com bônus por unidade (parte 2)
    // ----------------------------------------------------------------

    /** Catálogo de produtos ativos, reutilizável entre edições. */
    public static function produtosCatalogo(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM corrida_produto WHERE ativo = 1 ORDER BY nome'
        )->fetchAll();
    }

    /** Cria (ou reaproveita, por nome) um produto no catálogo e devolve o id. */
    public static function criarOuAcharProduto(string $nome, string $unidadeRotulo): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM corrida_produto WHERE nome = :nome');
        $stmt->execute(['nome' => $nome]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            $pdo->prepare('UPDATE corrida_produto SET ativo = 1, unidade_rotulo = :u WHERE id = :id')
                ->execute(['u' => $unidadeRotulo, 'id' => (int) $id]);

            return (int) $id;
        }

        $pdo->prepare('INSERT INTO corrida_produto (nome, unidade_rotulo) VALUES (:nome, :u)')
            ->execute(['nome' => $nome, 'u' => $unidadeRotulo]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Produtos que participam de uma edição, com o vínculo de grupo e o valor por unidade.
     * @return list<array<string,mixed>>
     */
    public static function produtosDaEdicao(int $edicaoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT cep.id, cep.produto_id, cep.grupo_id, cep.bonus_unidade,
                    p.nome, p.unidade_rotulo, cg.nome AS grupo_nome
               FROM corrida_edicao_produto cep
               JOIN corrida_produto p ON p.id = cep.produto_id
               LEFT JOIN corrida_grupo cg ON cg.id = cep.grupo_id
              WHERE cep.edicao_id = :edicao_id
              ORDER BY p.nome'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        return $stmt->fetchAll();
    }

    public static function edicaoProduto(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM corrida_edicao_produto WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Adiciona um produto do catálogo a uma edição. $grupoId null = produto solto. */
    public static function adicionarProdutoEdicao(int $edicaoId, int $produtoId, ?int $grupoId, float $bonusUnidade): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO corrida_edicao_produto (edicao_id, produto_id, grupo_id, bonus_unidade)
             VALUES (:edicao_id, :produto_id, :grupo_id, :bonus)'
        );
        $stmt->execute([
            'edicao_id' => $edicaoId,
            'produto_id' => $produtoId,
            'grupo_id' => $grupoId,
            'bonus' => $bonusUnidade,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function atualizarProdutoEdicao(int $id, ?int $grupoId, float $bonusUnidade): void
    {
        Database::pdo()->prepare(
            'UPDATE corrida_edicao_produto SET grupo_id = :grupo_id, bonus_unidade = :bonus WHERE id = :id'
        )->execute(['grupo_id' => $grupoId, 'bonus' => $bonusUnidade, 'id' => $id]);
    }

    public static function removerProdutoEdicao(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM corrida_edicao_produto WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Grade [funcionario_id][edicao_produto_id] => ['quantidade'=>float, 'valor'=>float].
     * @param list<int> $funcionarioIds
     * @param list<int> $edicaoProdutoIds
     * @return array<int, array<int, array{quantidade:float, valor:float}>>
     */
    public static function gradeProdutos(array $funcionarioIds, array $edicaoProdutoIds): array
    {
        $mapa = [];
        foreach ($funcionarioIds as $fid) {
            foreach ($edicaoProdutoIds as $epid) {
                $mapa[$fid][$epid] = ['quantidade' => 0.0, 'valor' => 0.0];
            }
        }
        if (empty($edicaoProdutoIds)) {
            return $mapa;
        }

        $placeholders = implode(',', array_fill(0, count($edicaoProdutoIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT funcionario_id, edicao_produto_id, quantidade, valor
               FROM corrida_lancamento_produto
              WHERE edicao_produto_id IN ($placeholders)"
        );
        $stmt->execute($edicaoProdutoIds);
        foreach ($stmt->fetchAll() as $row) {
            $fid = (int) $row['funcionario_id'];
            $epid = (int) $row['edicao_produto_id'];
            if (isset($mapa[$fid][$epid])) {
                $mapa[$fid][$epid] = ['quantidade' => (float) $row['quantidade'], 'valor' => (float) $row['valor']];
            }
        }

        return $mapa;
    }

    /**
     * Sobrescreve a grade de produtos inteira.
     * @param array<int, array<int, array{quantidade:float, valor:float}>> $valores
     */
    public static function salvarGradeProdutos(array $valores, int $usuarioId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO corrida_lancamento_produto
                    (edicao_produto_id, funcionario_id, quantidade, valor, atualizado_em, atualizado_por)
                 VALUES (:ep, :funcionario_id, :quantidade, :valor, NOW(), :usuario_id)
                 ON DUPLICATE KEY UPDATE
                    quantidade = VALUES(quantidade),
                    valor = VALUES(valor),
                    atualizado_em = VALUES(atualizado_em),
                    atualizado_por = VALUES(atualizado_por)'
            );
            foreach ($valores as $funcionarioId => $porProduto) {
                foreach ($porProduto as $epId => $cel) {
                    $stmt->execute([
                        'ep' => (int) $epId,
                        'funcionario_id' => (int) $funcionarioId,
                        'quantidade' => (float) $cel['quantidade'],
                        'valor' => (float) $cel['valor'],
                        'usuario_id' => $usuarioId,
                    ]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Bônus por unidade por funcionário (ao vivo): soma de quantidade x bonus_unidade em todos
     * os produtos participantes (vinculados ou soltos). Pago a quem vendeu, sem depender de posição.
     * @return list<array{funcionario_id:int, nome:string, filial_id:?int, quantidade_total:float, valor_bonus:float}>
     */
    public static function bonusPorFuncionario(int $edicaoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT clp.funcionario_id, f.nome, ' . self::FILIAL_SQL . ' AS filial_id,
                    clp.quantidade, cep.bonus_unidade
               FROM corrida_lancamento_produto clp
               JOIN corrida_edicao_produto cep ON cep.id = clp.edicao_produto_id
               JOIN funcionario f ON f.id = clp.funcionario_id
              WHERE cep.edicao_id = :edicao_id'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        $acc = [];
        foreach ($stmt->fetchAll() as $r) {
            $fid = (int) $r['funcionario_id'];
            if (!isset($acc[$fid])) {
                $acc[$fid] = [
                    'funcionario_id' => $fid,
                    'nome' => (string) $r['nome'],
                    'filial_id' => $r['filial_id'] !== null ? (int) $r['filial_id'] : null,
                    'quantidade_total' => 0.0,
                    'valor_bonus' => 0.0,
                ];
            }
            $acc[$fid]['quantidade_total'] += (float) $r['quantidade'];
            $acc[$fid]['valor_bonus'] += (float) $r['quantidade'] * (float) $r['bonus_unidade'];
        }

        foreach ($acc as &$linha) {
            $linha['valor_bonus'] = round($linha['valor_bonus'], 2);
        }
        unset($linha);

        usort($acc, static fn (array $a, array $b): int => $b['valor_bonus'] <=> $a['valor_bonus'] ?: strcmp($a['nome'], $b['nome']));

        return array_values($acc);
    }

    /** Bônus por unidade congelado numa edição fechada (mesma forma de bonusPorFuncionario). */
    public static function resultadoBonus(int $edicaoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT crb.funcionario_id, f.nome, ' . self::FILIAL_SQL . ' AS filial_id,
                    crb.quantidade_total, crb.valor_bonus
               FROM corrida_resultado_bonus crb
               JOIN funcionario f ON f.id = crb.funcionario_id
              WHERE crb.edicao_id = :edicao_id
              ORDER BY crb.valor_bonus DESC, f.nome'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        return array_map(static fn (array $r): array => [
            'funcionario_id' => (int) $r['funcionario_id'],
            'nome' => (string) $r['nome'],
            'filial_id' => $r['filial_id'] !== null ? (int) $r['filial_id'] : null,
            'quantidade_total' => (float) $r['quantidade_total'],
            'valor_bonus' => (float) $r['valor_bonus'],
        ], $stmt->fetchAll());
    }

    public static function totalBonus(int $edicaoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(valor_bonus), 0) FROM corrida_resultado_bonus WHERE edicao_id = :edicao_id'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Ids das edições no mesmo semestre / ano da edição de referência
     * (semestre 1 = trimestres 1-2, semestre 2 = trimestres 3-4).
     * @return array{semestre: list<int>, ano: list<int>}
     */
    public static function edicoesRelacionadas(array $edicao): array
    {
        $ano = (int) $edicao['ano'];
        $semestre = (int) $edicao['trimestre'] <= 2 ? [1, 2] : [3, 4];

        $stmt = Database::pdo()->prepare(
            'SELECT id, trimestre FROM corrida_edicao WHERE ano = :ano'
        );
        $stmt->execute(['ano' => $ano]);

        $doAno = [];
        $doSemestre = [];
        foreach ($stmt->fetchAll() as $row) {
            $doAno[] = (int) $row['id'];
            if (in_array((int) $row['trimestre'], $semestre, true)) {
                $doSemestre[] = (int) $row['id'];
            }
        }

        return ['semestre' => $doSemestre, 'ano' => $doAno];
    }

    /**
     * Snapshot de resultados de uma edição fechada, por grupo.
     * @return array<int, list<array<string,mixed>>> grupo_id => linhas (colocação asc)
     */
    public static function resultadosPorGrupo(int $edicaoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT cr.*, f.nome
               FROM corrida_resultado cr
               JOIN funcionario f ON f.id = cr.funcionario_id
              WHERE cr.edicao_id = :edicao_id
              ORDER BY cr.grupo_id, cr.colocacao, f.nome'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['grupo_id']][] = $row;
        }

        return $mapa;
    }

    /** Total de prêmio já congelado numa edição fechada. */
    public static function totalPremiado(int $edicaoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(premio), 0) FROM corrida_resultado WHERE edicao_id = :edicao_id'
        );
        $stmt->execute(['edicao_id' => $edicaoId]);

        return (float) $stmt->fetchColumn();
    }
}
