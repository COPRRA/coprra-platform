/* eslint-env browser */
/* global document, localStorage, window */
/**
 * Theme Switcher - Light, Dark, and Auto (System) Mode
 * Supports three modes: light, dark, and auto (follows system preference)
 */

(function() {
    'use strict';

    const ThemeSwitcher = {
        // Theme values
        THEMES: {
            LIGHT: 'light',
            DARK: 'dark',
            AUTO: 'auto'
        },

        // Initialize theme switcher
        init: function() {
            this.setupTheme();
            this.setupToggle();
            this.setupSystemPreferenceListener();
        },

        // Get current theme from localStorage or default to 'auto'
        getCurrentTheme: function() {
            return localStorage.getItem('theme') || this.THEMES.AUTO;
        },

        // Save theme to localStorage
        saveTheme: function(theme) {
            localStorage.setItem('theme', theme);
        },

        // Check if system prefers dark mode
        systemPrefersDark: function() {
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        },

        // Apply theme to document
        applyTheme: function(theme) {
            const html = document.documentElement;

            // Remove all theme classes
            html.classList.remove('theme-light', 'theme-dark', 'theme-auto');

            // Determine actual theme to apply
            let actualTheme = theme;
            if (theme === this.THEMES.AUTO) {
                actualTheme = this.systemPrefersDark() ? this.THEMES.DARK : this.THEMES.LIGHT;
            }

            // Add appropriate class
            html.classList.add(`theme-${theme}`);
            html.setAttribute('data-theme', actualTheme);

            // Also add/remove dark-mode class for backward compatibility
            if (actualTheme === this.THEMES.DARK) {
                html.classList.add('dark-mode');
            } else {
                html.classList.remove('dark-mode');
            }
        },

        // Setup initial theme on page load
        setupTheme: function() {
            const currentTheme = this.getCurrentTheme();
            this.applyTheme(currentTheme);
        },

        // Setup theme toggle button/dropdown
        setupToggle: function() {
            const themeToggle = document.getElementById('theme-toggle');
            const themeSelect = document.getElementById('theme-select');
            const themeSelectMobile = document.getElementById('theme-select-mobile');

            // Handle main toggle button click (open/close dropdown)
            if (themeToggle) {
                themeToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const dropdown = themeToggle.closest('.relative');
                    const menu = dropdown?.querySelector('[x-show]') || dropdown?.querySelector('.absolute');
                    
                    if (menu) {
                        const isVisible = menu.style.display !== 'none' && !menu.hasAttribute('hidden');
                        if (isVisible) {
                            menu.style.display = 'none';
                            menu.setAttribute('hidden', '');
                        } else {
                            menu.style.display = 'block';
                            menu.removeAttribute('hidden');
                        }
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (themeToggle && !themeToggle.contains(e.target)) {
                        const dropdown = themeToggle.closest('.relative');
                        const menu = dropdown?.querySelector('[x-show]') || dropdown?.querySelector('.absolute');
                        if (menu && menu.style.display !== 'none') {
                            menu.style.display = 'none';
                            menu.setAttribute('hidden', '');
                        }
                    }
                });
            }

            // Handle theme option buttons (new dropdown style)
            document.querySelectorAll('[data-theme-option]').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const selectedTheme = e.currentTarget.getAttribute('data-theme-option');
                    this.saveTheme(selectedTheme);
                    this.applyTheme(selectedTheme);
                    this.updateToggleUI(selectedTheme);
                    // Close dropdown
                    const dropdown = themeToggle?.closest('.relative');
                    const menu = dropdown?.querySelector('[x-show]') || dropdown?.querySelector('.absolute');
                    if (menu) {
                        menu.style.display = 'none';
                        menu.setAttribute('hidden', '');
                    }
                });
            });

            // Handle select dropdown (desktop)
            if (themeSelect) {
                themeSelect.addEventListener('change', (e) => {
                    const selectedTheme = e.target.value;
                    this.saveTheme(selectedTheme);
                    this.applyTheme(selectedTheme);
                    this.updateToggleUI(selectedTheme);
                });
            }

            // Handle select dropdown (mobile)
            if (themeSelectMobile) {
                themeSelectMobile.addEventListener('change', (e) => {
                    const selectedTheme = e.target.value;
                    this.saveTheme(selectedTheme);
                    this.applyTheme(selectedTheme);
                    this.updateToggleUI(selectedTheme);
                });
            }

            // Update UI on init
            this.updateToggleUI(this.getCurrentTheme());
        },

        // Update toggle button/select UI to reflect current theme
        updateToggleUI: function(theme) {
            const themeToggle = document.getElementById('theme-toggle');
            const themeSelect = document.getElementById('theme-select');
            const themeSelectMobile = document.getElementById('theme-select-mobile');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');

            // Update button icon and text
            if (themeIcon) {
                if (theme === this.THEMES.LIGHT) {
                    themeIcon.className = 'fas fa-sun';
                    if (themeText) themeText.textContent = 'Light';
                } else if (theme === this.THEMES.DARK) {
                    themeIcon.className = 'fas fa-moon';
                    if (themeText) themeText.textContent = 'Dark';
                } else {
                    themeIcon.className = 'fas fa-adjust';
                    if (themeText) themeText.textContent = 'Auto';
                }
            }

            // Update select values (desktop and mobile)
            if (themeSelect) {
                themeSelect.value = theme;
            }
            if (themeSelectMobile) {
                themeSelectMobile.value = theme;
            }
        },

        // Listen for system theme preference changes (for auto mode)
        setupSystemPreferenceListener: function() {
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

                // Use addEventListener if available (modern browsers)
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', (e) => {
                        const currentTheme = this.getCurrentTheme();
                        if (currentTheme === this.THEMES.AUTO) {
                            this.applyTheme(this.THEMES.AUTO);
                        }
                    });
                } else {
                    // Fallback for older browsers
                    mediaQuery.addListener((e) => {
                        const currentTheme = this.getCurrentTheme();
                        if (currentTheme === this.THEMES.AUTO) {
                            this.applyTheme(this.THEMES.AUTO);
                        }
                    });
                }
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ThemeSwitcher.init());
    } else {
        ThemeSwitcher.init();
    }

    // Make ThemeSwitcher available globally for manual control
    window.ThemeSwitcher = ThemeSwitcher;
})();
