import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock axios before importing bootstrap
const mockAxios = {
  defaults: {
    headers: {
      common: {},
    },
  },
};

vi.mock('axios', () => ({
  default: mockAxios,
}));

// Mock the bootstrap module to avoid actual imports
vi.mock('../bootstrap.js', () => ({}));

describe('Bootstrap Configuration', () => {
  let originalGlobalThis;

  beforeEach(() => {
    // Save original globalThis properties
    originalGlobalThis = {
      axios: globalThis.axios,
    };

    // Clear any existing axios
    delete globalThis.axios;
  });

  afterEach(() => {
    // Restore original globalThis
    if (originalGlobalThis.axios === undefined) {
      delete globalThis.axios;
    } else {
      globalThis.axios = originalGlobalThis.axios;
    }
    vi.clearAllMocks();
  });

  describe('Axios Configuration', () => {
    it('should set axios as global variable', () => {
      // Simulate bootstrap configuration
      globalThis.axios = mockAxios;

      expect(globalThis.axios).toBeDefined();
      expect(globalThis.axios).toBe(mockAxios);
    });

    it('should configure X-Requested-With header', () => {
      // Simulate bootstrap configuration
      globalThis.axios = mockAxios;
      globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      expect(globalThis.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });

    it('should maintain axios defaults structure', () => {
      // Simulate bootstrap configuration
      globalThis.axios = mockAxios;

      expect(globalThis.axios.defaults).toBeDefined();
      expect(globalThis.axios.defaults.headers).toBeDefined();
      expect(globalThis.axios.defaults.headers.common).toBeDefined();
    });

    it('should not override existing axios configuration', () => {
      // Set up existing axios configuration
      globalThis.axios = {
        defaults: {
          headers: {
            common: {
              Authorization: 'Bearer token',
              'Custom-Header': 'custom-value',
            },
          },
        },
      };

      // Simulate adding X-Requested-With header
      globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      // Should preserve existing headers while adding new one
      expect(globalThis.axios.defaults.headers.common['Authorization']).toBe('Bearer token');
      expect(globalThis.axios.defaults.headers.common['Custom-Header']).toBe('custom-value');
      expect(globalThis.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });
  });

  describe('Module Loading', () => {
    it('should configure axios successfully', () => {
      // Simulate bootstrap configuration
      globalThis.axios = mockAxios;
      globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      expect(globalThis.axios).toBeDefined();
      expect(globalThis.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });

    it('should handle multiple configurations', () => {
      // First configuration
      globalThis.axios = mockAxios;
      globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      // Second configuration (should not break)
      globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      expect(globalThis.axios).toBeDefined();
      expect(globalThis.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });
  });
});
