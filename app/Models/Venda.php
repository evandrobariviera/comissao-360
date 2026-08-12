<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Venda
{
    public static function porFilialPeriodo(int $filialId, int $periodoId, int $limite = 30): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT v.id, v.data, v.valor, v.eh_sn, f.nome AS funcionario_nome, c.nome AS categoria_nome
             FROM venda_lancamento v
             JOIN funcionario f ON f.id = v.funcionario_id
             JOIN categoria c ON c.id = v.categoria_id
             WHERE v.filial_id = :filial_id AND v.periodo_id = :periodo_id
             ORDER BY v.data DESC, v.id DESC
             LIMIT ' . $limite
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);

        return $stmt->fetchAll();
    }

    public static function porFuncionarioPeriodo(int $funcionarioId, int $periodoId, int $limite = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT v.id, v.data, v.valor, v.eh_sn, c.nome AS categoria_nome
             FROM venda_lancamento v
             JOIN categoria c ON c.id = v.categoria_id
             WHERE v.funcionario_id = :funcionario_id AND v.periodo_id = :periodo_id
             ORDER BY v.data DESC, v.id DESC
             LIMIT ' . $limite
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId]);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM venda_lancamento WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function funcionarioPertenceFilial(int $funcionarioId, int $filialId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM funcionario_filial WHERE funcionario_id = :funcionario_id AND filial_id = :filial_id'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'filial_id' => $filialId]);

        return $stmt->fetchColumn() !== false;
    }

    public static function create(
        int $periodoId,
        int $funcionarioId,
        int $filialId,
        int $categoriaId,
        string $data,
        float $valor,
        bool $ehSn,
        int $criadoPor
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO venda_lancamento (periodo_id, funcionario_id, filial_id, categoria_id, data, valor, eh_sn, criado_por)
             VALUES (:periodo_id, :funcionario_id, :filial_id, :categoria_id, :data, :valor, :eh_sn, :criado_por)'
        );
        $stmt->execute([
            'periodo_id' => $periodoId,
            'funcionario_id' => $funcionarioId,
            'filial_id' => $filialId,
            'categoria_id' => $categoriaId,
            'data' => $data,
            'valor' => $valor,
            'eh_sn' => $ehSn ? 1 : 0,
            'criado_por' => $criadoPor,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM venda_lancamento WHERE id = :id')->execute(['id' => $id]);
    }

    public static function somaPorCategoria(int $funcionarioId, int $periodoId, int $categoriaId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM venda_lancamento
             WHERE funcionario_id = :funcionario_id AND periodo_id = :periodo_id AND categoria_id = :categoria_id'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId, 'categoria_id' => $categoriaId]);

        return (float) $stmt->fetchColumn();
    }

    public static function somaSemNota(int $funcionarioId, int $periodoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM venda_lancamento
             WHERE funcionario_id = :funcionario_id AND periodo_id = :periodo_id AND eh_sn = 1'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function somaTotalFuncionario(int $funcionarioId, int $periodoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM venda_lancamento WHERE funcionario_id = :funcionario_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function somaTotalPeriodo(int $periodoId): float
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(SUM(valor), 0) FROM venda_lancamento WHERE periodo_id = :periodo_id');
        $stmt->execute(['periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function somaTotalFilial(int $filialId, int $periodoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM venda_lancamento WHERE filial_id = :filial_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Mapa [funcionario_id][categoria_id] => total já realizado no mês (soma de todos
     * os ajustes até agora), para pré-preencher a grade de /vendas.
     *
     * @param int[] $funcionarioIds
     * @param int[] $categoriaIds
     * @return array<int, array<int, float>>
     */
    public static function gridTotais(array $funcionarioIds, array $categoriaIds, int $periodoId): array
    {
        $mapa = [];
        foreach ($funcionarioIds as $fid) {
            foreach ($categoriaIds as $cid) {
                $mapa[$fid][$cid] = 0.0;
            }
        }
        if (empty($funcionarioIds)) {
            return $mapa;
        }

        $placeholders = implode(',', array_fill(0, count($funcionarioIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT funcionario_id, categoria_id, COALESCE(SUM(valor), 0) AS total
             FROM venda_lancamento
             WHERE periodo_id = ? AND funcionario_id IN ($placeholders)
             GROUP BY funcionario_id, categoria_id"
        );
        $stmt->execute([$periodoId, ...$funcionarioIds]);
        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['funcionario_id']][(int) $row['categoria_id']] = (float) $row['total'];
        }

        return $mapa;
    }

    /** Mapa [funcionario_id] => total sem-nota já realizado no mês (subconjunto de Manipulação). */
    public static function mapaSemNota(array $funcionarioIds, int $periodoId): array
    {
        $mapa = array_fill_keys($funcionarioIds, 0.0);
        if (empty($funcionarioIds)) {
            return $mapa;
        }

        $placeholders = implode(',', array_fill(0, count($funcionarioIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT funcionario_id, COALESCE(SUM(valor), 0) AS total
             FROM venda_lancamento
             WHERE periodo_id = ? AND eh_sn = 1 AND funcionario_id IN ($placeholders)
             GROUP BY funcionario_id"
        );
        $stmt->execute([$periodoId, ...$funcionarioIds]);
        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['funcionario_id']] = (float) $row['total'];
        }

        return $mapa;
    }

    /**
     * Grava a grade de "total já vendido no mês" por funcionário/categoria. Não substitui
     * o histórico: para cada célula, grava um lançamento-ajuste só com a DIFERENÇA entre o
     * total novo e o total já realizado, preservando o rastro de auditoria e mantendo o
     * motor de cálculo (que soma por categoria) inalterado.
     *
     * @param array<int, array<int, float>> $totais [funcionario_id][categoria_id] => novo total do mês
     * @param array<int, float> $totaisSn [funcionario_id] => novo total sem-nota do mês (subconjunto de Manipulação)
     */
    public static function salvarGrade(
        int $periodoId,
        int $filialId,
        int $categoriaManipulacaoId,
        array $totais,
        array $totaisSn,
        int $usuarioId
    ): void {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO venda_lancamento (periodo_id, funcionario_id, filial_id, categoria_id, data, valor, eh_sn, criado_por)
                 VALUES (:periodo_id, :funcionario_id, :filial_id, :categoria_id, CURDATE(), :valor, :eh_sn, :criado_por)'
            );

            foreach ($totais as $funcionarioId => $porCategoria) {
                foreach ($porCategoria as $categoriaId => $novoTotal) {
                    if ($categoriaId === $categoriaManipulacaoId) {
                        $novoSn = $totaisSn[$funcionarioId] ?? 0.0;
                        $totalAtual = self::somaPorCategoria($funcionarioId, $periodoId, $categoriaId);
                        $snAtual = self::somaSemNota($funcionarioId, $periodoId);
                        $naoSnAtual = $totalAtual - $snAtual;
                        $novoNaoSn = $novoTotal - $novoSn;

                        $deltaNaoSn = round($novoNaoSn - $naoSnAtual, 2);
                        if ($deltaNaoSn !== 0.0) {
                            $insert->execute([
                                'periodo_id' => $periodoId, 'funcionario_id' => $funcionarioId,
                                'filial_id' => $filialId, 'categoria_id' => $categoriaId,
                                'valor' => $deltaNaoSn, 'eh_sn' => 0, 'criado_por' => $usuarioId,
                            ]);
                        }

                        $deltaSn = round($novoSn - $snAtual, 2);
                        if ($deltaSn !== 0.0) {
                            $insert->execute([
                                'periodo_id' => $periodoId, 'funcionario_id' => $funcionarioId,
                                'filial_id' => $filialId, 'categoria_id' => $categoriaId,
                                'valor' => $deltaSn, 'eh_sn' => 1, 'criado_por' => $usuarioId,
                            ]);
                        }
                        continue;
                    }

                    $atual = self::somaPorCategoria($funcionarioId, $periodoId, $categoriaId);
                    $delta = round($novoTotal - $atual, 2);
                    if ($delta !== 0.0) {
                        $insert->execute([
                            'periodo_id' => $periodoId, 'funcionario_id' => $funcionarioId,
                            'filial_id' => $filialId, 'categoria_id' => $categoriaId,
                            'valor' => $delta, 'eh_sn' => 0, 'criado_por' => $usuarioId,
                        ]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
