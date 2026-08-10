<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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
}
