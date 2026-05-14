export function initActiveNav() {
  const current = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
  document.querySelectorAll('[data-nav-link]').forEach((link) => {
    const href = (link.getAttribute('href') || '').toLowerCase();
    if (href === current || (current === '' && href === 'index.html')) {
      link.setAttribute('aria-current', 'page');
    } else {
      link.removeAttribute('aria-current');
    }
  });
}
