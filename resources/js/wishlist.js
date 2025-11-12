const WISHLIST_ENDPOINT = '/api/wishlist';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const isAuthenticated = document.body.dataset.authenticated === 'true';
const loginUrl = document.body.dataset.loginUrl ?? '/login';
const registerUrl = document.body.dataset.registerUrl ?? '/register';

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

        .coprra-toast__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .coprra-toast__actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background-color: rgba(255, 255, 255, 0.18);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .coprra-toast__actions a:hover {
            background-color: rgba(255, 255, 255, 0.3);
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

const renderToastActions = (actions = []) => {
  if (actions.length === 0) {
    return;
  }

  const wrapper = document.createElement('div');
  wrapper.className = 'coprra-toast__actions';

  for (const { label, href } of actions) {
    const link = document.createElement('a');
    link.textContent = label;
    link.href = href;
    wrapper.append(link);
  }

  return wrapper;
};

const notify = (message, type = 'info', { title = '', actions = [] } = {}) => {
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

  const actionsNode = renderToastActions(actions);
  if (actionsNode) {
    toast.append(actionsNode);
  }

  container.append(toast);

  requestAnimationFrame(() => {
    toast.classList.add('coprra-toast--visible');
  });

  const lifetime = Math.min(6500, Math.max(3000, message.length * 70));
  setTimeout(() => {
    toast.classList.remove('coprra-toast--visible');
  }, lifetime);
  setTimeout(() => {
    toast.remove();
  }, lifetime + 400);
};

const parseClassList = (input = '') =>
  input
    .split(/\s+/)
    .map(token => token.trim())
    .filter(Boolean);

const showAuthPrompt = () => {
  notify('Please log in or create an account to save products to your wishlist.', 'warning', {
    title: 'تسجيل الدخول مطلوب',
    actions: [
      { label: 'تسجيل الدخول', href: loginUrl },
      { label: 'إنشاء حساب', href: registerUrl },
    ],
  });
};

const updateWishlistBadges = count => {
  for (const badge of document.querySelectorAll('[data-wishlist-count]')) {
    badge.textContent = String(count);
    badge.dataset.initialCount = String(count);

    if (count > 0) {
      badge.classList.remove('hidden', 'opacity-0');
    } else {
      badge.classList.add('hidden', 'opacity-0');
    }
  }
};

const applyWishlistState = (button, isWishlisted) => {
  if (!button) {
    return;
  }

  const defaultClasses = parseClassList(button.dataset.wishlistClassDefault);
  const activeClasses = parseClassList(button.dataset.wishlistClassActive);
  const iconDefault = button.dataset.wishlistIconDefault ?? 'fas fa-heart';
  const iconActive = button.dataset.wishlistIconActive ?? 'fas fa-heart-broken';
  const labelDefault = button.dataset.wishlistLabelDefault ?? 'Add to Wishlist';
  const labelActive = button.dataset.wishlistLabelActive ?? 'Remove from Wishlist';

  if (defaultClasses.length > 0) {
    button.classList.remove(...defaultClasses);
  }

  if (activeClasses.length > 0) {
    button.classList.remove(...activeClasses);
  }

  const classSet = isWishlisted ? activeClasses : defaultClasses;
  if (classSet.length > 0) {
    button.classList.add(...classSet);
  }

  const icon = button.querySelector('.wishlist-icon');
  if (icon) {
    icon.className = `wishlist-icon ${isWishlisted ? iconActive : iconDefault}`;
  }

  const label = button.querySelector('.wishlist-label');
  if (label) {
    label.textContent = isWishlisted ? labelActive : labelDefault;
  }

  button.dataset.wishlisted = isWishlisted ? 'true' : 'false';
};

const escapeSelector = value => {
  if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }

  return String(value).replaceAll(/([ #.;?+*~':"!^$[\]()=>|/@])/g, String.raw`\$1`);
};

const updateButtonsForProduct = (productId, isWishlisted) => {
  for (const button of document.querySelectorAll(
    `.wishlist-toggle-btn[data-product-id="${escapeSelector(productId)}"]`
  ))
    applyWishlistState(button, isWishlisted);
};

const toggleWishlist = async button => {
  if (!button || button.disabled) {
    return;
  }

  if (!isAuthenticated) {
    showAuthPrompt();
    return;
  }

  const { productId } = button.dataset;

  if (!productId) {
    notify('Unable to determine product for wishlist action.', 'error');
    return;
  }

  const currentlyWishlisted = button.dataset.wishlisted === 'true';
  const method = currentlyWishlisted ? 'DELETE' : 'POST';
  const endpoint = `${WISHLIST_ENDPOINT}/${productId}`;

  button.disabled = true;

  try {
    const response = await fetch(endpoint, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-XSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });

    if (response.status === 401 || response.status === 419) {
      showAuthPrompt();
      return;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      notify(payload?.message ?? 'Unable to update wishlist right now.', 'error');
      return;
    }

    const updatedCount = Number(payload?.count ?? 0);
    updateWishlistBadges(Number.isNaN(updatedCount) ? 0 : updatedCount);

    const nextState = method !== 'DELETE';
    updateButtonsForProduct(productId, nextState);

    if (payload?.message) {
      notify(payload.message, 'success');
    }

    if (!nextState && globalThis.location.pathname === '/account/wishlist') {
      globalThis.location.reload();
    }
  } catch {
    notify('An unexpected error occurred while updating your wishlist.', 'error');
  } finally {
    button.disabled = false;
  }
};

const bootstrapWishlistButtons = () => {
  for (const button of document.querySelectorAll('.wishlist-toggle-btn[data-product-id]')) {
    button.addEventListener('click', event => {
      event.preventDefault();
      toggleWishlist(button);
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  bootstrapWishlistButtons();

  if (isAuthenticated) {
    fetch(WISHLIST_ENDPOINT, {
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

        const count = Number(
          payload.count ?? (Array.isArray(payload.data) ? payload.data.length : 0)
        );
        updateWishlistBadges(Number.isNaN(count) ? 0 : count);

        if (Array.isArray(payload.data)) {
          for (const item of payload.data) {
            if (item?.id) {
              updateButtonsForProduct(String(item.id), true);
            }
          }
        }
      })
      .catch(() => {
        notify('Unable to synchronise wishlist data.', 'error');
      });
  }
});
