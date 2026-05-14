export function initGalleryFilter() {
  const filterLinks = Array.from(document.querySelectorAll('[data-gallery-filter]'));
  const items = Array.from(document.querySelectorAll('[data-gallery-item]'));
  if (filterLinks.length === 0 || items.length === 0) return;

  const apply = (filter) => {
    items.forEach((item) => {
      const cat = item.getAttribute('data-category') || '';
      const visible = filter === 'all' || filter === '' || cat === filter;
      if (visible) item.removeAttribute('hidden');
      else item.setAttribute('hidden', '');
    });
    filterLinks.forEach((link) => {
      const target = link.getAttribute('data-gallery-filter');
      if (target === filter) {
        link.setAttribute('aria-current', 'true');
        link.classList.add('is-active');
      } else {
        link.removeAttribute('aria-current');
        link.classList.remove('is-active');
      }
    });
  };

  filterLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const filter = link.getAttribute('data-gallery-filter') || 'all';
      apply(filter);
      const newHash = filter === 'all' ? '' : `#${filter}`;
      try {
        history.replaceState(null, '', newHash || window.location.pathname);
      } catch (_) { /* noop */ }
    });
  });

  // Apply initial filter from hash
  const initialHash = (window.location.hash || '').replace('#', '');
  const validFilters = filterLinks.map((l) => l.getAttribute('data-gallery-filter'));
  const initial = validFilters.includes(initialHash) ? initialHash : 'all';
  apply(initial);

  // React to hash changes
  window.addEventListener('hashchange', () => {
    const hash = (window.location.hash || '').replace('#', '');
    apply(validFilters.includes(hash) ? hash : 'all');
  });
}
