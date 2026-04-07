/* ============================================================
   assets/js/main.js — SpaceCollab Client-side Logic
   ============================================================ */

'use strict';

// Get BASE_URL from meta tag or default to root
const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/';

// ── AJAX Helper ──────────────────────────────────────────────
async function ajax(url, data = {}, method = 'POST') {
  const body = new URLSearchParams(data);
  const requestUrl = method === 'GET' && Object.keys(data).length ? `${url}?${body}` : url;

  try {
    const res = await fetch(requestUrl, {
      method,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                 'X-Requested-With': 'XMLHttpRequest' },
      body: method === 'GET' ? undefined : body,
    });
    return await res.json();
  } catch (err) {
    console.error('AJAX error:', err);
    return { error: 'Network error. Please try again.' };
  }
}

let currentUnreadCount = 0;
let notificationPanelOpen = false;
const notificationPanelId = 'notif-panel';

function parseBadgeCount(text) {
  if (!text) return 0;
  return text === '9+' ? 9 : Number(text.replace(/[^0-9]/g, '')) || 0;
}

function setBadgeCount(count, highlight = false) {
  let badge = document.querySelector('.notif-badge');
  if (!badge) {
    const btn = document.querySelector('.notif-btn');
    if (!btn) return;
    badge = document.createElement('span');
    badge.className = 'notif-badge';
    btn.appendChild(badge);
  }

  badge.textContent = count > 9 ? '9+' : String(count);
  badge.style.animation = highlight ? 'pulse 1s ease' : 'none';
  if (highlight) {
    setTimeout(() => { if (badge) badge.style.animation = 'none'; }, 1000);
  }
}

function renderNotificationPanel(notifications, panel) {
  panel.innerHTML = notifications.length
    ? notifications.map(n => `
        <div style="padding:10px 0;border-bottom:1px solid var(--border)">
          <p style="font-size:.82rem;color:${n.is_read ? 'var(--text-muted)' : 'var(--text-primary)'}">${n.message}</p>
          <span style="font-size:.7rem;color:var(--text-muted)">${n.created_at}</span>
        </div>`).join('')
    : '<p class="text-muted text-center" style="padding:20px">No notifications</p>';
}

async function pollNotifications() {
  const res = await ajax(BASE_URL + 'api/notifications.php', { action: 'count' }, 'GET');
  if (res.error || typeof res.count === 'undefined') return;

  const unreadCount = Number(res.count);
  if (unreadCount !== currentUnreadCount) {
    const shouldHighlight = unreadCount > currentUnreadCount;
    currentUnreadCount = unreadCount;
    setBadgeCount(unreadCount, shouldHighlight);

    if (notificationPanelOpen) {
      await refreshNotificationPanel();
    }
  }
}

async function refreshNotificationPanel() {
  if (!notificationPanelOpen) return;
  const panel = document.getElementById(notificationPanelId);
  if (!panel) { notificationPanelOpen = false; return; }

  panel.innerHTML = '<div class="spinner" style="margin:20px auto;display:block"></div>';
  const res = await ajax(BASE_URL + 'api/notifications.php', {}, 'GET');
  if (res.error) {
    panel.innerHTML = `<p class="text-muted">${res.error}</p>`;
    return;
  }

  renderNotificationPanel(res, panel);
}

// ── Flash / Toast ────────────────────────────────────────────
function toast(message, type = 'info', duration = 3500) {
  const wrap = document.getElementById('toast-wrap') || (() => {
    const d = document.createElement('div');
    d.id = 'toast-wrap';
    Object.assign(d.style, {
      position: 'fixed', bottom: '24px', right: '24px',
      zIndex: 9999, display: 'flex', flexDirection: 'column', gap: '10px',
    });
    document.body.appendChild(d);
    return d;
  })();

  const icons = { success: '✅', danger: '❌', info: 'ℹ️', warning: '⚠️' };
  const t = document.createElement('div');
  t.className = `alert alert-${type}`;
  t.style.cssText = 'min-width:260px;max-width:360px;animation:slideIn .3s ease;box-shadow:0 8px 32px rgba(0,0,0,0.4)';
  t.innerHTML = `${icons[type] || ''} ${message}`;
  wrap.appendChild(t);

  const style = document.createElement('style');
  style.textContent = '@keyframes slideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}';
  document.head.appendChild(style);

  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, duration);
}

// ── Like / Unlike ────────────────────────────────────────────
document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.like-btn');
  if (!btn) return;
  btn.disabled = true;
  const entityType = btn.dataset.type;
  const entityId   = btn.dataset.id;
  const countEl    = btn.querySelector('.like-count');

  const res = await ajax(BASE_URL + 'api/like.php', { entity_type: entityType, entity_id: entityId });
  if (res.error) { toast(res.error, 'danger'); }
  else {
    btn.classList.toggle('liked', res.liked);
    if (countEl) countEl.textContent = res.count;
    toast(res.liked ? '❤️ Liked!' : 'Like removed', 'success', 1500);
  }
  btn.disabled = false;
});

// ── Inline Comment Submit ────────────────────────────────────
document.addEventListener('submit', async function (e) {
  if (!e.target.classList.contains('comment-form')) return;
  e.preventDefault();
  const form     = e.target;
  const textarea = form.querySelector('textarea');
  const content  = textarea.value.trim();
  if (!content) return;

  const res = await ajax(BASE_URL + 'api/comment.php', {
    entity_type: form.dataset.type,
    entity_id:   form.dataset.id,
    content,
  });
  if (res.error) { toast(res.error, 'danger'); return; }

  const list = document.getElementById('comment-list-' + form.dataset.id);
  if (list) {
    const div = document.createElement('div');
    div.className = 'comment-item';
    div.innerHTML = `
      <div class="avatar">${res.initials}</div>
      <div class="comment-body">
        <span class="comment-author">${res.name}</span>
        <span class="comment-time">just now</span>
        <p class="comment-text">${escapeHtml(content)}</p>
      </div>`;
    list.prepend(div);
    textarea.value = '';
    toast('Comment posted!', 'success', 1500);
  }
});

// ── Forum AJAX Reply ─────────────────────────────────────────
document.addEventListener('submit', async function (e) {
  if (!e.target.classList.contains('reply-form')) return;
  e.preventDefault();
  const form    = e.target;
  const content = form.querySelector('textarea').value.trim();
  if (!content) return;

  const res = await ajax(BASE_URL + 'api/reply.php', {
    thread_id: form.dataset.thread,
    content,
  });
  if (res.error) { toast(res.error, 'danger'); return; }

  const list = document.getElementById('replies-list');
  if (list) {
    const div = document.createElement('div');
    div.className = 'comment-item';
    div.innerHTML = `
      <div class="avatar">${res.initials}</div>
      <div class="comment-body">
        <span class="comment-author">${res.name}</span>
        <span class="comment-time">just now</span>
        <p class="comment-text">${escapeHtml(content)}</p>
      </div>`;
    list.appendChild(div);
    form.querySelector('textarea').value = '';
    toast('Reply posted!', 'success', 1500);
    div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
});

// ── Notifications panel toggle ───────────────────────────────
const notifBtn = document.getElementById('notif-btn');
if (notifBtn) {
  const initialBadge = notifBtn.querySelector('.notif-badge');
  currentUnreadCount = parseBadgeCount(initialBadge?.textContent || '0');
  pollNotifications();
  setInterval(pollNotifications, 10000);

  notifBtn.addEventListener('click', async () => {
    let panel = document.getElementById(notificationPanelId);
    if (panel) { panel.remove(); notificationPanelOpen = false; return; }

    notificationPanelOpen = true;
    panel = document.createElement('div');
    panel.id = notificationPanelId;
    Object.assign(panel.style, {
      position: 'fixed', top: '70px', right: '20px',
      width: '320px', maxHeight: '400px', overflowY: 'auto',
      background: 'var(--bg-card)', border: '1px solid var(--border)',
      borderRadius: 'var(--radius-lg)', padding: '16px',
      zIndex: 999, boxShadow: 'var(--shadow)',
    });
    panel.innerHTML = '<div class="spinner" style="margin:20px auto;display:block"></div>';
    document.body.appendChild(panel);

    const res = await ajax(BASE_URL + 'api/notifications.php', {}, 'GET');
    if (res.error) { panel.innerHTML = `<p class="text-muted">${res.error}</p>`; return; }

    renderNotificationPanel(res, panel);
    ajax(BASE_URL + 'api/notifications.php', { mark_read: 1 });
    setBadgeCount(0);

    document.addEventListener('click', function outside(ev) {
      if (!panel.contains(ev.target) && ev.target !== notifBtn) {
        panel.remove();
        notificationPanelOpen = false;
        document.removeEventListener('click', outside);
      }
    });
  });
}

// ── Search autocomplete (basic) ──────────────────────────────
let searchTimer;
const searchInput = document.getElementById('global-search');
if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
      const q = searchInput.value.trim();
      if (q.length < 2) return;
      const res = await ajax(BASE_URL + 'api/search.php', { q }, 'GET');
      // Results handled per-page
    }, 350);
  });
}

// ── Mobile sidebar toggle ────────────────────────────────────
const menuToggle = document.getElementById('menu-toggle');
const sidebar    = document.querySelector('.sidebar');
if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}

// ── Confirm delete dialogs ───────────────────────────────────
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-confirm]');
  if (!btn) return;
  if (!confirm(btn.dataset.confirm)) e.preventDefault();
});

// ── Utility: escape HTML ────────────────────────────────────
function escapeHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Password strength meter ──────────────────────────────────
const passInput = document.getElementById('password');
const strengthBar = document.getElementById('pass-strength');
if (passInput && strengthBar) {
  passInput.addEventListener('input', () => {
    const val = passInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const colours = ['#ef4444','#f59e0b','#10b981','#4fc3f7'];
    const labels  = ['Weak','Fair','Good','Strong'];
    strengthBar.style.width = (score * 25) + '%';
    strengthBar.style.background = colours[score - 1] || '#ef4444';
    const label = document.getElementById('pass-strength-label');
    if (label) label.textContent = labels[score - 1] || '';
  });
}

// ── File upload preview ──────────────────────────────────────
const fileInput = document.getElementById('media-upload');
const preview   = document.getElementById('media-preview');
if (fileInput && preview) {
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    if (file.type.startsWith('image/')) {
      preview.innerHTML = `<img src="${url}" style="max-width:100%;max-height:200px;border-radius:8px;margin-top:8px">`;
    } else {
      preview.innerHTML = `<p class="text-muted mt-8">📎 ${escapeHtml(file.name)}</p>`;
    }
  });
}
