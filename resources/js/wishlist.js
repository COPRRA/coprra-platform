const WISHLIST_ENDPOINT = '/api/wishlist';

const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const isAuthenticated = document.body.dataset.authenticated === 'true';
const loginUrl = document.body.dataset.loginUrl ?? '/login';
const registerUrl = document.body.dataset.registerUrl ?? '/register';

const parseClassList = (input = '') =>
    input
        .split(/\s+/)
        .map((token) => token.trim())
        .filter(Boolean);

const notify = (message, type = 'info') => {
    // Replace alert with a more advanced toast/notification system when available.
    if (typeof window !== 'undefined') {
        // eslint-disable-next-line no-alert
        alert(`${type.toUpperCase()}: ${message}`);
    }
};

const showAuthPrompt = () => {
    const promptMessage = [
        'Please log in or create an account to save products to your wishlist.',
        `Login: ${loginUrl}`,
        `Register: ${registerUrl}`,
    ].join('\n');

    if (typeof window !== 'undefined') {
        // eslint-disable-next-line no-alert
        if (confirm(promptMessage)) {
            window.location.href = loginUrl;
        }
    }
};

const updateWishlistBadges = (count) => {
    document.querySelectorAll('[data-wishlist-count]').forEach((badge) => {
        badge.textContent = String(count);
        badge.dataset.initialCount = String(count);

        if (count > 0) {
            badge.classList.remove('hidden', 'opacity-0');
        } else {
            badge.classList.add('hidden', 'opacity-0');
        }
    });
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

    // Remove both default and active classes before applying the correct set
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

const escapeSelector = (value) => {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
    }

    return String(value).replace(/([ #.;?+*~':"!^$[\]()=>|/@])/g, '\\$1');
};

const updateButtonsForProduct = (productId, isWishlisted) => {
    document
        .querySelectorAll(`.wishlist-toggle-btn[data-product-id="${escapeSelector(productId)}"]`)
        .forEach((button) => applyWishlistState(button, isWishlisted));
};

const toggleWishlist = async (button) => {
    if (!button || button.disabled) {
        return;
    }

    if (!isAuthenticated) {
        showAuthPrompt();

        return;
    }

    const productId = button.dataset.productId;

    if (!productId) {
        notify('Unable to determine product for wishlist action.', 'error');

        return;
    }

    const currentlyWishlisted = button.dataset.wishlisted === 'true';
    const method = currentlyWishlisted ? 'DELETE' : 'POST';
    const endpoint =
        method === 'POST' ? `${WISHLIST_ENDPOINT}/${productId}` : `${WISHLIST_ENDPOINT}/${productId}`;

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

        const nextState = method === 'DELETE' ? false : true;
        updateButtonsForProduct(productId, nextState);

        if (payload?.message) {
            notify(payload.message, 'success');
        }
    } catch (error) {
        console.error('Wishlist API error:', error);
        notify('An unexpected error occurred while updating your wishlist.', 'error');
    } finally {
        button.disabled = false;
    }
};

const bootstrapWishlistButtons = () => {
    document.querySelectorAll('.wishlist-toggle-btn[data-product-id]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            toggleWishlist(button);
        });
    });
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
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                if (!payload) {
                    return;
                }

                const count = Number(payload.count ?? (Array.isArray(payload.data) ? payload.data.length : 0));
                updateWishlistBadges(Number.isNaN(count) ? 0 : count);
            })
            .catch((error) => {
                console.error('Failed to sync wishlist count:', error);
            });
    }
});
