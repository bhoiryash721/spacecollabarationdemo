/* ============================================================
   assets/js/search.js — Search autocomplete dropdown
   ============================================================ */
(function () {
  const input = document.getElementById('global-search');
  if (!input) return;

  // Create dropdown
  const dropdown = document.createElement('div');
  dropdown.id = 'search-dropdown';
  Object.assign(dropdown.style, {
    position: 'absolute', background: 'var(--bg-card)',
    border: '1px solid var(--border)', borderRadius: 'var(--radius)',
    boxShadow: 'var(--shadow)', zIndex: 1000, width: input.offsetWidth + 'px',
    display: 'none', maxHeight: '300px', overflowY: 'auto',
  });
  input.parentNode.style.position = 'relative';
  input.parentNode.appendChild(dropdown);

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { dropdown.style.display = 'none'; return; }

    timer = setTimeout(async () => {
      const res = await fetch((window.BASE_URL || '/') + 'api/search.php?q=' + encodeURIComponent(q), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const items = await res.json();
      if (!items.length) { dropdown.style.display = 'none'; return; }

      dropdown.innerHTML = items.map(i =>
        `<a href="${i.url}" style="display:block;padding:10px 16px;color:var(--text-primary);
          border-bottom:1px solid var(--border);font-size:.85rem;text-decoration:none"
          onmouseover="this.style.background='var(--bg-hover)'"
          onmouseout="this.style.background=''">${i.label}</a>`
      ).join('');
      dropdown.style.display = 'block';
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target)) dropdown.style.display = 'none';
  });
})();
