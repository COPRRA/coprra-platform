const COMPARE_ENDPOINT = '/api/compare';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const ensureToastStyles = () => {
  if (document.querySelector('#coprra-toast-styles')) {
    return;
  }

  const style = document.createElement('style');
  style.id = 'coprra-toast-styles';
  style.textContent = `
        .coprra-toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            z-index: 2147483647;
            pointer-events: none;
        }

        .coprra-toast {
            min-width: 18rem;
            max-width: 22rem;
            background-color: rgba(17, 24, 39, 0.95);
            color: #fff;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.25);
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: auto;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .coprra-toast--visible {
            opacity: 1;
            transform: translateY(0);
        }

        .coprra-toast__title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: block;
        }

        .coprra-toast--success { background-color: rgba(22, 163, 74, 0.95); }
        .coprra-toast--warning { background-color: rgba(217, 119, 6, 0.95); }
        .coprra-toast--error { background-color: rgba(220, 38, 38, 0.95); }
    `;

  document.head.append(style);
};

const getToastContainer = () => {
  let container = document.querySelector('.coprra-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'coprra-toast-container';
    document.body.append(container);
  }

  return container;
};

const notify = (message, type = 'info', title = '') => {
  if (typeof document === 'undefined') {
    return;
  }

  ensureToastStyles();
  const container = getToastContainer();
  const toast = document.createElement('div');
  toast.className = `coprra-toast coprra-toast--${type}`;
  toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

  if (title) {
    const heading = document.createElement('span');
    heading.className = 'coprra-toast__title';
    heading.textContent = title;
    toast.append(heading);
  }

  const body = document.createElement('span');
  body.textContent = message;
  toast.append(body);

  container.append(toast);

  requestAnimationFrame(() => {
    toast.classList.add('coprra-toast--visible');
  });

  const lifetime = Math.min(6000, Math.max(2500, message.length * 70));
  setTimeout(() => {
    toast.classList.remove('coprra-toast--visible');
  }, lifetime);
  setTimeout(() => {
    toast.remove();
  }, lifetime + 400);
};

const parseClassList = (value = '') =>
  value
    .split(/\s+/)
    .map(token => token.trim())
    .filter(Boolean);

const updateCompareBadge = count => {
  for (const badge of document.querySelectorAll('[data-compare-count]')) {
    badge.textContent = String(count);

    if (count > 0) {
      badge.classList.remove('hidden', 'opacity-0');
    } else {
      badge.classList.add('hidden', 'opacity-0');
    }
  }
};

const applyButtonState = (button, isAdded) => {
  if (!button) {
    return;
  }

  const defaultClasses = parseClassList(button.dataset.compareClassDefault);
  const activeClasses = parseClassList(button.dataset.compareClassAdded);
  const labelDefault = button.dataset.compareLabelDefault ?? 'Add to Compare';
  const labelAdded = button.dataset.compareLabelAdded ?? 'Added to Compare';

  if (defaultClasses.length > 0) {
    button.classList.remove(...defaultClasses);
  }

  if (activeClasses.length > 0) {
    button.classList.remove(...activeClasses);
  }

  const classSet = isAdded ? activeClasses : defaultClasses;
  if (classSet.length > 0) {
    button.classList.add(...classSet);
  }

  const labelSpan = button.querySelector('[data-compare-label]');
  if (labelSpan) {
    labelSpan.textContent = isAdded ? labelAdded : labelDefault;
  } else {
    button.textContent = isAdded ? labelAdded : labelDefault;
  }

  button.dataset.compareAdded = isAdded ? 'true' : 'false';
  button.setAttribute('aria-pressed', isAdded ? 'true' : 'false');
};

const escapeSelector = value => {
  if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }

  return String(value).replaceAll(/([ #.;?+*~':"!^$[\]()=>|/@])/g, String.raw`\$1`);
};

const syncButtonState = ids => {
  for (const button of document.querySelectorAll('[data-compare-add]')) {
    const productId = button.dataset.compareAdd;
    const isAdded = productId && ids.includes(Number.parseInt(productId, 10));
    applyButtonState(button, Boolean(isAdded));
  }
};

const handleAddRemove = async button => {
  if (!button || button.disabled) {
    return;
  }

  const productId = button.dataset.compareAdd;

  if (!productId) {
    return;
  }

  const isCurrentlyAdded = button.dataset.compareAdded === 'true';
  const endpoint = `${COMPARE_ENDPOINT}/${productId}`;
  const method = isCurrentlyAdded ? 'DELETE' : 'POST';

  button.disabled = true;

  try {
    const response = await fetch(endpoint, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      const message = payload?.message ?? 'Unable to update compare list.';
      const severity = response.status === 409 ? 'warning' : 'error';
      const title = severity === 'warning' ? 'Limit reached' : 'Action failed';
      notify(message, severity, title);

      return;
    }

    const items = Array.isArray(payload.items) ? payload.items : [];
    syncButtonState(items);
    updateCompareBadge(Number(payload.count ?? items.length));

    if (globalThis.location.pathname === '/compare') {
      globalThis.location.reload();
    }
  } catch {
    notify('We could not update the comparison list. Please try again.', 'error');
  } finally {
    button.disabled = false;
  }
};

const handleRemove = async button => {
  const productId = button.dataset.compareRemove;

  if (!productId) {
    return;
  }

  button.disabled = true;

  try {
    const response = await fetch(`${COMPARE_ENDPOINT}/${productId}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      return;
    }

    const items = Array.isArray(payload.items) ? payload.items : [];
    syncButtonState(items);
    updateCompareBadge(Number(payload.count ?? items.length));
    globalThis.location.reload();
  } catch {
    notify('We could not remove that product. Please try again.', 'error');
  }
};

const handleClear = async button => {
  button.disabled = true;

  try {
    const response = await fetch(`${COMPARE_ENDPOINT}/clear`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      return;
    }

    syncButtonState([]);
    updateCompareBadge(0);
    globalThis.location.reload();
  } catch {
    notify('Unable to clear comparison list right now.', 'error');
  } finally {
    button.disabled = false;
  }
};

const initializeAttributeFilters = () => {
  for (const checkbox of document.querySelectorAll('[data-compare-attribute-toggle]')) {
    checkbox.addEventListener('change', () => {
      const attribute = checkbox.dataset.compareAttributeToggle;
      if (!attribute) {
        return;
      }

      for (const row of document.querySelectorAll(
        `[data-compare-attribute-row="${escapeSelector(attribute)}"]`
      )) {
        row.classList.toggle('hidden', !checkbox.checked);
      }
    });
  }
};

const bootstrapCompare = () => {
  for (const button of document.querySelectorAll('[data-compare-add]')) {
    button.addEventListener('click', event => {
      event.preventDefault();
      handleAddRemove(button);
    });
  }

  for (const button of document.querySelectorAll('[data-compare-remove]')) {
    button.addEventListener('click', event => {
      event.preventDefault();
      handleRemove(button);
    });
  }

  const clearButton = document.querySelector('[data-compare-clear="true"]');
  let clearConfirmTimer;

  if (clearButton) {
    clearButton.addEventListener('click', event => {
      event.preventDefault();

      if (clearButton.dataset.confirmPending === 'true') {
        clearButton.dataset.confirmPending = 'false';
        clearTimeout(clearConfirmTimer);
        handleClear(clearButton);

        return;
      }

      clearButton.dataset.confirmPending = 'true';
      notify('اضغط مرة أخرى خلال 5 ثوانٍ لتأكيد مسح المقارنة.', 'warning', 'Confirm action');

      clearConfirmTimer = setTimeout(() => {
        clearButton.dataset.confirmPending = 'false';
      }, 5000);
    });
  }

  initializeAttributeFilters();

  fetch(COMPARE_ENDPOINT, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  })
    .then(response => (response.ok ? response.json() : undefined))
    .then(payload => {
      if (!payload) {
        return;
      }

      const items = Array.isArray(payload.items) ? payload.items : [];
      syncButtonState(items);
      updateCompareBadge(Number(payload.count ?? items.length));
    })
    .catch(() => {
      notify('Unable to synchronise comparison data.', 'error');
    });
};

document.addEventListener('DOMContentLoaded', bootstrapCompare);
