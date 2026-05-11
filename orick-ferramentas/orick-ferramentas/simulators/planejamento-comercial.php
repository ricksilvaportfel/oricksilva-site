<?php
/**
 * Simulador: Planejamento Comercial
 * Stub — calcula receita projetada a partir de AuM, taxa média e crescimento.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="pc-sim">
  <style>
    .pc-sim { max-width: 960px; margin: 0 auto; font-family: 'Inter', sans-serif; color: var(--ofr-fg); }
    .pc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .pc-card { border: 1px solid var(--ofr-border); padding: 32px; background: var(--ofr-bg-2); }
    .pc-card h3 { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 400; margin: 0 0 20px; letter-spacing: -0.01em; }
    .pc-field { margin-bottom: 18px; }
    .pc-field label { display: block; font-family: 'JetBrains Mono', monospace; font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--ofr-fg-dim); margin-bottom: 6px; }
    .pc-field input { width: 100%; background: var(--ofr-bg); border: 1px solid var(--ofr-border); color: var(--ofr-fg); padding: 12px 14px; font-family: inherit; font-size: 15px; }
    .pc-field input:focus { outline: none; border-color: var(--ofr-accent); }
    .pc-result { font-family: 'Fraunces', serif; font-size: 48px; font-weight: 400; line-height: 1; letter-spacing: -0.02em; color: var(--ofr-accent); margin: 8px 0 4px; }
    .pc-label { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--ofr-fg-dim); }
    .pc-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--ofr-border); font-size: 14px; }
    .pc-row:last-child { border-bottom: 0; }
    .pc-row span:first-child { color: var(--ofr-fg-dim); }
    @media (max-width: 720px) { .pc-grid { grid-template-columns: 1fr; gap: 24px; } }
  </style>

  <div class="pc-grid">
    <div class="pc-card">
      <h3>Sua operação hoje</h3>
      <div class="pc-field">
        <label>AuM atual (R$)</label>
        <input type="text" id="pc-aum" data-mask="money" value="R$ 50.000.000,00">
      </div>
      <div class="pc-field">
        <label>Taxa média anual sobre AuM (%)</label>
        <input type="number" id="pc-taxa" step="0.05" min="0" max="5" value="0.8">
      </div>
      <div class="pc-field">
        <label>Novos aportes/mês esperados (R$)</label>
        <input type="text" id="pc-novos" data-mask="money" value="R$ 500.000,00">
      </div>
      <div class="pc-field">
        <label>Horizonte (meses)</label>
        <input type="number" id="pc-meses" min="1" max="60" value="12">
      </div>
    </div>

    <div class="pc-card">
      <h3>Projeção</h3>
      <div class="pc-label">Receita no período</div>
      <div class="pc-result" id="pc-receita">—</div>

      <div style="margin-top: 32px;">
        <div class="pc-row"><span>AuM ao final</span><span id="pc-aum-final">—</span></div>
        <div class="pc-row"><span>Receita média mensal</span><span id="pc-receita-mes">—</span></div>
        <div class="pc-row"><span>Crescimento em AuM</span><span id="pc-cresc">—</span></div>
      </div>
    </div>
  </div>

  <script>
  (function(){
    function parseMoney(v) { return parseFloat(String(v).replace(/[^\d,]/g, '').replace(',', '.')) || 0; }
    function fmt(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }

    function calc() {
      var AuM = parseMoney(document.getElementById('pc-aum').value);
      var taxa = (parseFloat(document.getElementById('pc-taxa').value) || 0) / 100;
      var novo = parseMoney(document.getElementById('pc-novos').value);
      var meses = parseInt(document.getElementById('pc-meses').value) || 0;

      var receita = 0;
      var aum = AuM;
      for (var i = 0; i < meses; i++) {
        aum += novo;
        receita += aum * (taxa / 12);
      }

      document.getElementById('pc-receita').textContent = fmt(receita);
      document.getElementById('pc-aum-final').textContent = fmt(aum);
      document.getElementById('pc-receita-mes').textContent = meses > 0 ? fmt(receita / meses) : '—';
      document.getElementById('pc-cresc').textContent = fmt(aum - AuM);
    }

    ['pc-aum','pc-taxa','pc-novos','pc-meses'].forEach(function(id){
      document.getElementById(id).addEventListener('input', calc);
    });
    calc();
  })();
  </script>
</div>
