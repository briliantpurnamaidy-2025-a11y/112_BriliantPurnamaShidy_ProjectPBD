'use strict';
const searchBox = document.querySelector('#searchRows');
if (searchBox) {
  const rows = Array.from(document.querySelectorAll('#masterTable tbody tr.filterable-row'));
  const indicator = document.querySelector('#visibleCount');
  const noMatches = document.querySelector('#noMatches');
  const refresh = () => {
    const q = searchBox.value.trim().toLocaleLowerCase('id');
    let n = 0;
    rows.forEach(row => {
      const show = row.textContent.toLocaleLowerCase('id').includes(q);
      row.hidden = !show;
      if (show) n++;
    });
    if (indicator) indicator.textContent = String(n);
    if (noMatches) noMatches.hidden = n !== 0 || rows.length === 0;
  };
  searchBox.addEventListener('input', refresh);
}
const toggle = document.querySelector('#menuToggle');
const sidebar = document.querySelector('#sidebar');
if (toggle && sidebar) {
  toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}

// Baris transaksi dinamis; semua harga/total tetap divalidasi di server dan dihitung MySQL.
const itemRows = document.querySelector('#itemRows[data-item-list]');
if (itemRows) {
  const kind = itemRows.dataset.itemList;
  const refreshRow = row => {
    const select = row.querySelector('.product-pick');
    const field = row.querySelector('.unit-price');
    if (!select || !field) return;
    const opt = select.selectedOptions[0];
    const price = opt?.dataset.price ?? '';
    if (kind === 'penjualan') {
      field.value = price ? Number(price).toLocaleString('id-ID') : '';
    } else if (select.value && price) {
      field.value = String(price);
    }
  };
  itemRows.addEventListener('change', e => {
    if (e.target.matches('.product-pick')) refreshRow(e.target.closest('.item-row'));
  });
  itemRows.addEventListener('click', e => {
    const btn = e.target.closest('.remove-row');
    if (btn) {
      const row = btn.closest('.item-row');
      if (itemRows.querySelectorAll('.item-row').length > 1) row.remove();
      else {
        row.querySelectorAll('input').forEach(input => {
          input.value = input.classList.contains('unit-price') ? '' : '1';
        });
        row.querySelectorAll('select').forEach(select => { select.value = ''; });
      }
    }
  });
  const add = document.querySelector('[data-add-item]');
  if (add) add.addEventListener('click', () => {
    if (itemRows.querySelectorAll('.item-row').length >= 30) return;
    const first = itemRows.querySelector('.item-row');
    if (!first) return;
    const clone = first.cloneNode(true);
    clone.querySelectorAll('select').forEach(select => select.value = '');
    clone.querySelectorAll('input').forEach(input => {
      input.value = input.classList.contains('unit-price') ? '' : '1';
    });
    itemRows.appendChild(clone);
  });
}
