/**
 * One Optics Clinic - UI enhancements (public pages)
 * Progressive enhancement only: every page still works if this file fails.
 * Kept deliberately small: no scroll or pointer-move handlers.
 */
document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('header');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Favicon (inline SVG so no extra asset is needed) ----
  if (!document.querySelector('link[rel~="icon"]')) {
    const icon = document.createElement('link');
    icon.rel = 'icon';
    icon.type = 'image/svg+xml';
    icon.href = "data:image/svg+xml," + encodeURIComponent(
      "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='#a8121f'/>" +
      "<g fill='none' stroke='#fff' stroke-width='5' stroke-linecap='round'><circle cx='20' cy='36' r='10'/><circle cx='44' cy='36' r='10'/><path d='M30 34q2-3 4 0'/></g>" +
      "</svg>");
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

  // ---- Nav: aria-current + mobile hamburger ----
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

  // ---- Reveal section headings on scroll (only hides what is below the fold) ----
  const targets = Array.from(document.querySelectorAll('.section-title, .section-head, .step-card, [data-reveal]'));
  if (!reduceMotion && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.1 });

    targets.forEach((el) => {
      if (el.getBoundingClientRect().top < window.innerHeight * 0.95) { el.classList.add('is-in'); return; }
      if (el.classList.contains('step-card')) el.style.setProperty('--d', (Array.from(el.parentElement.children).indexOf(el) * 0.14).toFixed(2) + 's');
      el.classList.add('will-reveal');
      io.observe(el);
    });
  } else {
    targets.forEach((el) => el.classList.add('is-in'));
  }
});
