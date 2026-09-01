<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Periodo
{
    private const SESSION_KEY = 'periodo_ativo';

    /** Retorna o período do mês corrente, criando-o (status 'aberto') se ainda não existir. */
    public static function atual(): array
    {
        $ano = (int) date('Y');
        $mes = (int) date('n');

        $stmt = Database::pdo()->prepare('SELECT * FROM periodo WHERE ano = :ano AND mes = :mes');
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        $row = $stmt->fetch();
        if ($row !== false) {
            return $row;
        }

        $stmt = Database::pdo()->prepare('INSERT INTO periodo (ano, mes, status) VALUES (:ano, :mes, :status)');
        $stmt->execute(['ano' => $ano, 'mes' => $mes, 'status' => 'aberto']);
        $id = (int) Database::pdo()->lastInsertId();

        return ['id' => $id, 'ano' => $ano, 'mes' => $mes, 'status' => 'aberto'];
    }

    /**
     * Período que as telas devem usar na requisição atual: o mês corrente, a menos que o usuário
     * tenha escolhido outro no seletor global do cabeçalho (ver selecionar()). A seleção fica na
     * sessão, por usuário logado, até ele trocar de novo ou a sessão expirar.
     */
    public static function ativo(): array
    {
        $atual = self::atual();

        $sessao = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($sessao) && isset($sessao['ano'], $sessao['mes'])) {
            $selecionado = self::porAnoMes((int) $sessao['ano'], (int) $sessao['mes']);
            if ($selecionado !== null) {
                return $selecionado;
            }
            unset($_SESSION[self::SESSION_KEY]);
        }

        return $atual;
    }

    /** Guarda a escolha do seletor global na sessão. Só aceita período que já existe. */
    public static function selecionar(int $ano, int $mes): bool
    {
        if (self::porAnoMes($ano, $mes) === null) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = ['ano' => $ano, 'mes' => $mes];
        return true;
    }

    public static function porAnoMes(int $ano, int $mes): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM periodo WHERE ano = :ano AND mes = :mes');
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Todos os períodos já existentes (algum lançamento ou fechamento feito neles), mais recente primeiro. */
    public static function listar(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM periodo ORDER BY ano DESC, mes DESC')
            ->fetchAll();
    }

    public static function aprovar(int $id, int $aprovadoPor): void
    {
        Database::pdo()
            ->prepare('UPDATE periodo SET status = "aprovado", aprovado_por = :por, aprovado_em = NOW() WHERE id = :id')
            ->execute(['por' => $aprovadoPor, 'id' => $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM periodo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
