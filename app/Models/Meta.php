<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Meta
{
    /**
     * Mapa [funcionario_id][categoria_id] => meta_venda já lançada no período (0 se ainda não lançada).
     *
     * @param int[] $funcionarioIds
     * @param int[] $categoriaIds
     * @return array<int, array<int, float>>
     */
    public static function grid(array $funcionarioIds, array $categoriaIds, int $periodoId): array
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
            "SELECT funcionario_id, categoria_id, meta_venda FROM meta_funcionario
             WHERE periodo_id = ? AND funcionario_id IN ($placeholders)"
        );
        $stmt->execute([$periodoId, ...$funcionarioIds]);
        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['funcionario_id']][(int) $row['categoria_id']] = (float) $row['meta_venda'];
        }

        return $mapa;
    }

    /**
     * Salva a meta da filial e o grid de metas por funcionário/categoria numa única transação.
     * Não mexe nos campos de override de Qualidade (ver salvarOverridesQualidade) — são conceitos
     * separados (meta = alvo a bater; override = regra de pontuação), editados em telas diferentes.
     *
     * @param array{meta_venda: float, meta_rentabilidade: float, valor_premio: float} $metaFilial
     * @param array<int, array{funcionario_id:int, categoria_id:int, meta_venda:float}> $metasFuncionarios
     */
    public static function salvarTudo(int $periodoId, int $filialId, array $metaFilial, array $metasFuncionarios): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO meta_filial (periodo_id, filial_id, meta_venda, meta_rentabilidade, valor_premio)
                 VALUES (:periodo_id, :filial_id, :meta_venda, :meta_rentabilidade, :valor_premio)
                 ON DUPLICATE KEY UPDATE
                    meta_venda = VALUES(meta_venda), meta_rentabilidade = VALUES(meta_rentabilidade), valor_premio = VALUES(valor_premio)'
            )->execute([
                'periodo_id' => $periodoId,
                'filial_id' => $filialId,
                'meta_venda' => $metaFilial['meta_venda'],
                'meta_rentabilidade' => $metaFilial['meta_rentabilidade'],
                'valor_premio' => $metaFilial['valor_premio'],
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO meta_funcionario (periodo_id, funcionario_id, categoria_id, meta_venda)
                 VALUES (:periodo_id, :funcionario_id, :categoria_id, :meta_venda)
                 ON DUPLICATE KEY UPDATE meta_venda = VALUES(meta_venda)'
            );
            foreach ($metasFuncionarios as $linha) {
                $stmt->execute([
                    'periodo_id' => $periodoId,
                    'funcionario_id' => $linha['funcionario_id'],
                    'categoria_id' => $linha['categoria_id'],
                    'meta_venda' => $linha['meta_venda'],
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Meta Individual Total = soma das metas por categoria do funcionário no período. */
    public static function totalIndividual(int $funcionarioId, int $periodoId): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(meta_venda), 0) FROM meta_funcionario WHERE funcionario_id = :funcionario_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['funcionario_id' => $funcionarioId, 'periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function totalRedeVenda(int $periodoId): float
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(SUM(meta_venda), 0) FROM meta_filial WHERE periodo_id = :periodo_id');
        $stmt->execute(['periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }

    public static function filial(int $filialId, int $periodoId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM meta_filial WHERE filial_id = :filial_id AND periodo_id = :periodo_id');
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Salva o override de filial dos parâmetros de Qualidade (desconto/rentabilidade/ticket médio).
     * Campos NULL = sem override, a filial usa o padrão global (ver Pontuacao360Calculator::resolvido).
     * Não mexe nos campos de meta (meta_venda etc.) — ver salvarTudo.
     *
     * @param array{
     *   ticket_medio_piso: ?float, ticket_medio_teto: ?float,
     *   desconto_piso_pct: ?float, desconto_teto_pct: ?float,
     *   rentab_piso_pct: ?float, rentab_teto_pct: ?float
     * } $overrides
     */
    public static function salvarOverridesQualidade(int $periodoId, int $filialId, array $overrides): void
    {
        Database::pdo()->prepare(
            'INSERT INTO meta_filial (
                periodo_id, filial_id,
                ticket_medio_piso, ticket_medio_teto, desconto_piso_pct, desconto_teto_pct, rentab_piso_pct, rentab_teto_pct
             )
             VALUES (
                :periodo_id, :filial_id,
                :ticket_medio_piso, :ticket_medio_teto, :desconto_piso_pct, :desconto_teto_pct, :rentab_piso_pct, :rentab_teto_pct
             )
             ON DUPLICATE KEY UPDATE
                ticket_medio_piso = VALUES(ticket_medio_piso), ticket_medio_teto = VALUES(ticket_medio_teto),
                desconto_piso_pct = VALUES(desconto_piso_pct), desconto_teto_pct = VALUES(desconto_teto_pct),
                rentab_piso_pct = VALUES(rentab_piso_pct), rentab_teto_pct = VALUES(rentab_teto_pct)'
        )->execute([
            'periodo_id' => $periodoId,
            'filial_id' => $filialId,
            'ticket_medio_piso' => $overrides['ticket_medio_piso'],
            'ticket_medio_teto' => $overrides['ticket_medio_teto'],
            'desconto_piso_pct' => $overrides['desconto_piso_pct'],
            'desconto_teto_pct' => $overrides['desconto_teto_pct'],
            'rentab_piso_pct' => $overrides['rentab_piso_pct'],
            'rentab_teto_pct' => $overrides['rentab_teto_pct'],
        ]);
    }

    /**
     * Lançamentos diários da venda bruta da filial, mais recentes primeiro, com o recorte por
     * categoria de cada um (só as categorias com meta de mix configurada têm recorte).
     *
     * @return array<int, array{id:int, data:string, valor:string, categorias: array<int, array{categoria_id:int, nome:string, valor:string}>}>
     */
    public static function lancamentosVendaBruta(int $filialId, int $periodoId, int $limite = 60): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, data, valor FROM venda_bruta_lancamento
             WHERE filial_id = :filial_id AND periodo_id = :periodo_id
             ORDER BY data DESC, id DESC
             LIMIT {$limite}"
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);
        $lancamentos = $stmt->fetchAll();
        if (empty($lancamentos)) {
            return [];
        }

        $ids = array_column($lancamentos, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtCat = Database::pdo()->prepare(
            "SELECT vbcl.venda_bruta_lancamento_id, vbcl.categoria_id, c.nome, vbcl.valor
             FROM venda_bruta_categoria_lancamento vbcl
             JOIN categoria c ON c.id = vbcl.categoria_id
             WHERE vbcl.venda_bruta_lancamento_id IN ($placeholders)"
        );
        $stmtCat->execute($ids);

        $porLancamento = [];
        foreach ($stmtCat->fetchAll() as $row) {
            $porLancamento[(int) $row['venda_bruta_lancamento_id']][] = [
                'categoria_id' => (int) $row['categoria_id'],
                'nome' => $row['nome'],
                'valor' => $row['valor'],
            ];
        }

        foreach ($lancamentos as &$l) {
            $l['categorias'] = $porLancamento[(int) $l['id']] ?? [];
        }

        return $lancamentos;
    }

    public static function lancamentoVendaBruta(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM venda_bruta_lancamento WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Acrescenta um lançamento diário de venda bruta (dia + valor) — SOMA ao total do período,
     * não sobrescreve. `meta_filial.venda_bruta_realizada` é recalculado como SUM(valor) logo em
     * seguida, na mesma transação, pra continuar servindo de cache pros outros lugares que já leem
     * essa coluna (dashboard, PremioFilialService, Pontuacao360Calculator).
     *
     * $porCategoria é opcional e informativo (não precisa somar o valor total do dia) — só pras
     * categorias com meta de mix configurada (ver Categoria::comMetaPercentual).
     *
     * @param array<int, float> $porCategoria [categoria_id => valor do dia]
     */
    public static function adicionarLancamentoVendaBruta(
        int $periodoId,
        int $filialId,
        string $data,
        float $valor,
        int $usuarioId,
        array $porCategoria = []
    ): void {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO venda_bruta_lancamento (periodo_id, filial_id, data, valor, criado_por)
                 VALUES (:periodo_id, :filial_id, :data, :valor, :criado_por)'
            )->execute([
                'periodo_id' => $periodoId,
                'filial_id' => $filialId,
                'data' => $data,
                'valor' => $valor,
                'criado_por' => $usuarioId,
            ]);
            $lancamentoId = (int) $pdo->lastInsertId();

            if (!empty($porCategoria)) {
                $stmtCat = $pdo->prepare(
                    'INSERT INTO venda_bruta_categoria_lancamento (venda_bruta_lancamento_id, categoria_id, valor)
                     VALUES (:lancamento_id, :categoria_id, :valor)'
                );
                foreach ($porCategoria as $categoriaId => $valorCategoria) {
                    $stmtCat->execute([
                        'lancamento_id' => $lancamentoId,
                        'categoria_id' => $categoriaId,
                        'valor' => $valorCategoria,
                    ]);
                }
            }

            self::recalcularVendaBrutaRealizada($pdo, $periodoId, $filialId, $usuarioId);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Exclui um lançamento diário e recalcula o total do período/filial dele. */
    public static function excluirLancamentoVendaBruta(int $id, int $usuarioId): void
    {
        $lancamento = self::lancamentoVendaBruta($id);
        if ($lancamento === null) {
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM venda_bruta_lancamento WHERE id = :id')->execute(['id' => $id]);
            self::recalcularVendaBrutaRealizada($pdo, (int) $lancamento['periodo_id'], (int) $lancamento['filial_id'], $usuarioId);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function recalcularVendaBrutaRealizada(\PDO $pdo, int $periodoId, int $filialId, int $usuarioId): void
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM venda_bruta_lancamento WHERE periodo_id = :periodo_id AND filial_id = :filial_id'
        );
        $stmt->execute(['periodo_id' => $periodoId, 'filial_id' => $filialId]);
        $total = (float) $stmt->fetchColumn();

        $pdo->prepare(
            'INSERT INTO meta_filial (periodo_id, filial_id, venda_bruta_realizada, venda_bruta_atualizado_em, venda_bruta_atualizado_por)
             VALUES (:periodo_id, :filial_id, :valor, NOW(), :usuario_id)
             ON DUPLICATE KEY UPDATE
                venda_bruta_realizada = VALUES(venda_bruta_realizada),
                venda_bruta_atualizado_em = VALUES(venda_bruta_atualizado_em),
                venda_bruta_atualizado_por = VALUES(venda_bruta_atualizado_por)'
        )->execute([
            'periodo_id' => $periodoId,
            'filial_id' => $filialId,
            'valor' => $total,
            'usuario_id' => $usuarioId,
        ]);
    }

    public static function totalRedeVendaBrutaRealizada(int $periodoId): float
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(SUM(venda_bruta_realizada), 0) FROM meta_filial WHERE periodo_id = :periodo_id');
        $stmt->execute(['periodo_id' => $periodoId]);

        return (float) $stmt->fetchColumn();
    }
}
