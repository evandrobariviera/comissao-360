<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Parametro;

/** Tela admin dos parâmetros globais ("parâmetro mãe") da Meta 360 — valem pra rede toda,
 *  salvo onde a filial tiver override em meta_filial (ver /metas). */
final class ParametroController extends Controller
{
    /** Chaves editáveis nesta tela, na ordem de exibição — protege contra POST de chave arbitrária. */
    private const CHAVES_EDITAVEIS = [
        'desconto_piso_pct', 'desconto_teto_pct', 'desconto_pts_max',
        'rentab_piso_pct', 'rentab_teto_pct', 'rentab_filial_pts', 'rentab_funcionario_pts',
        'ticket_medio_piso', 'ticket_medio_teto', 'ticket_medio_pts_max',
        'peso_individual_max', 'peso_filial_max', 'peso_qualidade_max', 'peso_equipe_max',
        'premio_filial_padrao',
    ];

    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);

        $parametros = [];
        foreach (Parametro::todosDetalhados() as $row) {
            $parametros[$row['chave']] = $row;
        }

        $this->render('admin/parametros/index', ['parametros' => $parametros]);
    }

    public function salvar(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $post = $this->input('valor', []);
        $post = is_array($post) ? $post : [];

        $pares = [];
        foreach (self::CHAVES_EDITAVEIS as $chave) {
            if (!array_key_exists($chave, $post)) {
                continue;
            }
            $valorRaw = trim(str_replace(',', '.', (string) $post[$chave]));
            if (!is_numeric($valorRaw) || (float) $valorRaw < 0) {
                Flash::set('erro', "Valor inválido para o parâmetro \"{$chave}\".");
                $this->redirect('/parametros');
            }
            $pares[$chave] = $valorRaw;
        }

        $descontoPiso = (float) ($pares['desconto_piso_pct'] ?? Parametro::float('desconto_piso_pct'));
        $descontoTeto = (float) ($pares['desconto_teto_pct'] ?? Parametro::float('desconto_teto_pct'));
        $rentabPiso = (float) ($pares['rentab_piso_pct'] ?? Parametro::float('rentab_piso_pct'));
        $rentabTeto = (float) ($pares['rentab_teto_pct'] ?? Parametro::float('rentab_teto_pct'));
        $ticketPiso = (float) ($pares['ticket_medio_piso'] ?? Parametro::float('ticket_medio_piso'));
        $ticketTeto = (float) ($pares['ticket_medio_teto'] ?? Parametro::float('ticket_medio_teto'));

        if ($descontoTeto <= $descontoPiso) {
            Flash::set('erro', 'Desconto médio: o teto precisa ser maior que o piso.');
            $this->redirect('/parametros');
        }
        if ($rentabTeto <= $rentabPiso) {
            Flash::set('erro', 'Rentabilidade: o teto precisa ser maior que o piso.');
            $this->redirect('/parametros');
        }
        if ($ticketTeto <= $ticketPiso) {
            Flash::set('erro', 'Ticket médio: o teto precisa ser maior que o piso.');
            $this->redirect('/parametros');
        }

        Parametro::atualizar($pares);
        Audit::log('atualizar', 'parametro', 0, implode(',', array_keys($pares)));
        Flash::set('sucesso', 'Parâmetros globais salvos.');
        $this->redirect('/parametros');
    }
}
