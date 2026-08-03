<?php

declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function log(string $acao, string $entidade, ?int $entidadeId = null, ?string $detalhes = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO log_auditoria (usuario_id, acao, entidade, entidade_id, detalhes)
             VALUES (:usuario_id, :acao, :entidade, :entidade_id, :detalhes)'
        );
        $stmt->execute([
            'usuario_id' => Auth::id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'detalhes' => $detalhes,
        ]);
    }
}
