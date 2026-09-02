// RESQZONE shared front-end utilities
(function () {
  'use strict';

  window.HZ = window.HZ || {};

  // ---- Toast helper -----------------------------------------------------
  HZ.toast = function (message, type = 'info') {
    let holder = document.getElementById('hzToastHolder');
    if (!holder) {
      holder = document.createElement('div');
      holder.id = 'hzToastHolder';
      holder.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      document.body.appendChild(holder);
    }
    const colors = { success: 'success', error: 'danger', info: 'primary', warning: 'warning' };
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${colors[type] || 'primary'} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    holder.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3500 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };

  // ---- Generic client-side table filter/search --------------------------
  HZ.filterTable = function (tableId, filters) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
      let visible = true;
      for (const key in filters) {
        const val = (filters[key] || '').toLowerCase();
        if (!val) continue;
        const cell = row.getAttribute('data-' + key);
        if (cell === null) continue;
        if (!cell.toLowerCase().includes(val)) visible = false;
      }
      row.style.display = visible ? '' : 'none';
    });
  };

  // ---- Poll unread alert count for the navbar bell -----------------------
  function pollAlerts() {
    const base = window.HZ_BASE || '';
    fetch(base + 'api/alerts.php?action=unread_count')
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        const dot = document.getElementById('navAlertDot');
        if (!dot || !data) return;
        dot.classList.toggle('show', (data.count || 0) > 0);
      })
      .catch(() => {});
  }
  document.addEventListener('DOMContentLoaded', pollAlerts);

  // ---- Enable all Bootstrap tooltips -------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  });
})();
