/**
 * One Optics Clinic - UI enhancements (public pages)
 * Progressive enhancement only: every page still works if this file fails.
 */
document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const header = document.querySelector('header');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Favicon (inline SVG so no extra asset is needed) ----
  if (!document.querySelector('link[rel~="icon"]')) {
    const icon = document.createElement('link');
    icon.rel = 'icon';
    icon.type = 'image/svg+xml';
    icon.href = "data:image/svg+xml," + encodeURIComponent(
      "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='#c4162a'/>" +
      "<g fill='none' stroke='#fff' stroke-width='5' stroke-linecap='round'><circle cx='20' cy='36' r='10'/><circle cx='44' cy='36' r='10'/><path d='M30 34q2-3 4 0'/></g>" +
      "<circle cx='50' cy='14' r='4' fill='#e0a526'/></svg>");
    document.head.appendChild(icon);
  }

  // ---- Skip link + main landmark target ----
  const firstSection = document.querySelector('main, .hero, body > .container, body > section');
  if (firstSection && !document.querySelector('.skip-link')) {
    if (!firstSection.id) firstSection.id = 'main-content';
    const skip = document.createElement('a');
    skip.className = 'skip-link';
    skip.href = '#' + firstSection.id;
    skip.textContent = 'Skip to content';
    document.body.prepend(skip);
  }

  // ---- Scroll progress + header shrink ----
  const bar = document.createElement('div');
  bar.className = 'scroll-progress';
  bar.setAttribute('aria-hidden', 'true');
  document.body.appendChild(bar);

  let ticking = false;
  const onScroll = () => {
    const max = root.scrollHeight - window.innerHeight;
    bar.style.setProperty('--p', max > 0 ? Math.min(window.scrollY / max, 1).toFixed(4) : 0);
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
  }, { passive: true });
  onScroll();

  // ---- Nav: aria-current, hamburger ----
  const navLinks = document.querySelector('.nav-links');
  const navbar = document.querySelector('.navbar');
  if (navLinks) {
    const active = navLinks.querySelector('a.active');
    if (active) active.setAttribute('aria-current', 'page');
  }
  if (navbar && navLinks && header) {
    const toggle = document.createElement('button');
    toggle.className = 'nav-toggle';
    toggle.type = 'button';
    toggle.setAttribute('aria-label', 'Open menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span></span><span></span><span></span>';
    navbar.appendChild(toggle);

    const setOpen = (open) => {
      header.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };
    toggle.addEventListener('click', () => setOpen(!header.classList.contains('nav-open')));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    navLinks.addEventListener('click', (e) => { if (e.target.closest('a')) setOpen(false); });
    window.matchMedia('(min-width: 901px)').addEventListener('change', () => setOpen(false));
  }

  // ---- Reveal on scroll (only hides what's below the fold, so no flash) ----
  const autoTargets = '.section-title, .section-head, .bento-card, .step-card, .contact-card, .footer-col, .form-panel, .info-panel, [data-reveal]';
  const targets = Array.from(document.querySelectorAll(autoTargets));
  if (!reduceMotion && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    targets.forEach((el, i) => {
      if (el.getBoundingClientRect().top < window.innerHeight * 0.95) return;
      // stagger siblings that share a parent
      const sibs = Array.from(el.parentElement.children).filter((c) => targets.includes(c));
      el.style.setProperty('--d', (Math.max(sibs.indexOf(el), 0) * 0.08).toFixed(2) + 's');
      el.classList.add('will-reveal');
      io.observe(el);
    });
  } else {
    targets.forEach((el) => el.classList.add('is-in'));
  }

  // ---- Cursor spotlight on cards (delegated, works for injected cards) ----
  document.addEventListener('pointermove', (e) => {
    const card = e.target.closest && e.target.closest('.product-card, .doctor-card, .bento-card');
    if (!card) return;
    const r = card.getBoundingClientRect();
    card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    card.style.setProperty('--my', (e.clientY - r.top) + 'px');
  }, { passive: true });

  // ---- Hero parallax ----
  const hero = document.querySelector('.hero');
  const visual = document.querySelector('.hero-visual');
  if (hero && visual && !reduceMotion && window.matchMedia('(hover: hover)').matches) {
    hero.addEventListener('pointermove', (e) => {
      const r = hero.getBoundingClientRect();
      visual.style.setProperty('--px', (((e.clientX - r.left) / r.width) * 2 - 1).toFixed(3));
      visual.style.setProperty('--py', (((e.clientY - r.top) / r.height) * 2 - 1).toFixed(3));
    }, { passive: true });
    hero.addEventListener('pointerleave', () => {
      visual.style.setProperty('--px', 0);
      visual.style.setProperty('--py', 0);
    });
  }
});

// ---- Image fallback: a failed product/doctor image shows a branded placeholder ----
(() => {
  const svg = (label) => 'data:image/svg+xml,' + encodeURIComponent(
    "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'>" +
    "<rect width='400' height='300' fill='#f6f2ee'/>" +
    "<g fill='none' stroke='#c4162a' stroke-width='9' stroke-linecap='round' opacity='.85'>" +
    "<circle cx='140' cy='150' r='46'/><circle cx='260' cy='150' r='46'/><path d='M186 146q14-16 28 0M94 140l-20-48M306 140l20-48'/></g>" +
    "<text x='200' y='246' font-family='sans-serif' font-size='18' font-weight='600' fill='#62585a' text-anchor='middle'>" +
    label.replace(/[<>&]/g, '') + "</text></svg>");
  const swap = (img) => {
    if (img.dataset.fallback) return;
    img.dataset.fallback = '1';
    img.src = svg(img.alt || 'One Optics');
  };
  document.addEventListener('error', (e) => {
    const t = e.target;
    if (t && t.tagName === 'IMG' && t.matches('.product-img, .doctor-img, img[class*="product"], img[class*="cart"]')) swap(t);
  }, true);
})();
