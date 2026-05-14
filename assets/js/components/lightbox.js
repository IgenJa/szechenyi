const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function initLightbox() {
  const triggers = Array.from(document.querySelectorAll('[data-lightbox-trigger]'));
  if (triggers.length === 0) return;

  // Build slide list from triggers
  const slides = triggers.map((trigger, index) => {
    const img = trigger.querySelector('img');
    const fullSrc = trigger.getAttribute('data-lightbox-src') || img?.src || '';
    const caption = trigger.getAttribute('data-lightbox-caption') || img?.alt || '';
    const category = trigger.getAttribute('data-category') || '';
    trigger.setAttribute('data-lightbox-index', String(index));
    return { src: fullSrc, caption, category, alt: img?.alt || caption || `Kép ${index + 1}` };
  });

  // Create lightbox DOM
  const root = document.createElement('div');
  root.className = 'lightbox';
  root.setAttribute('aria-hidden', 'true');
  root.setAttribute('role', 'dialog');
  root.setAttribute('aria-modal', 'true');
  root.setAttribute('aria-label', 'Képnézegető');
  root.innerHTML = `
    <header class="lightbox__header">
      <span class="lightbox__wordmark">Széchenyi Márk</span>
      <button type="button" class="lightbox__close" data-lightbox-close aria-label="Bezárás">
        <svg class="lightbox__icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" fill="none"/>
        </svg>
      </button>
    </header>
    <div class="lightbox__body">
      <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Előző kép">
        <svg class="lightbox__icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.5" fill="none"/>
        </svg>
      </button>
      <figure class="lightbox__image-wrap">
        <img class="lightbox__image" alt="" />
        <figcaption class="lightbox__meta">
          <span data-lightbox-counter>1 / 1</span>
          <span aria-hidden="true">•</span>
          <span data-lightbox-caption></span>
        </figcaption>
      </figure>
      <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Következő kép">
        <svg class="lightbox__icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.5" fill="none"/>
        </svg>
      </button>
    </div>
    <div class="lightbox__thumbs" data-lightbox-thumbs role="tablist"></div>
  `;
  document.body.appendChild(root);

  const imgEl = root.querySelector('.lightbox__image');
  const counterEl = root.querySelector('[data-lightbox-counter]');
  const captionEl = root.querySelector('[data-lightbox-caption]');
  const thumbsEl = root.querySelector('[data-lightbox-thumbs]');
  const prevBtn = root.querySelector('[data-lightbox-prev]');
  const nextBtn = root.querySelector('[data-lightbox-next]');
  const closeBtn = root.querySelector('[data-lightbox-close]');

  // Build thumbnails
  slides.forEach((slide, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'lightbox__thumb';
    btn.setAttribute('role', 'tab');
    btn.setAttribute('aria-label', `${slide.caption || 'Kép'} ${i + 1}`);
    btn.setAttribute('data-thumb-index', String(i));
    btn.innerHTML = `<img src="${slide.src}" alt="" loading="lazy" />`;
    btn.addEventListener('click', () => goTo(i));
    thumbsEl.appendChild(btn);
  });
  const thumbBtns = Array.from(thumbsEl.querySelectorAll('.lightbox__thumb'));

  let currentIndex = 0;
  let lastFocused = null;

  const total = slides.length;

  const render = () => {
    const slide = slides[currentIndex];
    imgEl.src = slide.src;
    imgEl.alt = slide.alt;
    counterEl.textContent = `${String(currentIndex + 1).padStart(2, '0')} / ${String(total).padStart(2, '0')}`;
    captionEl.textContent = slide.caption || '';
    thumbBtns.forEach((btn, i) => {
      if (i === currentIndex) {
        btn.setAttribute('aria-current', 'true');
        btn.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
      } else {
        btn.removeAttribute('aria-current');
      }
    });
  };

  const goTo = (index) => {
    if (total === 0) return;
    currentIndex = (index + total) % total;
    render();
  };

  const open = (index) => {
    lastFocused = document.activeElement;
    currentIndex = index;
    render();
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('is-lightbox-open');
    closeBtn.focus();
  };

  const close = () => {
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('is-lightbox-open');
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  };

  // Wire triggers
  triggers.forEach((trigger, index) => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      open(index);
    });
  });

  prevBtn.addEventListener('click', () => goTo(currentIndex - 1));
  nextBtn.addEventListener('click', () => goTo(currentIndex + 1));
  closeBtn.addEventListener('click', close);

  // Backdrop click
  root.addEventListener('click', (e) => {
    if (e.target === root || e.target.classList.contains('lightbox__body')) {
      close();
    }
  });

  // Keyboard
  document.addEventListener('keydown', (e) => {
    if (root.getAttribute('aria-hidden') !== 'false') return;
    if (e.key === 'Escape') { e.preventDefault(); close(); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(currentIndex - 1); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); goTo(currentIndex + 1); }
    else if (e.key === 'Tab') {
      const focusable = Array.from(root.querySelectorAll(FOCUSABLE));
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

  // Swipe support for touch devices
  let touchStartX = 0;
  let touchStartY = 0;
  root.addEventListener('touchstart', (e) => {
    if (e.touches.length !== 1) return;
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
  }, { passive: true });
  root.addEventListener('touchend', (e) => {
    if (e.changedTouches.length !== 1) return;
    const dx = e.changedTouches[0].clientX - touchStartX;
    const dy = e.changedTouches[0].clientY - touchStartY;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      goTo(currentIndex + (dx < 0 ? 1 : -1));
    }
  }, { passive: true });
}
