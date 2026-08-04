<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Indicador
{
    /** Funcionários ativos da filial com o indicador já lançado no período (se houver). */
    public static function funcionariosComIndicador(int $filialId, int $periodoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.id, f.nome, i.desconto_medio, i.rentabilidade_pct
             FROM funcionario f
             JOIN funcionario_filial ff ON ff.funcionario_id = f.id
             LEFT JOIN indicador_funcionario i ON i.funcionario_id = f.id AND i.periodo_id = :periodo_id
             WHERE ff.filial_id = :filial_id AND f.ativo = 1
             ORDER BY f.nome'
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);

        return $stmt->fetchAll();
    }

    public static function rentabilidadeFilial(int $filialId, int $periodoId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM rentabilidade_filial WHERE filial_id = :filial_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function checklist(int $filialId, int $periodoId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM checklist_equipe WHERE filial_id = :filial_id AND periodo_id = :periodo_id'
        );
        $stmt->execute(['filial_id' => $filialId, 'periodo_id' => $periodoId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Salva os três blocos do fechamento numa única transação (upsert por período+filial/funcionário).
     *
     * @param array<int, array{funcionario_id:int, desconto_medio:float, rentabilidade_pct:float}> $indicadoresFuncionarios
     * @param array<string, bool> $checklist chaves = colunas c1_...c5_..., valores = marcado ou não
     */
    public static function salvarTudo(
        int $periodoId,
        int $filialId,
        array $indicadoresFuncionarios,
        float $rentabilidadeFilialPct,
        array $checklist
    ): void {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO indicador_funcionario (periodo_id, funcionario_id, desconto_medio, rentabilidade_pct)
                 VALUES (:periodo_id, :funcionario_id, :desconto_medio, :rentabilidade_pct)
                 ON DUPLICATE KEY UPDATE desconto_medio = VALUES(desconto_medio), rentabilidade_pct = VALUES(rentabilidade_pct)'
            );
            foreach ($indicadoresFuncionarios as $linha) {
                $stmt->execute([
                    'periodo_id' => $periodoId,
                    'funcionario_id' => $linha['funcionario_id'],
                    'desconto_medio' => $linha['desconto_medio'],
                    'rentabilidade_pct' => $linha['rentabilidade_pct'],
                ]);
            }

            $pdo->prepare(
                'INSERT INTO rentabilidade_filial (periodo_id, filial_id, rentabilidade_pct)
                 VALUES (:periodo_id, :filial_id, :rentabilidade_pct)
                 ON DUPLICATE KEY UPDATE rentabilidade_pct = VALUES(rentabilidade_pct)'
            )->execute(['periodo_id' => $periodoId, 'filial_id' => $filialId, 'rentabilidade_pct' => $rentabilidadeFilialPct]);

            $pdo->prepare(
                'INSERT INTO checklist_equipe (
                    periodo_id, filial_id,
                    c1_sem_falta_injustificada, c2_cumpriu_escala, c3_setor_organizado,
                    c4_ajudou_treinou_colega, c5_loja_bateu_meta_coletiva
                 ) VALUES (
                    :periodo_id, :filial_id, :c1, :c2, :c3, :c4, :c5
                 ) ON DUPLICATE KEY UPDATE
                    c1_sem_falta_injustificada = VALUES(c1_sem_falta_injustificada),
                    c2_cumpriu_escala = VALUES(c2_cumpriu_escala),
                    c3_setor_organizado = VALUES(c3_setor_organizado),
                    c4_ajudou_treinou_colega = VALUES(c4_ajudou_treinou_colega),
                    c5_loja_bateu_meta_coletiva = VALUES(c5_loja_bateu_meta_coletiva)'
            )->execute([
                'periodo_id' => $periodoId,
                'filial_id' => $filialId,
                'c1' => !empty($checklist['c1_sem_falta_injustificada']) ? 1 : 0,
                'c2' => !empty($checklist['c2_cumpriu_escala']) ? 1 : 0,
                'c3' => !empty($checklist['c3_setor_organizado']) ? 1 : 0,
                'c4' => !empty($checklist['c4_ajudou_treinou_colega']) ? 1 : 0,
                'c5' => !empty($checklist['c5_loja_bateu_meta_coletiva']) ? 1 : 0,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
