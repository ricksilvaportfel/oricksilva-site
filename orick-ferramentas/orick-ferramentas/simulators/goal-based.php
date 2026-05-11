<?php
/**
 * Simulador: Goal Based Investing
 * Stub inicial — calcula aporte mensal necessário pra atingir um objetivo financeiro.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="gbi-sim">
  <style>
    .gbi-sim { max-width: 960px; margin: 0 auto; font-family: 'Inter', sans-serif; color: var(--ofr-fg); }
    .gbi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .gbi-card { border: 1px solid var(--ofr-border); padding: 32px; background: var(--ofr-bg-2); }
    .gbi-card h3 { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 400; margin: 0 0 20px; letter-spacing: -0.01em; }
    .gbi-field { margin-bottom: 18px; }
    .gbi-field label { display: block; font-family: 'JetBrains Mono', monospace; font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--ofr-fg-dim); margin-bottom: 6px; }
    .gbi-field input { width: 100%; background: var(--ofr-bg); border: 1px solid var(--ofr-border); color: var(--ofr-fg); padding: 12px 14px; font-family: inherit; font-size: 15px; }
    .gbi-field input:focus { outline: none; border-color: var(--ofr-accent); }
    .gbi-result-value { font-family: 'Fraunces', serif; font-size: 54px; font-weight: 400; line-height: 1; letter-spacing: -0.02em; color: var(--ofr-accent); margin: 8px 0 4px; }
    .gbi-result-label { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--ofr-fg-dim); }
    .gbi-summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--ofr-border); font-size: 14px; }
    .gbi-summary-row:last-child { border-bottom: 0; }
    .gbi-summary-row span:first-child { color: var(--ofr-fg-dim); }
    @media (max-width: 720px) { .gbi-grid { grid-template-columns: 1fr; gap: 24px; } }
  </style>

  <div class="gbi-grid">
    <div class="gbi-card">
      <h3>Seu objetivo</h3>
      <div class="gbi-field">
        <label>Valor desejado (R$)</label>
        <input type="text" id="gbi-meta" data-mask="money" value="R$ 1.000.000,00">
      </div>
      <div class="gbi-field">
        <label>Prazo (anos)</label>
        <input type="number" id="gbi-prazo" min="1" max="50" value="10">
      </div>
      <div class="gbi-field">
        <label>Aporte inicial (R$)</label>
        <input type="text" id="gbi-inicial" data-mask="money" value="R$ 10.000,00">
      </div>
      <div class="gbi-field">
        <label>Taxa de retorno anual (%)</label>
        <input type="number" id="gbi-taxa" step="0.1" min="0" max="30" value="10">
      </div>
    </div>

    <div class="gbi-card">
      <h3>Resultado</h3>
      <div class="gbi-result-label">Aporte mensal necessário</div>
      <div class="gbi-result-value" id="gbi-aporte">—</div>

      <div style="margin-top: 32px;">
        <div class="gbi-summary-row"><span>Total de aportes</span><span id="gbi-total-aportes">—</span></div>
        <div class="gbi-summary-row"><span>Rendimento</span><span id="gbi-rendimento">—</span></div>
        <div class="gbi-summary-row"><span>Patrimônio ao final</span><span id="gbi-patrimonio">—</span></div>
      </div>
    </div>
  </div>

  <script>
  (function(){
    function parseMoney(v) { return parseFloat(String(v).replace(/[^\d,]/g, '').replace(',', '.')) || 0; }
    function fmt(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function calc() {
      var FV = parseMoney(document.getElementById('gbi-meta').value);
      var anos = parseFloat(document.getElementById('gbi-prazo').value) || 0;
      var PV = parseMoney(document.getElementById('gbi-inicial').value);
      var taxaAnual = (parseFloat(document.getElementById('gbi-taxa').value) || 0) / 100;

      var n = anos * 12;
      var i = Math.pow(1 + taxaAnual, 1/12) - 1;

      // FV = PV*(1+i)^n + PMT * [((1+i)^n - 1) / i]
      var futInicial = PV * Math.pow(1 + i, n);
      var falta = FV - futInicial;
      var fator = i === 0 ? n : ((Math.pow(1 + i, n) - 1) / i);
      var PMT = fator > 0 ? falta / fator : 0;

      if (PMT < 0) PMT = 0;
      var totalAportes = PV + (PMT * n);
      var rend = FV - totalAportes;

      document.getElementById('gbi-aporte').textContent = fmt(PMT);
      document.getElementById('gbi-total-aportes').textContent = fmt(totalAportes);
      document.getElementById('gbi-rendimento').textContent = fmt(rend);
      document.getElementById('gbi-patrimonio').textContent = fmt(FV);
    }

    ['gbi-meta','gbi-prazo','gbi-inicial','gbi-taxa'].forEach(function(id){
      document.getElementById(id).addEventListener('input', calc);
    });
    calc();
  })();
  </script>
</div>
