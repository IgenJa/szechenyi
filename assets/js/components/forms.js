export function initForms() {
  const forms = Array.from(document.querySelectorAll('[data-form]'));
  forms.forEach(bindForm);
}

function bindForm(form) {
  const type = form.getAttribute('data-form'); // 'contact' | 'booking'
  const recipient = form.getAttribute('data-form-recipient') || 'info@szechenyifoto.hu';

  form.setAttribute('novalidate', '');

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const errors = validate(form);
    showErrors(form, errors);
    if (errors.length > 0) {
      const firstField = form.querySelector(`[name="${errors[0].name}"]`);
      firstField?.focus();
      return;
    }

    const data = collectData(form);
    const subject = type === 'booking'
      ? `Stúdió foglalás — ${data.name || ''}`
      : `Kapcsolatfelvétel — ${data.name || ''}`;

    const body = Object.entries(data)
      .filter(([, v]) => v && String(v).trim() !== '')
      .map(([k, v]) => `${labelFor(k)}: ${v}`)
      .join('\n');

    const mailto = `mailto:${encodeURIComponent(recipient)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

    showSuccess(form);
    window.location.href = mailto;
  });

  // Live-clear errors as the user types
  form.querySelectorAll('input, textarea, select').forEach((el) => {
    el.addEventListener('input', () => clearFieldError(el));
    el.addEventListener('change', () => clearFieldError(el));
  });
}

function validate(form) {
  const errors = [];
  form.querySelectorAll('input, textarea, select').forEach((el) => {
    const name = el.name;
    if (!name) return;
    const value = (el.value || '').trim();
    if (el.required && !value) {
      errors.push({ name, message: 'Ez a mező kötelező.' });
      return;
    }
    if (el.type === 'email' && value && !isEmail(value)) {
      errors.push({ name, message: 'Érvénytelen e-mail cím.' });
    }
    if (el.type === 'date' && el.required && value) {
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        errors.push({ name, message: 'Érvénytelen dátum.' });
      }
    }
  });
  return errors;
}

function isEmail(str) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
}

function showErrors(form, errors) {
  // Clear existing
  form.querySelectorAll('.form__field--error').forEach((f) => f.classList.remove('form__field--error'));
  form.querySelectorAll('.form__error').forEach((e) => { e.textContent = ''; });

  errors.forEach(({ name, message }) => {
    const field = form.querySelector(`[name="${name}"]`);
    if (!field) return;
    const wrap = field.closest('.form__field');
    if (!wrap) return;
    wrap.classList.add('form__field--error');
    let err = wrap.querySelector('.form__error');
    if (!err) {
      err = document.createElement('p');
      err.className = 'form__error';
      err.setAttribute('role', 'alert');
      wrap.appendChild(err);
    }
    err.textContent = message;
  });
}

function clearFieldError(el) {
  const wrap = el.closest('.form__field');
  if (!wrap || !wrap.classList.contains('form__field--error')) return;
  wrap.classList.remove('form__field--error');
  const err = wrap.querySelector('.form__error');
  if (err) err.textContent = '';
}

function collectData(form) {
  const data = {};
  new FormData(form).forEach((value, key) => {
    data[key] = value;
  });
  return data;
}

function labelFor(key) {
  const map = {
    name: 'Név',
    email: 'E-mail',
    message: 'Üzenet',
    date: 'Dátum',
    duration: 'Időtartam',
    project: 'Projekt leírása',
  };
  return map[key] || key;
}

function showSuccess(form) {
  let success = form.querySelector('.form__success');
  if (!success) {
    success = document.createElement('p');
    success.className = 'form__success';
    success.setAttribute('role', 'status');
    form.appendChild(success);
  }
  success.textContent = 'Köszönöm! Az üzenet az e-mail kliensedben megnyílt — kérlek küldd el a tartalmat.';
}
