/**
 * One Optics Clinic - shared UI primitives (public + admin pages)
 *  - OpticsUI.toast(message, type)      replaces alert()
 *  - OpticsUI.openLayer / closeLayer    accessible modal + drawer behaviour
 *  - OpticsUI.confirm(options)          replaces confirm()
 *  - OpticsUI.esc(value)                HTML-escape for template strings
 *  - <footer id="site-footer"> is filled in here so every page shares one footer
 */
(() => {
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const ICONS = {
    success: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.01"/>',
  };

  // ---------- Toasts ----------
  let stack;
  function toast(message, type = 'info', ms = 3600) {
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      stack.setAttribute('role', 'status');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span class="toast-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[type] || ICONS.info}</svg></span><span class="toast-msg">${esc(message)}</span>`;
    stack.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-in'));
    const dismiss = () => {
      el.classList.remove('is-in');
      setTimeout(() => el.remove(), 350);
    };
    el.addEventListener('click', dismiss);
    setTimeout(dismiss, ms);
    return el;
  }

  // ---------- Layers (modal / drawer) ----------
  const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const open = [];

  function openLayer(layer, opts = {}) {
    if (!layer || layer.classList.contains('is-open')) return;
    layer._returnFocus = document.activeElement;
    layer._onClose = opts.onClose;
    layer.hidden = false;
    layer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
    open.push(layer);
    requestAnimationFrame(() => {
      layer.classList.add('is-open');
      const target = layer.querySelector(opts.focus || '[data-autofocus]') || layer.querySelector(FOCUSABLE);
      if (target) target.focus({ preventScroll: true });
    });
  }

  function closeLayer(layer) {
    if (!layer || !layer.classList.contains('is-open')) return;
    layer.classList.remove('is-open');
    layer.setAttribute('aria-hidden', 'true');
    const i = open.indexOf(layer);
    if (i > -1) open.splice(i, 1);
    if (!open.length) document.body.classList.remove('no-scroll');
    setTimeout(() => { if (!layer.classList.contains('is-open')) layer.hidden = true; }, 350);
    if (layer._returnFocus && layer._returnFocus.focus) layer._returnFocus.focus({ preventScroll: true });
    if (typeof layer._onClose === 'function') layer._onClose();
  }

  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-close-layer]');
    if (closer) closeLayer(closer.closest('.ui-layer'));
  });
  document.addEventListener('keydown', (e) => {
    const top = open[open.length - 1];
    if (!top) return;
    if (e.key === 'Escape') { e.preventDefault(); closeLayer(top); return; }
    if (e.key !== 'Tab') return;
    const items = Array.from(top.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null);
    if (!items.length) return;
    const first = items[0], last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  // ---------- Confirm dialog (promise based) ----------
  function confirmDialog({ title = 'Are you sure?', message = '', confirmText = 'Confirm', cancelText = 'Cancel', danger = false } = {}) {
    return new Promise((resolve) => {
      const layer = document.createElement('div');
      layer.className = 'ui-layer';
      layer.hidden = true;
      layer.setAttribute('role', 'alertdialog');
      layer.setAttribute('aria-modal', 'true');
      layer.innerHTML = `
        <div class="ui-backdrop" data-close-layer></div>
        <div class="ui-dialog ui-dialog-sm">
          <h3 class="ui-dialog-title">${esc(title)}</h3>
          <p class="ui-dialog-text">${esc(message)}</p>
          <div class="ui-dialog-actions">
            <button type="button" class="btn btn-outline" data-close-layer>${esc(cancelText)}</button>
            <button type="button" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-autofocus data-yes>${esc(confirmText)}</button>
          </div>
        </div>`;
      document.body.appendChild(layer);
      let answered = false;
      layer.querySelector('[data-yes]').addEventListener('click', () => { answered = true; resolve(true); closeLayer(layer); });
      openLayer(layer, { onClose: () => { if (!answered) resolve(false); setTimeout(() => layer.remove(), 400); } });
    });
  }

  // ---------- Shared footer ----------
  function renderFooter() {
    const f = document.getElementById('site-footer');
    if (!f) return;
    f.innerHTML = `
      <div class="container">
        <div class="footer-content">
          <div class="footer-col footer-brand">
            <h3>ONE OPTICS Clinic</h3>
            <p class="footer-tag">Clear Vision &bull; Brighter Tomorrow</p>
            <p>Professional eye care and premium eyewear in the heart of Malate, Manila.</p>
            <a href="appointment.html" class="btn btn-light btn-sm">Book an appointment <span class="arr" aria-hidden="true">&rarr;</span></a>
          </div>
          <div class="footer-col">
            <h3>Explore</h3>
            <a href="index.html">Home</a>
            <a href="catalog.html">Eyewear catalog</a>
            <a href="appointment.html">Book appointment</a>
            <a href="about.html">About us</a>
            <a href="contact.html">Contact</a>
          </div>
          <div class="footer-col">
            <h3>Visit us</h3>
            <p>2598 Singalong Street<br>Vito Cruz, Malate, Manila</p>
            <a href="tel:+639561277149">0956 127 7149</a>
            <a href="mailto:one_optics@yahoo.com">one_optics@yahoo.com</a>
            <p class="footer-hours">Opens at 10 AM. Call us to confirm closing hours.</p>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; ${new Date().getFullYear()} One Optics Clinic. All rights reserved. Developed by Luis Agastine Lozada, BSIT.</span>
          <a href="admin-login.html" class="footer-staff">Staff login</a>
        </div>
      </div>`;
  }
  renderFooter();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderFooter);

  // ---------- Shared header: one template, so every page has the same navbar ----------
  function renderHeader() {
    const h = document.getElementById('site-header');
    if (!h || h.childElementCount) return;
    const pages = [['index.html', 'Home'], ['catalog.html', 'Eyewear'], ['appointment.html', 'Appointments'], ['about.html', 'About Us'], ['contact.html', 'Contact'], ['admin-login.html', 'Admin']];
    const here = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
    h.innerHTML = `
      <div class="container navbar">
        <div class="logo">
          <a href="index.html">ONE OPTICS Clinic</a>
          <span class="tagline">Clear Vision &bull; Brighter Tomorrow</span>
        </div>
        <nav aria-label="Main">
          <ul class="nav-links">
            ${pages.map(([href, label]) => `<li><a href="${href}"${here === href ? ' class="active"' : ''}>${label}</a></li>`).join('')}
            <li><button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode"></button></li>
          </ul>
        </nav>
      </div>`;
  }
  renderHeader();

  // ---------- Image fallback: a failed product/doctor image shows a branded placeholder ----------
  const svg = (label) => 'data:image/svg+xml,' + encodeURIComponent(
    "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'>" +
    "<g fill='none' stroke='#a8121f' stroke-width='9' stroke-linecap='round' opacity='.85'>" +
    "<circle cx='140' cy='150' r='46'/><circle cx='260' cy='150' r='46'/><path d='M186 146q14-16 28 0M94 140l-20-48M306 140l20-48'/></g>" +
    "<text x='200' y='246' font-family='sans-serif' font-size='18' font-weight='600' fill='#62585a' text-anchor='middle'>" +
    label.replace(/[<>&]/g, '') + "</text></svg>");
  const failed = new Set();
  window.OpticsImg = { failed, placeholder: svg, pick: (url, label) => (failed.has(url) ? svg(label) : url) };
  const swap = (img) => {
    if (!img.getAttribute('src') || img.dataset.fallback) return;
    failed.add(img.getAttribute('src'));
    img.dataset.fallback = '1';
    img.src = svg(img.alt || 'One Optics');
  };
  document.addEventListener('error', (e) => {
    const t = e.target;
    if (t && t.tagName === 'IMG' && t.matches('.product-img, .doctor-img, .adm-img, img[class*="product"], img[class*="cart"]')) swap(t);
  }, true);

  window.OpticsUI = { toast, openLayer, closeLayer, confirm: confirmDialog, esc };
})();
