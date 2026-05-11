/* Máscaras para CPF, telefone, dinheiro + toggle de campos condicionais */
(function () {
  'use strict';

  function maskCPF(v) {
    v = v.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    return v;
  }

  function maskPhone(v) {
    v = v.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
      v = v.replace(/(\d{2})(\d)/, '($1) $2');
      v = v.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
      v = v.replace(/(\d{2})(\d)/, '($1) $2');
      v = v.replace(/(\d{5})(\d)/, '$1-$2');
    }
    return v;
  }

  function maskMoney(v) {
    v = v.replace(/\D/g, '');
    if (!v) return '';
    v = (parseInt(v, 10) / 100).toFixed(2);
    v = v.replace('.', ',');
    v = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return 'R$ ' + v;
  }

  document.addEventListener('input', function (e) {
    var el = e.target;
    var mask = el.getAttribute && el.getAttribute('data-mask');
    if (!mask) return;
    if (mask === 'cpf') el.value = maskCPF(el.value);
    if (mask === 'phone') el.value = maskPhone(el.value);
    if (mask === 'money') el.value = maskMoney(el.value);
  });

  // Toggle de campos condicionais na profissão
  function toggleProfissao() {
    var sel = document.getElementById('ofr-profissao');
    if (!sel) return;
    var outraWrap = document.getElementById('ofr-profissao-outra-wrap');
    var aumWrap = document.getElementById('ofr-aum-wrap');
    var v = sel.value;
    if (outraWrap) outraWrap.style.display = v === 'outra' ? '' : 'none';
    if (aumWrap) {
      var comAum = ['assessor', 'consultor', 'bancario', 'planejador'];
      aumWrap.style.display = comAum.indexOf(v) > -1 ? '' : 'none';
    }
  }
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'ofr-profissao') toggleProfissao();
  });
  document.addEventListener('DOMContentLoaded', toggleProfissao);
})();
