<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Categoria
{
    /** Vocabulário controlado de marcações de venda que podem compor a faixa de outra categoria. */
    public const FLAGS_VENDA = [
        'eh_sn' => 'Venda sem nota (S/N)',
    ];

    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM categoria ORDER BY ordem, nome')->fetchAll();
    }

    public static function ativas(): array
    {
        return Database::pdo()->query('SELECT * FROM categoria WHERE ativo = 1 ORDER BY ordem, nome')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categoria WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array{id:int, nome:string}> */
    public static function listaParaSelect(?int $excluirId = null): array
    {
        if ($excluirId !== null) {
            $stmt = Database::pdo()->prepare('SELECT id, nome FROM categoria WHERE id != :id ORDER BY nome');
            $stmt->execute(['id' => $excluirId]);
        } else {
            $stmt = Database::pdo()->query('SELECT id, nome FROM categoria ORDER BY nome');
        }

        return $stmt->fetchAll();
    }

    public static function create(string $nome, int $ordem): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO categoria (nome, ordem) VALUES (:nome, :ordem)');
        $stmt->execute(['nome' => $nome, 'ordem' => $ordem]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function alternarStatus(int $id): void
    {
        Database::pdo()->prepare('UPDATE categoria SET ativo = NOT ativo WHERE id = :id')->execute(['id' => $id]);
    }

    public static function faixas(int $categoriaId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM faixa_comissao WHERE categoria_id = :id ORDER BY ordem');
        $stmt->execute(['id' => $categoriaId]);

        return $stmt->fetchAll();
    }

    public static function composicoes(int $categoriaId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT cc.*, c.nome AS fonte_categoria_nome
             FROM categoria_composicao cc
             LEFT JOIN categoria c ON c.id = cc.fonte_categoria_id
             WHERE cc.categoria_regra_id = :id'
        );
        $stmt->execute(['id' => $categoriaId]);

        return $stmt->fetchAll();
    }

    /**
     * Salva dados básicos + faixas + composição de uma categoria numa única transação.
     *
     * @param array<int, array{limite_ate: float|null, percentual: float}> $faixas já validadas e em ordem
     * @param array<int, array{fonte_tipo:string, fonte_categoria_id:?int, fonte_flag:?string}> $composicoes já validadas
     */
    public static function salvarCompleta(int $id, string $nome, int $ordem, array $faixas, array $composicoes): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE categoria SET nome = :nome, ordem = :ordem WHERE id = :id')
                ->execute(['nome' => $nome, 'ordem' => $ordem, 'id' => $id]);

            $pdo->prepare('DELETE FROM faixa_comissao WHERE categoria_id = :id')->execute(['id' => $id]);
            $stmt = $pdo->prepare(
                'INSERT INTO faixa_comissao (categoria_id, ordem, limite_ate, percentual) VALUES (:categoria_id, :ordem, :limite_ate, :percentual)'
            );
            foreach (array_values($faixas) as $index => $linha) {
                $stmt->execute([
                    'categoria_id' => $id,
                    'ordem' => $index + 1,
                    'limite_ate' => $linha['limite_ate'],
                    'percentual' => $linha['percentual'],
                ]);
            }

            $pdo->prepare('DELETE FROM categoria_composicao WHERE categoria_regra_id = :id')->execute(['id' => $id]);
            $stmt = $pdo->prepare(
                'INSERT INTO categoria_composicao (categoria_regra_id, fonte_tipo, fonte_categoria_id, fonte_flag)
                 VALUES (:categoria_regra_id, :fonte_tipo, :fonte_categoria_id, :fonte_flag)'
            );
            foreach ($composicoes as $linha) {
                $stmt->execute([
                    'categoria_regra_id' => $id,
                    'fonte_tipo' => $linha['fonte_tipo'],
                    'fonte_categoria_id' => $linha['fonte_categoria_id'],
                    'fonte_flag' => $linha['fonte_flag'],
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
