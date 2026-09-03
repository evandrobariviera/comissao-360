<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Categoria;
use App\Models\FechamentoFilial;
use App\Models\Filial;
use App\Models\Meta;
use App\Models\Parametro;
use App\Models\Periodo;

/**
 * "Regras de comissão" — reúne numa tela só, em abas, as três camadas da metodologia
 * que antes ficavam espalhadas em /categorias e /parametros:
 *   1. Faixas por categoria (comissão base)
 *   2. Meta 360 — pesos dos pilares + pisos/tetos de Qualidade (+ override por filial)
 *   3. Prêmio de filial (valor de referência) + meta de mix por categoria
 *
 * Os formulários continuam postando para CategoriaController / ParametroController — este
 * controller só monta a visão. As rotas GET /categorias e /parametros redirecionam pra cá.
 */
final class RegrasController extends Controller
{
    private const ABAS = ['faixas', 'meta-360', 'premio-mix'];

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);

        $aba = (string) $this->input('aba', 'faixas');
        if (!in_array($aba, self::ABAS, true)) {
            $aba = 'faixas';
        }

        $filiais = Filial::ativas();
        $ids = array_map('intval', array_column($filiais, 'id'));
        $filialId = (int) $this->input('filial_id', 0);
        if (!in_array($filialId, $ids, true)) {
            $filialId = $ids[0] ?? 0;
        }

        $periodo = Periodo::ativo();

        $parametros = [];
        foreach (Parametro::todosDetalhados() as $row) {
            $parametros[$row['chave']] = $row;
        }

        $this->render('regras/index', [
            'aba' => $aba,
            'todasCategorias' => Categoria::all(),
            'categoriasMix' => Categoria::ativas(),
            'parametros' => $parametros,
            'parametrosGlobais' => Parametro::todos(),
            'filiais' => $filiais,
            'filialId' => $filialId,
            'periodo' => $periodo,
            'fechamento' => $filialId > 0 ? FechamentoFilial::status((int) $periodo['id'], $filialId) : null,
            'metaFilial' => $filialId > 0 ? Meta::filial($filialId, (int) $periodo['id']) : null,
        ]);
    }
}
