/**
 * Orick Ferramentas — Simulador Planejamento Independência Financeira
 *
 * Estado persistido em wp_orick_lead_tool_state (tool="planejamento-if")
 * via AJAX (debounced). Variáveis esperadas em window.OrickSimIF (wp_localize_script).
 */
(function () {
  'use strict';

  if (!window.OrickSimIF) {
    console.warn('[sim-if] OrickSimIF bootstrap ausente — nada a fazer.');
    return;
  }

  // ---------------------------------------------------------------------------
  // STATE
  // ---------------------------------------------------------------------------
  let financialChart = null;
  let fullscreenChart = null;
  const settings = { accumulationRate: 0.06, retirementRate: 0.04, finalAge: 90 };
  let releases = [];
  let saveTimer = null;
  let isBootstrapping = true; // evita salvar enquanto estamos carregando valores

  // ---------------------------------------------------------------------------
  // DOM SHORTCUTS
  // ---------------------------------------------------------------------------
  const $ = (id) => document.getElementById(id);
  const els = {};
  function wireElements() {
    [
      'sim-if-save-hint','sim-if-open-settings','sim-if-gen-pdf',
      'sim-if-lbl-final-age','sim-if-aporte-finito','sim-if-alvo-finito',
      'sim-if-aporte-vital','sim-if-alvo-vital',
      'sim-if-expand','sim-if-chart','sim-if-fullscreen-chart',
      'sim-if-birth','sim-if-current-age','sim-if-birth-error',
      'sim-if-retirement-input','sim-if-retirement-slider',
      'sim-if-final-input','sim-if-final-slider',
      'sim-if-income-input','sim-if-income-slider',
      'sim-if-invest-input','sim-if-invest-slider',
      'sim-if-open-releases','sim-if-releases-list','sim-if-detailed',
      'sim-if-export-xlsx',
      'sim-if-modal-chart','sim-if-modal-settings','sim-if-modal-release',
      'sim-if-acc-rate','sim-if-ret-rate',
      'sim-if-r-name','sim-if-r-type','sim-if-r-date','sim-if-r-rec',
      'sim-if-r-inst-group','sim-if-r-inst','sim-if-r-value','sim-if-save-release',
    ].forEach((id) => { els[id] = $(id); });
  }

  // ---------------------------------------------------------------------------
  // HELPERS
  // ---------------------------------------------------------------------------
  function mascaraMoeda(input) {
    let v = input.value.replace(/\D/g, '');
    if (v === '') { input.value = ''; return; }
    v = (parseInt(v, 10) / 100).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = v;
  }
  function getMoneyValue(str) {
    if (!str) return 0;
    if (typeof str === 'number') return str;
    return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
  }
  function fmtBR(value) {
    return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function calculateAge(birthDate) {
    const today = new Date(); const birth = new Date(birthDate);
    let years = today.getFullYear() - birth.getFullYear();
    let months = today.getMonth() - birth.getMonth();
    if (months < 0) { years--; months += 12; }
    return { years, months };
  }
  function showBirthDateError(show) {
    els['sim-if-birth-error'].classList.toggle('visible', show);
  }

  // ---------------------------------------------------------------------------
  // CHART
  // ---------------------------------------------------------------------------
  const watermarkPlugin = {
    id: 'watermark',
    beforeDraw: (chart) => {
      const ctx = chart.ctx;
      ctx.save();
      ctx.fillStyle = 'rgba(228,216,199,0.035)';
      ctx.font = "400 5vw 'Fraunces', serif";
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.translate(chart.width / 2, chart.height / 2);
      ctx.rotate(-Math.PI / 12);
      ctx.fillText('@oricksilva', 0, 0);
      ctx.restore();
    },
  };

  function buildChartConfig() {
    const gridColor = 'rgba(228,216,199,0.06)';
    const textColor = 'rgba(228,216,199,0.56)';
    return {
      type: 'line',
      data: {
        labels: [],
        datasets: [
          { label: 'Patrimônio projetado', backgroundColor: 'rgba(228,216,199,0.08)', borderColor: '#E4D8C7', borderWidth: 2, pointRadius: 0, tension: 0.35, fill: true, data: [] },
          { label: 'Principal investido', backgroundColor: 'rgba(228,216,199,0.02)', borderColor: 'rgba(228,216,199,0.5)', borderWidth: 1.5, pointRadius: 0, tension: 0.35, fill: false, borderDash: [2, 3], data: [] },
          { label: 'Alvo — Finito', backgroundColor: 'transparent', borderColor: '#A75232', borderWidth: 2, pointRadius: 0, tension: 0.35, fill: false, data: [] },
          { label: 'Alvo — Vitalício', backgroundColor: 'transparent', borderColor: '#7A8B5C', borderWidth: 2, pointRadius: 0, tension: 0.35, fill: false, data: [] },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: gridColor, lineWidth: 1, drawTicks: false },
            ticks: {
              color: textColor,
              callback: (v) => v >= 1e6 ? `R$ ${(v / 1e6).toFixed(1)}M` : `R$ ${(v / 1000).toFixed(0)}k`,
              font: { family: "'JetBrains Mono', monospace", size: 11 },
              padding: 10,
            },
            border: { display: false },
          },
          x: {
            grid: { display: false },
            ticks: {
              color: textColor,
              font: { family: "'JetBrains Mono', monospace", size: 11 },
              padding: 6, maxRotation: 0,
              callback: function (val) {
                const lbl = this.getLabelForValue(val);
                return (lbl % 5 === 0) ? lbl : '';
              },
            },
            border: { display: false },
          },
        },
        plugins: {
          tooltip: {
            backgroundColor: '#14130F',
            titleColor: '#E4D8C7', bodyColor: '#E4D8C7',
            borderColor: 'rgba(228,216,199,0.18)', borderWidth: 1,
            titleFont: { family: "'Fraunces', serif", size: 14, weight: '500' },
            bodyFont: { family: "'JetBrains Mono', monospace", size: 12 },
            padding: 12, cornerRadius: 0, boxPadding: 6,
            callbacks: {
              title: (c) => `Idade: ${c[0].label} anos`,
              label: (c) => ` ${c.dataset.label}: R$ ${c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`,
            },
          },
          legend: {
            position: 'top', align: 'end',
            labels: {
              color: textColor,
              font: { family: "'Inter', sans-serif", size: 11, weight: '500' },
              usePointStyle: true, pointStyle: 'rectRounded',
              boxWidth: 10, boxHeight: 10, padding: 14,
            },
          },
        },
      },
      plugins: [watermarkPlugin],
    };
  }

  function initializeChart() {
    if (financialChart) financialChart.destroy();
    financialChart = new Chart(els['sim-if-chart'].getContext('2d'), buildChartConfig());
  }

  // ---------------------------------------------------------------------------
  // CALCULATIONS (mantém a matemática do original)
  // ---------------------------------------------------------------------------
  function calculateRequiredSavings(targetWealth, yearsToRetirement, accumulationRate) {
    const monthlyRate = accumulationRate / 12;
    const months = yearsToRetirement * 12;
    if (months <= 0) return 0;
    const denom = ((Math.pow(1 + monthlyRate, months) - 1) / monthlyRate) * (1 + monthlyRate);
    return targetWealth / denom;
  }

  function updateAgeDisplay() {
    const birth = els['sim-if-birth'].value;
    if (!birth) return;
    const age = calculateAge(birth);
    els['sim-if-current-age'].textContent = `Idade atual: ${age.years} anos e ${age.months} meses`;
  }

  function checkReleaseApplication(release, yyyy, mm) {
    if (!release.date) return false;
    const [ry, rm] = release.date.split('-').map(Number);
    const relAbs = ry * 12 + (rm - 1);
    const curAbs = yyyy * 12 + mm;
    const diff = curAbs - relAbs;
    if (diff < 0) return false;
    if (release.recurrence === 'none' && diff === 0) return true;
    if (release.recurrence === 'monthly' && diff < release.installments) return true;
    if (release.recurrence === 'yearly' && diff % 12 === 0 && (diff / 12) < release.installments) return true;
    return false;
  }

  function updateCalculations() {
    const retirementAge = parseInt(els['sim-if-retirement-input'].value, 10);
    const desiredIncome = getMoneyValue(els['sim-if-income-input'].value);
    const monthlyInvest = getMoneyValue(els['sim-if-invest-input'].value);
    const finalAge = settings.finalAge;

    const birthDate = new Date(els['sim-if-birth'].value);
    if (isNaN(birthDate.getTime()) || birthDate > new Date()) { showBirthDateError(true); return; }
    showBirthDateError(false);

    const age = calculateAge(els['sim-if-birth'].value);
    const yearsToRetirement = retirementAge - age.years;
    const monthlyRetRate = settings.retirementRate / 12;
    const monthsInRet = (finalAge - retirementAge) * 12;

    // Finito
    let wealthFinite = 0;
    if (monthsInRet > 0 && monthlyRetRate > 0) {
      wealthFinite = desiredIncome * (1 - Math.pow(1 + monthlyRetRate, -monthsInRet)) / monthlyRetRate;
    } else {
      wealthFinite = desiredIncome * monthsInRet;
    }
    const savingsFinite = calculateRequiredSavings(wealthFinite, yearsToRetirement, settings.accumulationRate);

    // Vitalício
    let wealthPerp = 0;
    if (monthlyRetRate > 0) wealthPerp = desiredIncome / monthlyRetRate;
    const savingsPerp = calculateRequiredSavings(wealthPerp, yearsToRetirement, settings.accumulationRate);

    els['sim-if-alvo-finito'].textContent = `R$ ${fmtBR(wealthFinite)}`;
    els['sim-if-aporte-finito'].textContent = `R$ ${fmtBR(savingsFinite)}`;
    els['sim-if-lbl-final-age'].textContent = finalAge;
    els['sim-if-alvo-vital'].textContent = `R$ ${fmtBR(wealthPerp)}`;
    els['sim-if-aporte-vital'].textContent = `R$ ${fmtBR(savingsPerp)}`;

    runSimulation(age, retirementAge, finalAge, monthlyInvest, desiredIncome, wealthFinite, savingsFinite, wealthPerp, savingsPerp);
  }

  function runSimulation(age, retirementAge, finalAge, monthlyInvest, desiredIncome, wealthFinite, savingsFinite, wealthPerp, savingsPerp) {
    const tbody = els['sim-if-detailed'];
    let curWealth = 0, curPrincipal = 0;
    let idealFinite = 0, idealPerp = 0;
    const rows = [];
    const totalMonths = (finalAge - age.years) * 12;
    const retMonth = (retirementAge - age.years) * 12;
    const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    const labels = [age.years], total = [0], principal = [0], iFinite = [0], iPerp = [0];

    for (let m = 0; m < totalMonths; m++) {
      const d = new Date(); d.setMonth(d.getMonth() + m);
      const yyyy = d.getFullYear(), mm = d.getMonth();
      const pAge = { years: age.years + Math.floor((age.months + m) / 12), months: (age.months + m) % 12 };
      const isRet = m >= retMonth;
      const rate = isRet ? settings.retirementRate / 12 : settings.accumulationRate / 12;

      curWealth = curWealth * (1 + rate);
      let dep = 0, wd = 0;

      if (!isRet) {
        dep += monthlyInvest;
        curWealth += monthlyInvest;
        curPrincipal += monthlyInvest;
        idealFinite = idealFinite * (1 + rate) + savingsFinite;
        idealPerp = idealPerp * (1 + rate) + savingsPerp;
      } else {
        wd += desiredIncome;
        curWealth = Math.max(0, curWealth - desiredIncome);
        curPrincipal = Math.max(0, curPrincipal - desiredIncome);
        if (m === retMonth) idealFinite = wealthFinite;
        else idealFinite = Math.max(0, idealFinite * (1 + rate) - desiredIncome);
        if (m === retMonth) idealPerp = wealthPerp;
        else idealPerp = Math.max(0, idealPerp * (1 + rate) - desiredIncome);
      }

      for (const r of releases) {
        if (checkReleaseApplication(r, yyyy, mm)) {
          if (r.value > 0) dep += r.value; else wd += Math.abs(r.value);
          curWealth += r.value;
          curPrincipal += r.value;
        }
      }

      rows.push(
        `<tr>
          <td>${m + 1}</td>
          <td>${monthNames[mm]}/${yyyy}</td>
          <td>${pAge.years}a ${pAge.months}m</td>
          <td>R$ ${fmtBR(dep)}</td>
          <td>R$ ${fmtBR(wd)}</td>
          <td>${(rate * 100).toFixed(2)}%</td>
          <td class="val-total">R$ ${fmtBR(Math.max(0, curWealth))}</td>
          <td>R$ ${fmtBR(Math.max(0, curPrincipal))}</td>
          <td class="val-finite">R$ ${fmtBR(Math.max(0, idealFinite))}</td>
          <td class="val-perp">R$ ${fmtBR(Math.max(0, idealPerp))}</td>
        </tr>`
      );

      if (pAge.months === 11 || m === totalMonths - 1) {
        labels.push(pAge.years + 1);
        total.push(Math.max(0, curWealth));
        principal.push(Math.max(0, curPrincipal));
        iFinite.push(Math.max(0, idealFinite));
        iPerp.push(Math.max(0, idealPerp));
      }
    }

    tbody.innerHTML = rows.join('');
    financialChart.data.labels = labels;
    financialChart.data.datasets[0].data = total;
    financialChart.data.datasets[1].data = principal;
    financialChart.data.datasets[2].data = iFinite;
    financialChart.data.datasets[3].data = iPerp;
    financialChart.update();

    if (fullscreenChart && els['sim-if-modal-chart'].classList.contains('active')) {
      fullscreenChart.data = JSON.parse(JSON.stringify(financialChart.data));
      fullscreenChart.update();
    }
  }

  // ---------------------------------------------------------------------------
  // SLIDERS
  // ---------------------------------------------------------------------------
  function initializeSliders() {
    const makeSlider = (sliderId, inputId, config, isMoney) => {
      const slider = els[sliderId], input = els[inputId];
      noUiSlider.create(slider, { start: config.start, connect: 'lower', range: config.range, step: config.step });
      slider.noUiSlider.on('update', (values) => {
        const val = parseFloat(values[0]);
        if (isMoney) {
          if (document.activeElement !== input) input.value = fmtBR(val);
        } else {
          input.value = Math.round(val);
        }
        updateCalculations();
        if (!isBootstrapping) scheduleSave();
      });
      if (!isMoney) input.addEventListener('change', function () { slider.noUiSlider.set(this.value); });
      if (isMoney) {
        input.addEventListener('input', function () {
          mascaraMoeda(this);
          slider.noUiSlider.set(getMoneyValue(this.value));
        });
        input.addEventListener('change', function () {
          this.value = fmtBR(parseFloat(slider.noUiSlider.get()));
          updateCalculations();
        });
      }
    };
    makeSlider('sim-if-retirement-slider', 'sim-if-retirement-input', { start: 65, range: { min: 45, max: 80 }, step: 1 }, false);
    makeSlider('sim-if-final-slider', 'sim-if-final-input', { start: 90, range: { min: 40, max: 120 }, step: 1 }, false);
    makeSlider('sim-if-income-slider', 'sim-if-income-input', { start: 10000, range: { min: 0, max: 100000 }, step: 1000 }, true);
    makeSlider('sim-if-invest-slider', 'sim-if-invest-input', { start: 2000, range: { min: 0, max: 100000 }, step: 100 }, true);
    els['sim-if-final-slider'].noUiSlider.on('update', (values) => { settings.finalAge = Math.round(values[0]); });
  }

  // ---------------------------------------------------------------------------
  // RELEASES
  // ---------------------------------------------------------------------------
  function toggleInstallments() {
    const rec = els['sim-if-r-rec'].value;
    els['sim-if-r-inst-group'].style.display = rec === 'none' ? 'none' : 'flex';
  }
  function saveRelease() {
    const name = els['sim-if-r-name'].value;
    const type = els['sim-if-r-type'].value;
    const date = els['sim-if-r-date'].value;
    const rawValue = getMoneyValue(els['sim-if-r-value'].value);
    const recurrence = els['sim-if-r-rec'].value;
    const installments = parseInt(els['sim-if-r-inst'].value, 10) || 1;
    if (!date || rawValue <= 0) { alert('Preencha a data e o valor corretamente.'); return; }
    releases.push({ id: Date.now(), name, type, date, recurrence, installments, value: type === 'retirada' ? -rawValue : rawValue });
    releases.sort((a, b) => new Date(a.date) - new Date(b.date));
    updateReleasesList();
    updateCalculations();
    scheduleSave();
    closeModal('sim-if-modal-release');
    ['sim-if-r-name','sim-if-r-date','sim-if-r-value'].forEach((id) => els[id].value = '');
    els['sim-if-r-rec'].value = 'none';
    els['sim-if-r-inst'].value = '1';
    toggleInstallments();
  }
  function deleteRelease(id) {
    releases = releases.filter((r) => r.id !== id);
    updateReleasesList();
    updateCalculations();
    scheduleSave();
  }
  function editRelease(id) {
    const r = releases.find((x) => x.id === id); if (!r) return;
    els['sim-if-r-name'].value = r.name || '';
    els['sim-if-r-type'].value = r.value < 0 ? 'retirada' : 'aporte';
    els['sim-if-r-date'].value = r.date;
    els['sim-if-r-rec'].value = r.recurrence || 'none';
    els['sim-if-r-inst'].value = r.installments || 1;
    els['sim-if-r-value'].value = fmtBR(Math.abs(r.value));
    toggleInstallments();
    openModal('sim-if-modal-release');
    deleteRelease(id);
  }
  function updateReleasesList() {
    const tbody = els['sim-if-releases-list'];
    if (releases.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="empty">Nenhum lançamento extra agendado.</td></tr>'; return; }
    tbody.innerHTML = releases.map((r) => {
      const [y, m] = r.date.split('-');
      const recText = r.recurrence === 'none' ? 'Única' : r.recurrence === 'monthly' ? `Mensal (${r.installments}x)` : `Anual (${r.installments}x)`;
      const type = r.value < 0 ? 'retirada' : 'aporte';
      return `<tr>
        <td>${(r.name || '—').replace(/</g,'&lt;')}</td>
        <td><span class="sim-tag ${type}">${type}</span></td>
        <td>${m}/${y}</td>
        <td>${recText}</td>
        <td class="${r.value < 0 ? 'val-down' : 'val-up'}">R$ ${fmtBR(Math.abs(r.value))}</td>
        <td>
          <button class="sim-btn sim-btn-sm" data-edit="${r.id}">Editar</button>
          <button class="sim-btn sim-btn-sm" data-del="${r.id}" style="color:#C06B5A;border-color:rgba(192,107,90,0.3);">Excluir</button>
        </td>
      </tr>`;
    }).join('');
    // bind ações
    tbody.querySelectorAll('[data-edit]').forEach((b) => b.addEventListener('click', () => editRelease(parseInt(b.dataset.edit, 10))));
    tbody.querySelectorAll('[data-del]').forEach((b) => b.addEventListener('click', () => deleteRelease(parseInt(b.dataset.del, 10))));
  }

  // ---------------------------------------------------------------------------
  // MODAIS
  // ---------------------------------------------------------------------------
  function openModal(id) { els[id].classList.add('active'); }
  function closeModal(id) {
    els[id].classList.remove('active');
    if (id === 'sim-if-modal-settings') { updateRates(); }
  }
  function openChartModal() {
    openModal('sim-if-modal-chart');
    if (!fullscreenChart) {
      fullscreenChart = new Chart(
        els['sim-if-fullscreen-chart'].getContext('2d'),
        {
          type: 'line',
          data: JSON.parse(JSON.stringify(financialChart.data)),
          options: JSON.parse(JSON.stringify(financialChart.options)),
          plugins: [watermarkPlugin],
        }
      );
    } else {
      fullscreenChart.data = JSON.parse(JSON.stringify(financialChart.data));
      fullscreenChart.update();
    }
  }
  function updateRates() {
    settings.accumulationRate = parseFloat(els['sim-if-acc-rate'].value) / 100;
    settings.retirementRate   = parseFloat(els['sim-if-ret-rate'].value) / 100;
    updateCalculations();
    scheduleSave();
  }

  // ---------------------------------------------------------------------------
  // PERSISTÊNCIA (AJAX)
  // ---------------------------------------------------------------------------
  function setSaveHint(mode) {
    const el = els['sim-if-save-hint'];
    el.classList.remove('saving', 'saved');
    if (mode === 'saving') { el.textContent = 'Salvando…'; el.classList.add('saving'); }
    else if (mode === 'saved') { el.textContent = 'Salvo'; el.classList.add('saved'); setTimeout(() => { if (el.textContent === 'Salvo') el.textContent = ''; }, 2000); }
    else if (mode === 'err') { el.textContent = 'Erro ao salvar — sessão pode ter expirado'; }
    else { el.textContent = ''; }
  }

  function collectState() {
    return {
      birthDate: els['sim-if-birth'].value,
      retirementAge: parseInt(els['sim-if-retirement-input'].value, 10),
      desiredIncome: getMoneyValue(els['sim-if-income-input'].value),
      monthlyInvestment: getMoneyValue(els['sim-if-invest-input'].value),
      accumulationRate: settings.accumulationRate,
      retirementRate: settings.retirementRate,
      finalAge: settings.finalAge,
      releases,
    };
  }

  function scheduleSave() {
    if (isBootstrapping) return;
    clearTimeout(saveTimer);
    setSaveHint('saving');
    saveTimer = setTimeout(doSave, 600);
  }

  function doSave() {
    const state = collectState();
    const body = new URLSearchParams({
      action: 'orick_ferr_save_state',
      _nonce: OrickSimIF.nonce,
      tool: OrickSimIF.tool,
      data: JSON.stringify(state),
    });
    fetch(OrickSimIF.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    })
      .then((r) => r.json())
      .then((json) => { if (json && json.success) setSaveHint('saved'); else setSaveHint('err'); })
      .catch(() => setSaveHint('err'));
  }

  function applyState(s) {
    if (!s || typeof s !== 'object') return;
    if (s.birthDate) { els['sim-if-birth'].value = s.birthDate; updateAgeDisplay(); }
    if (typeof s.retirementAge === 'number') els['sim-if-retirement-slider'].noUiSlider.set(s.retirementAge);
    if (typeof s.desiredIncome === 'number') els['sim-if-income-slider'].noUiSlider.set(s.desiredIncome);
    if (typeof s.monthlyInvestment === 'number') els['sim-if-invest-slider'].noUiSlider.set(s.monthlyInvestment);
    if (typeof s.finalAge === 'number') { settings.finalAge = s.finalAge; els['sim-if-final-slider'].noUiSlider.set(s.finalAge); }
    if (typeof s.accumulationRate === 'number') { settings.accumulationRate = s.accumulationRate; els['sim-if-acc-rate'].value = s.accumulationRate * 100; }
    if (typeof s.retirementRate === 'number')   { settings.retirementRate = s.retirementRate;   els['sim-if-ret-rate'].value = s.retirementRate * 100; }
    if (Array.isArray(s.releases)) { releases = s.releases; updateReleasesList(); }
  }

  // ---------------------------------------------------------------------------
  // EXPORTS
  // ---------------------------------------------------------------------------
  function exportToExcel() {
    const rows = Array.from(els['sim-if-detailed'].getElementsByTagName('tr'));
    const wsData = [['Mês','Data','Idade','Aporte','Retirada','Taxa','Patrimônio total','Principal','Alvo finito','Alvo vitalício']];
    rows.forEach((row) => {
      wsData.push(Array.from(row.getElementsByTagName('td')).map((cell) => {
        const v = cell.textContent.replace('R$ ', '').replace('%', '').replace(/\./g, '').replace(',', '.');
        return isNaN(v) ? cell.textContent : parseFloat(v);
      }));
    });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(wsData), 'Plano IF');
    XLSX.writeFile(wb, 'Plano_Independencia_Financeira.xlsx');
  }
  function generatePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    const target = document.querySelector('.sim-if');
    html2canvas(target, { scale: 2, useCORS: true, backgroundColor: '#1F1E1C' }).then((canvas) => {
      const imgWidth = doc.internal.pageSize.getWidth();
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      doc.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 20, imgWidth, imgHeight);
      doc.save('Plano_Independencia_Financeira.pdf');
    });
  }

  // ---------------------------------------------------------------------------
  // BOOT
  // ---------------------------------------------------------------------------
  function bindEvents() {
    els['sim-if-open-settings'].addEventListener('click', () => openModal('sim-if-modal-settings'));
    els['sim-if-gen-pdf'].addEventListener('click', generatePDF);
    els['sim-if-expand'].addEventListener('click', openChartModal);
    els['sim-if-export-xlsx'].addEventListener('click', exportToExcel);
    els['sim-if-open-releases'].addEventListener('click', () => openModal('sim-if-modal-release'));
    els['sim-if-save-release'].addEventListener('click', saveRelease);
    els['sim-if-r-rec'].addEventListener('change', toggleInstallments);
    els['sim-if-r-value'].addEventListener('input', function () { mascaraMoeda(this); });
    els['sim-if-acc-rate'].addEventListener('change', updateRates);
    els['sim-if-ret-rate'].addEventListener('change', updateRates);

    // Botões com data-sim-close (fecham modal correspondente)
    document.querySelectorAll('.sim-if [data-sim-close]').forEach((b) => {
      b.addEventListener('click', () => closeModal(b.dataset.simClose));
    });

    // Fechar modal ao clicar fora do content
    document.querySelectorAll('.sim-if .sim-modal').forEach((m) => {
      m.addEventListener('click', (e) => { if (e.target === m) m.classList.remove('active'); });
    });

    // Data de nascimento
    els['sim-if-birth'].addEventListener('change', function () {
      if (isNaN(new Date(this.value).getTime()) || new Date(this.value) > new Date()) showBirthDateError(true);
      else { showBirthDateError(false); updateAgeDisplay(); updateCalculations(); scheduleSave(); }
    });
  }

  function boot() {
    wireElements();
    bindEvents();
    initializeSliders();

    // Se veio state do servidor, aplica; senão default de 30 anos atrás
    if (OrickSimIF.state) {
      applyState(OrickSimIF.state);
    } else {
      const d = new Date(); d.setFullYear(d.getFullYear() - 30);
      els['sim-if-birth'].value = d.toISOString().split('T')[0];
      updateAgeDisplay();
    }

    initializeChart();
    updateCalculations();
    isBootstrapping = false;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
