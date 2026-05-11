<?php
/**
 * Simulador: Planejamento de Independência Financeira
 *
 * Contexto: este arquivo é incluído dentro de templates/single-ferramenta.php,
 * então o CSS global do plugin já cobre cabeçalho/breadcrumb — aqui entra só a ferramenta.
 *
 * Estado por usuário: persistido via AJAX em wp_orick_lead_tool_state (tool_slug="planejamento-if").
 * Exige login (o gate é aplicado no single-ferramenta.php quando _orick_requer_login=1).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Enqueue libs necessárias (só carrega nesta página)
wp_enqueue_style( 'nouislider', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css', [], '15.7.1' );
wp_enqueue_script( 'nouislider', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js', [], '15.7.1', true );
wp_enqueue_script( 'chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js', [], '4.4.1', true );
wp_enqueue_script( 'xlsx', 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', [], '0.18.5', true );
wp_enqueue_script( 'jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', [], '2.5.1', true );
wp_enqueue_script( 'html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], '1.4.1', true );

wp_enqueue_script(
    'orick-sim-if',
    ORICK_FERR_URL . 'simulators/planejamento-if.js',
    [ 'nouislider', 'chartjs', 'xlsx', 'jspdf', 'html2canvas' ],
    ORICK_FERR_VERSION,
    true
);

// Expõe credenciais AJAX + estado inicial (se houver) pro JS
$lead_id       = Orick_Ferr_Auth::current_lead_id();
$initial_state = $lead_id ? Orick_Ferr_State::get( $lead_id, 'planejamento-if' ) : null;

wp_localize_script( 'orick-sim-if', 'OrickSimIF', [
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'orick_ferr_state' ),
    'tool'    => 'planejamento-if',
    'state'   => $initial_state ?: null,
] );
?>

<div class="sim-if">
  <style>
    .sim-if { --sim-accent: #A75232; --sim-vital: #7A8B5C; --sim-vital-strong: #8B9D6B; --sim-accent-strong: #B9623F; }
    .sim-if .sim-actions { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 24px; flex-wrap: wrap; }
    .sim-if .sim-save-hint { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); margin-right: auto; align-self: center; }
    .sim-if .sim-save-hint.saving { color: var(--sim-accent); }
    .sim-if .sim-save-hint.saved { color: var(--sim-vital-strong); }

    /* Manchetes de resultado */
    .sim-if .sim-results { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid var(--ofr-border); background: var(--ofr-bg-2); }
    .sim-if .sim-result-card { padding: 32px; position: relative; border-right: 1px solid var(--ofr-border); }
    .sim-if .sim-result-card:last-child { border-right: none; }
    .sim-if .sim-result-card .band { position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--sim-accent); }
    .sim-if .sim-result-card.vital .band { background: var(--sim-vital); }
    .sim-if .sim-result-head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
    .sim-if .sim-result-label { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--sim-accent); }
    .sim-if .sim-result-card.vital .sim-result-label { color: var(--sim-vital-strong); }
    .sim-if .sim-chart-meta { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); }
    .sim-if .sim-result-kicker { font-family: 'Fraunces', serif; font-weight: 400; font-size: 22px; letter-spacing: -0.01em; line-height: 1.15; margin: 0; }
    .sim-if .sim-result-kicker em { font-style: italic; color: var(--ofr-fg-dim); font-weight: 300; }
    .sim-if .sim-headline-number { font-family: 'Fraunces', serif; font-weight: 500; font-size: clamp(36px, 4vw, 52px); line-height: 1; letter-spacing: -0.03em; color: var(--sim-accent); margin: 16px 0 4px; font-variant-numeric: tabular-nums; }
    .sim-if .sim-result-card.vital .sim-headline-number { color: var(--sim-vital-strong); }
    .sim-if .sim-headline-caption { font-size: 12px; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); font-family: 'JetBrains Mono', monospace; letter-spacing: 0.04em; }
    .sim-if .sim-row { display: flex; justify-content: space-between; align-items: baseline; padding: 10px 0; border-bottom: 1px dashed var(--ofr-border); }
    .sim-if .sim-row:last-of-type { border-bottom: none; }
    .sim-if .sim-row .lbl { font-size: 13px; color: var(--ofr-fg-dim); }
    .sim-if .sim-row .val { font-family: 'JetBrains Mono', monospace; font-size: 15px; color: var(--ofr-fg); font-weight: 500; }

    /* Dashboard */
    .sim-if .sim-dash { display: grid; grid-template-columns: 1fr 340px; gap: 0; margin-top: 24px; border: 1px solid var(--ofr-border); background: var(--ofr-bg-2); }
    .sim-if .sim-chart-panel { padding: 24px; position: relative; min-height: 480px; border-right: 1px solid var(--ofr-border); }
    .sim-if .sim-chart-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .sim-if .sim-chart-title { font-family: 'Fraunces', serif; font-weight: 500; font-size: 20px; letter-spacing: -0.01em; }
    .sim-if .sim-chart-sub { font-size: 12px; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); }
    .sim-if .sim-expand-btn { position: absolute; top: 24px; right: 24px; width: 32px; height: 32px; display: grid; place-items: center; color: var(--ofr-fg-dim); background: var(--ofr-bg-2); border: 1px solid var(--ofr-border); cursor: pointer; transition: all .15s; z-index: 2; padding: 0; }
    .sim-if .sim-expand-btn:hover { color: var(--ofr-fg); border-color: var(--ofr-fg-dim); }
    .sim-if .sim-expand-btn svg { display: block; }
    .sim-if #sim-if-chart { width: 100% !important; height: 420px !important; }

    /* Controles */
    .sim-if .sim-controls { padding: 24px; display: flex; flex-direction: column; gap: 22px; }
    .sim-if .sim-controls-title { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); padding-bottom: 12px; border-bottom: 1px solid var(--ofr-border); }
    .sim-if .sim-field { display: flex; flex-direction: column; gap: 6px; }
    .sim-if .sim-field-label { display: flex; justify-content: space-between; align-items: baseline; font-size: 12px; color: var(--ofr-fg-dim); }
    .sim-if .sim-input-raw { background: var(--ofr-bg); border: 1px solid var(--ofr-border); color: var(--ofr-fg); font-family: 'JetBrains Mono', monospace; font-size: 13px; padding: 9px 10px; width: 100%; outline: none; transition: border-color .15s; }
    .sim-if .sim-input-raw:focus { border-color: var(--sim-accent); }
    .sim-if .sim-input-inline { background: transparent; border: none; border-bottom: 1px solid var(--ofr-border); color: var(--ofr-fg); font-family: 'JetBrains Mono', monospace; font-size: 13px; padding: 2px 0; max-width: 110px; text-align: right; outline: none; }
    .sim-if .sim-input-inline:focus { border-bottom-color: var(--sim-accent); }
    .sim-if .sim-age-display { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); letter-spacing: 0.04em; }
    .sim-if .sim-error { color: #C06B5A; font-size: 11px; font-family: 'JetBrains Mono', monospace; min-height: 14px; opacity: 0; transition: opacity .15s; }
    .sim-if .sim-error.visible { opacity: 1; }

    /* noUiSlider customização (scoped) */
    .sim-if .noUi-target { background: var(--ofr-bg); border: none; border-radius: 0; box-shadow: none; height: 4px; margin: 8px 0 4px; }
    .sim-if .noUi-connect { background: var(--sim-accent); }
    .sim-if .noUi-handle { width: 14px !important; height: 14px !important; right: -7px !important; top: -6px !important; background: var(--ofr-fg); border: 2px solid var(--sim-accent); border-radius: 50%; box-shadow: none; cursor: pointer; }
    .sim-if .noUi-handle::before, .sim-if .noUi-handle::after { display: none; }
    .sim-if .noUi-handle:hover { background: var(--sim-accent); }

    /* Seções com tabela */
    .sim-if .sim-block { margin-top: 24px; border: 1px solid var(--ofr-border); background: var(--ofr-bg-2); }
    .sim-if .sim-block-head { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--ofr-border); gap: 16px; flex-wrap: wrap; }
    .sim-if .sim-block-title { font-family: 'Fraunces', serif; font-weight: 500; font-size: 20px; letter-spacing: -0.01em; margin: 0; }
    .sim-if .sim-block-sub { font-size: 12px; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); margin-top: 4px; }
    .sim-if .sim-table-wrap { overflow: auto; max-height: 420px; }
    .sim-if table.sim-table { width: 100%; border-collapse: collapse; font-family: 'JetBrains Mono', monospace; font-size: 12px; }
    .sim-if .sim-table thead th { background: var(--ofr-bg-2); color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); font-family: 'Inter', sans-serif; font-size: 10.5px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; padding: 12px 14px; text-align: right; white-space: nowrap; position: sticky; top: 0; z-index: 2; border-bottom: 1px solid var(--ofr-border); }
    .sim-if .sim-table thead th:first-child { text-align: left; }
    .sim-if .sim-table tbody td { padding: 10px 14px; text-align: right; border-bottom: 1px solid var(--ofr-border); color: var(--ofr-fg); white-space: nowrap; }
    .sim-if .sim-table tbody td:first-child { text-align: left; }
    .sim-if .sim-table tbody tr:hover { background: rgba(228,216,199,0.02); }
    .sim-if .sim-table td.empty { text-align: center; color: var(--ofr-fg-mute, rgba(228,216,199,0.42)); font-style: italic; padding: 24px; }
    .sim-if .sim-tag { display: inline-block; font-size: 10px; padding: 2px 8px; text-transform: uppercase; letter-spacing: 0.08em; border: 1px solid var(--ofr-border); }
    .sim-if .sim-tag.aporte { color: var(--sim-vital-strong); border-color: rgba(122,139,92,0.4); }
    .sim-if .sim-tag.retirada { color: #C06B5A; border-color: rgba(192,107,90,0.4); }
    .sim-if .val-finite { color: var(--sim-accent); }
    .sim-if .val-perp { color: var(--sim-vital-strong); }
    .sim-if .val-total { color: var(--ofr-fg); font-weight: 500; }
    .sim-if .val-up { color: var(--sim-vital-strong); }
    .sim-if .val-down { color: #C06B5A; }

    /* Modais */
    .sim-if .sim-modal { display: none; position: fixed; inset: 0; background: rgba(15,14,12,0.84); backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center; padding: 40px 20px; }
    .sim-if .sim-modal.active { display: flex; }
    .sim-if .sim-modal-content { position: relative; background: var(--ofr-bg-2); border: 1px solid var(--ofr-border); width: 100%; max-width: 520px; padding: 32px; max-height: 90vh; overflow: auto; }
    .sim-if .sim-modal-lg { max-width: 1280px; height: 86vh; display: flex; flex-direction: column; padding: 24px; }
    .sim-if .sim-modal-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--ofr-border); }
    .sim-if .sim-modal-title { font-family: 'Fraunces', serif; font-weight: 500; font-size: 26px; letter-spacing: -0.01em; margin: 0; }
    .sim-if .sim-modal-title em { font-style: italic; color: var(--ofr-fg-dim); font-weight: 300; }
    .sim-if .sim-close-btn { width: 32px; height: 32px; display: grid; place-items: center; color: var(--ofr-fg-dim); cursor: pointer; transition: all .15s; font-size: 18px; background: transparent; border: none; padding: 0; }
    .sim-if .sim-close-btn:hover { color: var(--sim-accent); }
    .sim-if .sim-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .sim-if .sim-form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--ofr-border); }

    /* Botão local compacto */
    .sim-if .sim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: 1px solid var(--ofr-border); background: transparent; color: var(--ofr-fg); font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; letter-spacing: 0.04em; text-transform: uppercase; cursor: pointer; transition: all .15s; white-space: nowrap; }
    .sim-if .sim-btn:hover { border-color: var(--ofr-fg); background: rgba(228,216,199,0.04); }
    .sim-if .sim-btn-primary { background: var(--sim-accent); border-color: var(--sim-accent); color: #fff; }
    .sim-if .sim-btn-primary:hover { background: var(--sim-accent-strong); border-color: var(--sim-accent-strong); }
    .sim-if .sim-btn-sm { padding: 6px 10px; font-size: 10.5px; letter-spacing: 0.06em; }

    @media (max-width: 1024px) {
      .sim-if .sim-dash { grid-template-columns: 1fr; }
      .sim-if .sim-chart-panel { border-right: none; border-bottom: 1px solid var(--ofr-border); }
    }
    @media (max-width: 720px) {
      .sim-if .sim-results { grid-template-columns: 1fr; }
      .sim-if .sim-result-card { border-right: none; border-bottom: 1px solid var(--ofr-border); }
      .sim-if .sim-result-card:last-child { border-bottom: none; }
      .sim-if .sim-form-grid { grid-template-columns: 1fr; }
      .sim-if #sim-if-chart { height: 320px !important; }
    }
  </style>

  <div class="sim-actions">
    <span class="sim-save-hint" id="sim-if-save-hint" aria-live="polite"></span>
    <button class="sim-btn" id="sim-if-open-settings">Taxas</button>
    <button class="sim-btn sim-btn-primary" id="sim-if-gen-pdf">Gerar PDF</button>
  </div>

  <!-- Resultados -->
  <section class="sim-results">
    <div class="sim-result-card">
      <div class="band"></div>
      <div class="sim-result-head">
        <span class="sim-result-label">Cenário 1 · Finito</span>
        <span class="sim-chart-meta">consome capital</span>
      </div>
      <h2 class="sim-result-kicker">Independência até os <em id="sim-if-lbl-final-age">90</em> anos</h2>
      <div class="sim-headline-number" id="sim-if-aporte-finito">R$ 0,00</div>
      <div class="sim-headline-caption">aporte mensal sugerido</div>
      <div style="margin-top: 20px;">
        <div class="sim-row">
          <span class="lbl">Patrimônio alvo</span>
          <span class="val" id="sim-if-alvo-finito">R$ 0,00</span>
        </div>
        <div class="sim-row">
          <span class="lbl">Estratégia</span>
          <span class="val" style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--ofr-fg-dim);">Patrimônio zera na idade final</span>
        </div>
      </div>
    </div>

    <div class="sim-result-card vital">
      <div class="band"></div>
      <div class="sim-result-head">
        <span class="sim-result-label">Cenário 2 · Vitalício</span>
        <span class="sim-chart-meta">preserva capital</span>
      </div>
      <h2 class="sim-result-kicker">Renda perpétua <em>dos juros</em></h2>
      <div class="sim-headline-number" id="sim-if-aporte-vital">R$ 0,00</div>
      <div class="sim-headline-caption">aporte mensal sugerido</div>
      <div style="margin-top: 20px;">
        <div class="sim-row">
          <span class="lbl">Patrimônio alvo</span>
          <span class="val" id="sim-if-alvo-vital">R$ 0,00</span>
        </div>
        <div class="sim-row">
          <span class="lbl">Estratégia</span>
          <span class="val" style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--ofr-fg-dim);">Vive só dos juros reais</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Dashboard -->
  <section class="sim-dash">
    <div class="sim-chart-panel">
      <button class="sim-expand-btn" id="sim-if-expand" title="Expandir">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline>
          <line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line>
        </svg>
      </button>
      <div class="sim-chart-head">
        <div>
          <div class="sim-chart-title">Projeção de patrimônio</div>
          <div class="sim-chart-sub">Linha do tempo até a idade final</div>
        </div>
        <div class="sim-chart-meta">real · anual</div>
      </div>
      <canvas id="sim-if-chart"></canvas>
    </div>

    <aside class="sim-controls">
      <div class="sim-controls-title">Parâmetros</div>

      <div class="sim-field">
        <label class="sim-field-label"><span class="lbl">Nascimento</span></label>
        <input type="date" id="sim-if-birth" class="sim-input-raw" required>
        <div class="sim-age-display" id="sim-if-current-age">Idade atual: —</div>
        <div class="sim-error" id="sim-if-birth-error">Data inválida</div>
      </div>

      <div class="sim-field">
        <div class="sim-field-label"><span class="lbl">Aposentadoria</span><input type="text" id="sim-if-retirement-input" class="sim-input-inline"></div>
        <div id="sim-if-retirement-slider"></div>
      </div>

      <div class="sim-field">
        <div class="sim-field-label"><span class="lbl">Idade final</span><input type="text" id="sim-if-final-input" class="sim-input-inline"></div>
        <div id="sim-if-final-slider"></div>
      </div>

      <div class="sim-field">
        <div class="sim-field-label"><span class="lbl">Renda desejada / mês</span><input type="text" id="sim-if-income-input" class="sim-input-inline"></div>
        <div id="sim-if-income-slider"></div>
      </div>

      <div class="sim-field">
        <div class="sim-field-label"><span class="lbl">Aporte atual / mês</span><input type="text" id="sim-if-invest-input" class="sim-input-inline"></div>
        <div id="sim-if-invest-slider"></div>
      </div>
    </aside>
  </section>

  <!-- Lançamentos -->
  <section class="sim-block">
    <div class="sim-block-head">
      <div>
        <h2 class="sim-block-title">Lançamentos extras</h2>
        <div class="sim-block-sub">Aportes pontuais, vendas de imóvel, heranças, retiradas fora do plano</div>
      </div>
      <button class="sim-btn sim-btn-primary" id="sim-if-open-releases">+ Adicionar</button>
    </div>
    <div class="sim-table-wrap" style="max-height: 280px;">
      <table class="sim-table">
        <thead>
          <tr><th>Descrição</th><th>Tipo</th><th>Início</th><th>Recorrência</th><th>Valor</th><th>Ações</th></tr>
        </thead>
        <tbody id="sim-if-releases-list">
          <tr><td colspan="6" class="empty">Nenhum lançamento extra agendado.</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Tabela mês a mês -->
  <section class="sim-block">
    <div class="sim-block-head">
      <div>
        <h2 class="sim-block-title">Projeção mês a mês</h2>
        <div class="sim-block-sub">Detalhamento completo da simulação — exporte pra analisar fora</div>
      </div>
      <button class="sim-btn" id="sim-if-export-xlsx">Exportar .xlsx</button>
    </div>
    <div class="sim-table-wrap">
      <table class="sim-table">
        <thead>
          <tr>
            <th>Mês</th><th>Data</th><th>Idade</th><th>Aporte</th><th>Retirada</th><th>Taxa</th>
            <th>Patrimônio total</th><th>Principal</th><th>Alvo finito</th><th>Alvo vitalício</th>
          </tr>
        </thead>
        <tbody id="sim-if-detailed"></tbody>
      </table>
    </div>
  </section>

  <!-- Modais -->
  <div id="sim-if-modal-chart" class="sim-modal">
    <div class="sim-modal-content sim-modal-lg">
      <div class="sim-modal-head">
        <h3 class="sim-modal-title">Projeção de patrimônio <em>— expandida</em></h3>
        <button class="sim-close-btn" data-sim-close="sim-if-modal-chart">✕</button>
      </div>
      <div style="flex: 1; min-height: 0;">
        <canvas id="sim-if-fullscreen-chart" style="width: 100%; height: 100%;"></canvas>
      </div>
    </div>
  </div>

  <div id="sim-if-modal-settings" class="sim-modal">
    <div class="sim-modal-content">
      <div class="sim-modal-head">
        <h3 class="sim-modal-title">Taxas de <em>rendimento</em></h3>
        <button class="sim-close-btn" data-sim-close="sim-if-modal-settings">✕</button>
      </div>
      <div class="sim-field" style="margin-bottom: 20px;">
        <label class="sim-field-label"><span class="lbl">Juros real anual — acumulação (%)</span></label>
        <input type="number" id="sim-if-acc-rate" class="sim-input-raw" min="0" max="15" step="0.1" value="6">
        <div class="sim-age-display">Taxa esperada enquanto você acumula patrimônio</div>
      </div>
      <div class="sim-field">
        <label class="sim-field-label"><span class="lbl">Juros real anual — aposentadoria (%)</span></label>
        <input type="number" id="sim-if-ret-rate" class="sim-input-raw" min="0" max="10" step="0.1" value="4">
        <div class="sim-age-display">Taxa esperada após a independência (mais conservadora)</div>
      </div>
      <div class="sim-form-actions">
        <button class="sim-btn sim-btn-primary" data-sim-close="sim-if-modal-settings">Salvar</button>
      </div>
    </div>
  </div>

  <div id="sim-if-modal-release" class="sim-modal">
    <div class="sim-modal-content">
      <div class="sim-modal-head">
        <h3 class="sim-modal-title">Novo <em>lançamento</em></h3>
        <button class="sim-close-btn" data-sim-close="sim-if-modal-release">✕</button>
      </div>
      <div class="sim-field" style="margin-bottom: 16px;">
        <label class="sim-field-label"><span class="lbl">Descrição</span></label>
        <input type="text" id="sim-if-r-name" class="sim-input-raw" placeholder="Ex.: Venda de imóvel">
      </div>
      <div class="sim-form-grid" style="margin-bottom: 16px;">
        <div class="sim-field">
          <label class="sim-field-label"><span class="lbl">Tipo</span></label>
          <select id="sim-if-r-type" class="sim-input-raw">
            <option value="aporte">Aporte (+)</option>
            <option value="retirada">Retirada (−)</option>
          </select>
        </div>
        <div class="sim-field">
          <label class="sim-field-label"><span class="lbl">Início (mês/ano)</span></label>
          <input type="month" id="sim-if-r-date" class="sim-input-raw">
        </div>
      </div>
      <div class="sim-form-grid" style="margin-bottom: 16px;">
        <div class="sim-field">
          <label class="sim-field-label"><span class="lbl">Recorrência</span></label>
          <select id="sim-if-r-rec" class="sim-input-raw">
            <option value="none">Única</option>
            <option value="monthly">Mensal</option>
            <option value="yearly">Anual</option>
          </select>
        </div>
        <div class="sim-field" id="sim-if-r-inst-group" style="display: none;">
          <label class="sim-field-label"><span class="lbl">Quantas vezes</span></label>
          <input type="number" id="sim-if-r-inst" class="sim-input-raw" value="1" min="1" step="1">
        </div>
      </div>
      <div class="sim-field">
        <label class="sim-field-label"><span class="lbl">Valor (R$)</span></label>
        <input type="text" id="sim-if-r-value" class="sim-input-raw" placeholder="0,00">
      </div>
      <div class="sim-form-actions">
        <button class="sim-btn" data-sim-close="sim-if-modal-release">Cancelar</button>
        <button class="sim-btn sim-btn-primary" id="sim-if-save-release">Salvar</button>
      </div>
    </div>
  </div>

</div>
