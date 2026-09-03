<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Categoria;
use App\Models\FechamentoFilial;
use App\Models\Filial;
use App\Models\Meta;
use App\Models\Parametro;
use App\Models\Periodo;

/** Tela admin dos parâmetros globais ("parâmetro mãe") da Meta 360 — valem pra rede toda,
 *  salvo onde a filial tiver override (abas por filial nesta mesma tela). */
final class ParametroController extends Controller
{
    /** Chaves globais editáveis nesta tela, na ordem de exibição — protege contra POST de chave arbitrária. */
    private const CHAVES_EDITAVEIS = [
        'desconto_piso_pct', 'desconto_teto_pct', 'desconto_pts_max',
        'rentab_piso_pct', 'rentab_teto_pct', 'rentab_filial_pts', 'rentab_funcionario_pts',
        'ticket_medio_piso', 'ticket_medio_teto', 'ticket_medio_pts_max',
        'peso_individual_max', 'peso_filial_max', 'peso_qualidade_max', 'peso_equipe_max',
        'premio_filial_padrao',
    ];

    /** A tela em si virou aba de /regras (ver RegrasController); esta rota antiga só redireciona. */
    public function index(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->redirect('/regras?aba=meta-360');
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
                $this->redirect('/regras?aba=meta-360');
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
            $this->redirect('/regras?aba=meta-360');
        }
        if ($rentabTeto <= $rentabPiso) {
            Flash::set('erro', 'Rentabilidade: o teto precisa ser maior que o piso.');
            $this->redirect('/regras?aba=meta-360');
        }
        if ($ticketTeto <= $ticketPiso) {
            Flash::set('erro', 'Ticket médio: o teto precisa ser maior que o piso.');
            $this->redirect('/regras?aba=meta-360');
        }

        Parametro::atualizar($pares);
        Audit::log('atualizar', 'parametro', 0, implode(',', array_keys($pares)));
        Flash::set('sucesso', 'Régua da Meta 360 salva.');
        $this->redirect('/regras?aba=meta-360');
    }

    /** Salva o override de Qualidade de UMA filial (aba selecionada) — não mexe no padrão global. */
    public function salvarFilial(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $filiais = Filial::ativas();
        $filialId = $this->resolverFilialId($filiais, (int) $this->input('filial_id', 0));
        $periodo = Periodo::ativo();

        if (!FechamentoFilial::estaAberto((int) $periodo['id'], $filialId)) {
            Flash::set('erro', 'Esta filial já está fechada neste período — não é mais possível alterar overrides.');
            $this->redirect("/regras?aba=meta-360&filial_id={$filialId}");
        }

        [$ticketPiso, $ticketTeto, $erroTicket] = $this->parOverride('ticket_medio_piso', 'ticket_medio_teto');
        [$descontoPiso, $descontoTeto, $erroDesconto] = $this->parOverride('desconto_piso_pct', 'desconto_teto_pct');
        [$rentabPiso, $rentabTeto, $erroRentab] = $this->parOverride('rentab_piso_pct', 'rentab_teto_pct');

        $erro = $erroTicket ?? $erroDesconto ?? $erroRentab;
        if ($erro !== null) {
            Flash::set('erro', $erro);
            $this->redirect("/regras?aba=meta-360&filial_id={$filialId}");
        }

        Meta::salvarOverridesQualidade((int) $periodo['id'], $filialId, [
            'ticket_medio_piso' => $ticketPiso,
            'ticket_medio_teto' => $ticketTeto,
            'desconto_piso_pct' => $descontoPiso,
            'desconto_teto_pct' => $descontoTeto,
            'rentab_piso_pct' => $rentabPiso,
            'rentab_teto_pct' => $rentabTeto,
        ]);

        Audit::log('salvar_override', 'meta_filial', $filialId, "periodo={$periodo['id']}");
        Flash::set('sucesso', 'Override da filial salvo.');
        $this->redirect("/regras?aba=meta-360&filial_id={$filialId}");
    }

    /**
     * Salva a meta de mix (%) por categoria — parâmetro global, não afeta comissão/pontuação.
     * Só serve de referência pros relatórios e pra decidir quais categorias pedem lançamento
     * diário por categoria em /vendas (só as que tiverem meta aqui, ver Categoria::comMetaPercentual).
     */
    public function salvarMix(): void
    {
        Auth::require(Auth::PAPEL_ADMIN);
        $this->requireCsrf();

        $post = $this->input('mix', []);
        $post = is_array($post) ? $post : [];
        $tipoPost = $this->input('mix_tipo', []);
        $tipoPost = is_array($tipoPost) ? $tipoPost : [];
        $categoriaIdsValidos = array_column(Categoria::ativas(), 'id');

        $pares = [];
        foreach ($categoriaIdsValidos as $categoriaId) {
            $valorRaw = trim(str_replace(',', '.', (string) ($post[$categoriaId] ?? '')));
            if ($valorRaw === '') {
                $pares[$categoriaId] = ['valor' => null, 'tipo' => 'piso'];
                continue;
            }
            if (!is_numeric($valorRaw) || (float) $valorRaw < 0 || (float) $valorRaw > 100) {
                Flash::set('erro', 'Meta de mix precisa ser um percentual entre 0 e 100 (ou vazio pra não rastrear a categoria).');
                $this->redirect('/regras?aba=premio-mix');
            }
            $tipo = (string) ($tipoPost[$categoriaId] ?? 'piso');
            if (!in_array($tipo, ['piso', 'teto'], true)) {
                $tipo = 'piso';
            }
            $pares[$categoriaId] = ['valor' => $valorRaw, 'tipo' => $tipo];
        }

        Categoria::salvarMetasPercentuais($pares);
        Audit::log('atualizar', 'categoria_meta_percentual', 0);
        Flash::set('sucesso', 'Meta de mix por categoria salva.');
        $this->redirect('/regras?aba=premio-mix');
    }

    private function resolverFilialId(array $filiais, int $solicitada = 0): int
    {
        $ids = array_column($filiais, 'id');
        if ($solicitada === 0) {
            $solicitada = (int) $this->input('filial_id', $ids[0] ?? 0);
        }

        return in_array($solicitada, $ids, true) ? $solicitada : (int) ($ids[0] ?? 0);
    }

    /**
     * Lê um par piso/teto opcional (override de filial): os dois vazios = sem override (NULL, usa o
     * padrão global); os dois preenchidos = override válido; só um preenchido = erro de preenchimento.
     *
     * @return array{0: ?float, 1: ?float, 2: ?string}
     */
    private function parOverride(string $campoPiso, string $campoTeto): array
    {
        $pisoRaw = trim(str_replace(',', '.', (string) $this->input($campoPiso, '')));
        $tetoRaw = trim(str_replace(',', '.', (string) $this->input($campoTeto, '')));

        if ($pisoRaw === '' && $tetoRaw === '') {
            return [null, null, null];
        }
        if ($pisoRaw === '' || $tetoRaw === '') {
            return [null, null, 'Preencha piso e teto juntos (ou deixe os dois vazios para usar o padrão global).'];
        }
        if (!is_numeric($pisoRaw) || (float) $pisoRaw < 0 || !is_numeric($tetoRaw) || (float) $tetoRaw <= (float) $pisoRaw) {
            return [null, null, 'Piso e teto precisam ser números válidos, com o teto maior que o piso.'];
        }

        return [(float) $pisoRaw, (float) $tetoRaw, null];
    }
}
