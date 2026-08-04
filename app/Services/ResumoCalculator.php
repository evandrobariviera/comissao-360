<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Funcionario;
use App\Models\Parametro;

/**
 * Calcula o resultado do mês (Blocos 1+2+3) por funcionário — usado tanto na
 * prévia do fechamento quanto nos dashboards, sempre com a filial PRINCIPAL
 * de cada pessoa (ver FechamentoController para o porquê).
 */
final class ResumoCalculator
{
    public static function linha(int $funcionarioId, string $nome, int $filialId, int $periodoId, array $parametros): array
    {
        $comissao = ComissaoCalculator::calcular($funcionarioId, $periodoId);
        $pontuacao = Pontuacao360Calculator::calcular($funcionarioId, $filialId, $periodoId, $parametros);
        $premio = PremioFilialService::calcular($filialId, $periodoId);
        $comissaoAjustada = $comissao['comissao_base'] * $pontuacao['multiplicador_protegido'];
        $total = $comissaoAjustada + $premio;

        return [
            'funcionario_id' => $funcionarioId,
            'nome' => $nome,
            'filial_id' => $filialId,
            'comissao_base' => $comissao['comissao_base'],
            'detalhe_categorias' => $comissao['detalhe'],
            'pontuacao' => $pontuacao,
            'premio_filial' => $premio,
            'comissao_ajustada' => $comissaoAjustada,
            'total' => $total,
        ];
    }

    /** @return array<int, array> */
    public static function porFilial(int $filialId, int $periodoId): array
    {
        $parametros = Parametro::todos();
        $linhas = [];
        foreach (Funcionario::porFilialPrincipal($filialId) as $f) {
            $linhas[] = self::linha((int) $f['id'], $f['nome'], $filialId, $periodoId, $parametros);
        }

        return $linhas;
    }

    /** Rede toda — cada funcionário aparece uma única vez, na sua filial principal. */
    public static function rede(int $periodoId): array
    {
        $parametros = Parametro::todos();
        $linhas = [];
        foreach (Funcionario::todosComFilialPrincipal() as $f) {
            $filialId = (int) ($f['filial_id'] ?? 0);
            if ($filialId === 0) {
                continue;
            }
            $linhas[] = self::linha((int) $f['funcionario_id'], $f['nome'], $filialId, $periodoId, $parametros);
        }

        return $linhas;
    }
}
