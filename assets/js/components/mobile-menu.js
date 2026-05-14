const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function initMobileMenu() {
  const toggle = document.querySelector('[data-mobile-menu-toggle]');
  const menu = document.querySelector('[data-mobile-menu]');
  if (!toggle || !menu) return;

  let lastFocused = null;

  const open = () => {
    lastFocused = document.activeElement;
    menu.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('is-menu-open');
    const first = menu.querySelector(FOCUSABLE);
    first?.focus();
  };

  const close = () => {
    menu.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('is-menu-open');
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  };

  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    expanded ? close() : open();
  });

  document.addEventListener('keydown', (e) => {
    if (toggle.getAttribute('aria-expanded') !== 'true') return;
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      return;
    }
    if (e.key === 'Tab') {
      const focusable = Array.from(menu.querySelectorAll(FOCUSABLE)).filter((el) => !el.hasAttribute('disabled'));
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });

  // Close menu when a nav link is clicked (same-page anchor or navigation)
  menu.querySelectorAll('a[href]').forEach((link) => {
    link.addEventListener('click', () => {
      // Allow navigation; close visually
      close();
    });
  });

  // Close if viewport widens past mobile breakpoint
  const mql = window.matchMedia('(min-width: 768px)');
  const handleResize = (e) => {
    if (e.matches && toggle.getAttribute('aria-expanded') === 'true') close();
  };
  mql.addEventListener('change', handleResize);
}
