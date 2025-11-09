const CompareManager = (() => {
    const API_ENDPOINT = '/api/compare';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const requestHeaders = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken !== '') {
        requestHeaders['X-CSRF-TOKEN'] = csrfToken;
    }

    let currentCount = 0;

    const parseClassList = (value) => value?.split(/\s+/).filter(Boolean) ?? [];

    const applyStateClasses = (button, isAdded) => {
        const defaultClasses = parseClassList(button.dataset.compareClassDefault);
        const addedClasses = parseClassList(button.dataset.compareClassAdded);

        if (defaultClasses.length > 0) {
            if (isAdded) {
                button.classList.remove(...defaultClasses);
            } else {
                button.classList.add(...defaultClasses);
            }
        }

        if (addedClasses.length > 0) {
            if (isAdded) {
                button.classList.add(...addedClasses);
            } else {
                button.classList.remove(...addedClasses);
            }
        }
    };

    /**
     * @param {HTMLElement} button
     */
    const markButtonAsAdded = (button) => {
        if (!button) {
            return;
        }

        button.dataset.compareAdded = 'true';
        const addedLabel = button.dataset.compareLabelAdded ?? 'Added to Compare';
        button.textContent = addedLabel;
        applyStateClasses(button, true);
        button.setAttribute('aria-pressed', 'true');
    };

    /**
     * @param {HTMLElement} button
     */
    const resetButtonState = (button) => {
        if (!button) {
            return;
        }

        const defaultLabel = button.dataset.compareLabelDefault ?? 'Compare';
        button.textContent = defaultLabel;
        applyStateClasses(button, false);
        button.setAttribute('aria-pressed', 'false');
        button.dataset.compareAdded = 'false';
    };

    /**
     * @param {string} message
     * @param {'success'|'error'|'info'} variant
     */
    const notify = (message, variant = 'info') => {
        if (!message) {
            return;
        }

        const containerId = 'compare-toast-container';
        let container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.position = 'fixed';
            container.style.top = '1.5rem';
            container.style.right = '1.5rem';
            container.style.zIndex = '9999';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '0.75rem';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.padding = '0.75rem 1.25rem';
        toast.style.borderRadius = '0.75rem';
        toast.style.boxShadow = '0 10px 30px rgba(15, 23, 42, 0.15)';
        toast.style.fontSize = '0.95rem';
        toast.style.fontWeight = '600';
        toast.style.color = '#fff';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';

        switch (variant) {
            case 'success':
                toast.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                break;
            case 'error':
                toast.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                break;
            default:
                toast.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
                break;
        }

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                toast.remove();
                if (container.childElementCount === 0) {
                    container.remove();
                }
            }, 300);
        }, 2600);
    };

    /**
     * @param {number} count
     */
    const syncCountBadges = (count) => {
        currentCount = count;

        document.querySelectorAll('[data-compare-count]').forEach((badge) => {
            badge.textContent = String(count);
            if (count > 0) {
                badge.classList.remove('hidden', 'opacity-0');
            } else {
                badge.classList.add('hidden', 'opacity-0');
            }
        });
    };

    /**
     * Attach listeners to add buttons.
     */
    const initAddButtons = () => {
        document.querySelectorAll('[data-compare-add]').forEach((button) => {
            if (button.dataset.compareInit === 'true') {
                return;
            }

            button.dataset.compareInit = 'true';
            const defaultLabel = button.dataset.compareLabelDefault ?? button.textContent?.trim() ?? 'Compare';
            button.dataset.compareLabelDefault = defaultLabel;
            button.dataset.compareLabelAdded = button.dataset.compareLabelAdded ?? 'Added to Compare';
        button.dataset.compareClassDefault = button.dataset.compareClassDefault ?? 'bg-blue-600 hover:bg-blue-700';
        button.dataset.compareClassAdded = button.dataset.compareClassAdded ?? 'bg-green-600 hover:bg-green-700';

            if (button.dataset.compareAdded === 'true') {
                markButtonAsAdded(button);
            } else {
                resetButtonState(button);
            }

            button.addEventListener('click', async (event) => {
                event.preventDefault();
                const productId = Number.parseInt(button.dataset.compareAdd ?? '', 10);

                if (!productId) {
                    return;
                }

                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');

                try {
                    const response = await fetch(`${API_ENDPOINT}/${productId}`, {
                        method: 'POST',
                        headers: requestHeaders,
                    });

                    const payload = await response.json();

                    if (response.status === 409) {
                        notify(payload.message ?? 'Comparison limit reached.', 'error');
                        return;
                    }

                    if (!response.ok) {
                        notify(payload.message ?? 'Failed to add product to compare.', 'error');
                        return;
                    }

                    const itemIds = Array.isArray(payload.items) ? payload.items.map(Number) : [];
                    if (itemIds.includes(productId)) {
                        markButtonAsAdded(button);
                    }

                    syncCountBadges(payload.count ?? currentCount);
                    notify(payload.message ?? 'Product added to comparison.', 'success');
                } catch (error) {
                    console.error(error);
                    notify('Failed to reach the comparison service. Please try again.', 'error');
                } finally {
                    button.disabled = false;
                    button.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            });
        });
    };

    /**
     * Attach listeners to remove buttons.
     */
    const initRemoveButtons = () => {
        document.querySelectorAll('[data-compare-remove]').forEach((button) => {
            if (button.dataset.compareInit === 'true') {
                return;
            }

            button.dataset.compareInit = 'true';

            button.addEventListener('click', async (event) => {
                event.preventDefault();
                const productId = Number.parseInt(button.dataset.compareRemove ?? '', 10);

                if (!productId) {
                    return;
                }

                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');

                try {
                    const response = await fetch(`${API_ENDPOINT}/${productId}`, {
                        method: 'DELETE',
                        headers: requestHeaders,
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        const message = payload?.message ?? 'Unable to update compare list.';
                        const severity = response.status === 409 ? 'warning' : 'error';
                        const title = severity === 'warning' ? 'Limit reached' : 'Action failed';
                        notify(message, severity, title);

                        return;
                    }

                    syncCountBadges(payload.count ?? currentCount);
                    notify(payload.message ?? 'Product removed from comparison.', 'success');

                    if (window.location.pathname.startsWith('/compare')) {
                        window.location.reload();
                    } else {
                        // Reset any corresponding add buttons so the user can add again.
                        document.querySelectorAll(`[data-compare-add="${productId}"]`).forEach((btn) => {
                            btn.dataset.compareAdded = 'false';
                            resetButtonState(btn);
                        });
                    }
                } catch {
                    notify('We could not remove that product. Please try again.', 'error');
                } finally {
                    button.disabled = false;
                    button.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            });
        });
    };

    /**
     * Bind attribute filter toggles for the comparison table.
     */
    const initAttributeFilters = () => {
        const toggles = document.querySelectorAll('[data-compare-attribute-toggle]');
        if (toggles.length === 0) {
            return;
        }

        toggles.forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const attribute = toggle.getAttribute('data-compare-attribute-toggle');
                if (!attribute) {
                    return;
                }

                const rows = document.querySelectorAll(`[data-compare-attribute-row="${attribute}"]`);
                rows.forEach((row) => {
                    if (toggle.checked) {
                        row.classList.remove('hidden');
                        row.style.display = '';
                    } else {
                        row.classList.add('hidden');
                        row.style.display = 'none';
                    }
                });
            });
        });
    };

    /**
     * Handle clear comparison button.
     */
    const initClearButton = () => {
        const button = document.querySelector('[data-compare-clear]');
        if (!button) {
            return;
        }

        const form = button.closest('[data-compare-clear-form]');
        const target = form ?? button;
        if (target.dataset.compareInit === 'true') {
            return;
        }

        target.dataset.compareInit = 'true';

        const handleClear = async (event) => {
            event.preventDefault();

            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');

            try {
                const response = await fetch(`${API_ENDPOINT}/clear`, {
                    method: 'POST',
                    headers: {
                        ...requestHeaders,
                        'Content-Type': 'application/json',
                    },
                });

                const payload = await response.json();

                if (!response.ok) {
                    notify(payload.message ?? 'Failed to clear comparison list.', 'error');
                    return;
                }

                syncCountBadges(0);
                notify(payload.message ?? 'Comparison list cleared.', 'success');

                if (window.location.pathname.startsWith('/compare')) {
                    window.location.reload();
                } else {
                    document.querySelectorAll('[data-compare-add]').forEach((btn) => {
                        btn.dataset.compareAdded = 'false';
                        resetButtonState(btn);
                    });
                }
            } catch {
                notify('Unable to clear comparison list right now.', 'error');
            } finally {
                button.disabled = false;
                button.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        };

        if (form) {
            form.addEventListener('submit', handleClear);
        } else {
            button.addEventListener('click', handleClear);
        }
    };

    /**
     * Fetch initial count to ensure consistency.
     */
    const hydrateInitialCount = async () => {
        try {
            const response = await fetch(API_ENDPOINT, {
                headers: requestHeaders,
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (typeof payload.count === 'number') {
                syncCountBadges(payload.count);
            }
        } catch (error) {
            console.error(error);
        }
    };

    return {
        init() {
            const initialTarget = document.querySelector('[data-compare-count]');
            if (initialTarget) {
                const initialCount = Number.parseInt(initialTarget.dataset.initialCount ?? initialTarget.textContent ?? '0', 10);
                syncCountBadges(Number.isNaN(initialCount) ? 0 : initialCount);
            }

            initAddButtons();
            initRemoveButtons();
            initAttributeFilters();
            initClearButton();
            hydrateInitialCount();
        },
    };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CompareManager.init());
} else {
    CompareManager.init();
}

export default CompareManager;
