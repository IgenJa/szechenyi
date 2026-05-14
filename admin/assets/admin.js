/* ============================================
   SZÉCHENYI ADMIN — JAVASCRIPT
   - delete confirm
   - upload dropzone + previews
   - drag & drop reorder (categories + images)
   ============================================ */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    initConfirms();
    initDropzone();
    initSortable(document.querySelector('[data-sortable-cats]'),  'reorder');
    initSortable(document.querySelector('[data-sortable-images]'), 'reorder_images');
  });

  /* ---------- Delete / destructive confirmation ---------- */
  function initConfirms() {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
      form.addEventListener('submit', (e) => {
        const msg = form.getAttribute('data-confirm') || 'Biztos?';
        if (!window.confirm(msg)) e.preventDefault();
      });
    });
  }

  /* ---------- Upload dropzone + previews ---------- */
  function initDropzone() {
    const zone   = document.querySelector('.admin-dropzone');
    const input  = document.querySelector('[data-file-input]');
    const wrap   = document.querySelector('[data-previews]');
    const submit = document.querySelector('[data-upload-submit]');
    if (!zone || !input) return;

    const form   = zone.closest('form');
    const maxSize = parseInt(form?.getAttribute('data-max-size') || '0', 10);

    const updateUI = () => {
      const files = Array.from(input.files || []);
      wrap.innerHTML = '';
      if (files.length === 0) {
        wrap.hidden = true;
        submit.disabled = true;
        return;
      }
      wrap.hidden = false;
      submit.disabled = false;

      files.forEach(file => {
        const item = document.createElement('div');
        item.className = 'admin-preview';

        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.alt = '';
          img.src = URL.createObjectURL(file);
          img.onload = () => URL.revokeObjectURL(img.src);
          item.appendChild(img);
        }
        const name = document.createElement('span');
        name.className = 'admin-preview__name';
        name.textContent = file.name;
        item.appendChild(name);

        if (maxSize > 0 && file.size > maxSize) {
          name.textContent += ' — túl nagy!';
          item.style.outline = '2px solid #ba1a1a';
        }
        wrap.appendChild(item);
      });
    };

    input.addEventListener('change', updateUI);

    ['dragenter', 'dragover'].forEach(ev => {
      zone.addEventListener(ev, e => {
        e.preventDefault();
        zone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach(ev => {
      zone.addEventListener(ev, e => {
        e.preventDefault();
        zone.classList.remove('is-dragover');
      });
    });
    zone.addEventListener('drop', e => {
      if (!e.dataTransfer || !e.dataTransfer.files) return;
      // Override the file input's files via DataTransfer
      const dt = new DataTransfer();
      Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
      input.files = dt.files;
      updateUI();
    });

    // Keyboard-accessible: enter/space toggles file picker
    zone.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        input.click();
      }
    });
  }

  /* ---------- Drag-drop sortable ---------- */
  function initSortable(container, action) {
    if (!container) return;
    const csrf = container.getAttribute('data-csrf') || '';
    const items = () => Array.from(container.children).filter(el =>
      el.matches('[data-id]')
    );

    let dragged = null;

    items().forEach(item => {
      // A teljes kártya draggable, de drag handle-tól is működik
      const handles = item.querySelectorAll('.admin-handle');

      handles.forEach(h => {
        h.addEventListener('mousedown',  () => { item.draggable = true; });
        h.addEventListener('mouseup',    () => { item.draggable = false; });
        h.addEventListener('touchstart', () => { item.draggable = true; }, { passive: true });
        h.addEventListener('touchend',   () => { item.draggable = false; });
      });

      item.addEventListener('dragstart', (e) => {
        dragged = item;
        item.classList.add('is-dragging');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', item.dataset.id || '');
        }
      });
      item.addEventListener('dragend', () => {
        item.classList.remove('is-dragging');
        items().forEach(el => el.classList.remove('is-drop-target'));
        item.draggable = false;
      });
      item.addEventListener('dragover', (e) => {
        if (!dragged || dragged === item) return;
        e.preventDefault();
        item.classList.add('is-drop-target');
      });
      item.addEventListener('dragleave', () => {
        item.classList.remove('is-drop-target');
      });
      item.addEventListener('drop', (e) => {
        if (!dragged || dragged === item) return;
        e.preventDefault();
        const all = items();
        const fromIdx = all.indexOf(dragged);
        const toIdx   = all.indexOf(item);
        if (fromIdx < toIdx) item.after(dragged);
        else                 item.before(dragged);
        item.classList.remove('is-drop-target');
        persistOrder();
      });
    });

    function persistOrder() {
      const ids = items().map(el => el.dataset.id);
      const fd = new FormData();
      fd.append('csrf', csrf);
      fd.append('action', action);
      fd.append('order', JSON.stringify(ids));

      // Az URL ugyanaz, mint az aktuális oldal (PHP a POST-ot ott dolgozza fel)
      fetch(window.location.href, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      }).then(r => {
        if (r.redirected) window.location.href = r.url;
      }).catch(() => {
        // hiba esetén nem hagyjuk csendben — frissítsünk, hogy a user lássa az aktuális állapotot
        window.location.reload();
      });
    }
  }
})();
