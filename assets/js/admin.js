/* ================================================================
   MAPAWA BAPTIST YOUTH — Admin JS
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Mobile sidebar toggle ─────────────────────────────────────
  const toggle = document.querySelector('.admin-menu-toggle');
  if (toggle) {
    document.addEventListener('click', (e) => {
      const sidebar = document.querySelector('.admin-sidebar');
      if (!sidebar) return;
      if (toggle.contains(e.target)) {
        sidebar.classList.toggle('open');
      } else if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ── Auto-dismiss alerts after 5 seconds ───────────────────────
  document.querySelectorAll('.a-alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });

  // ── Confirm delete buttons ────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Upload zone drag-and-drop ─────────────────────────────────
  document.querySelectorAll('.upload-zone').forEach(zone => {
    zone.addEventListener('dragover', e => {
      e.preventDefault();
      zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('dragover');
      const input = zone.querySelector('input[type="file"]');
      if (input && e.dataTransfer.files.length) {
        // Manually assign files
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  // ── Current year ──────────────────────────────────────────────
  const yr = document.getElementById('current-year');
  if (yr) yr.textContent = new Date().getFullYear();

});
