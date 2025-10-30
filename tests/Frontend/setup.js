/**
 * Frontend Test Setup
 * This file configures the testing environment for frontend tests
 */

import { beforeAll, afterAll, beforeEach, afterEach } from 'vitest';
import { cleanup } from '@testing-library/vue';

// Global test setup
beforeAll(() => {
  // Setup global test environment
  console.log('🧪 Setting up frontend test environment...');
  
  // Mock window.matchMedia
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: (query) => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: () => {},
      removeListener: () => {},
      addEventListener: () => {},
      removeEventListener: () => {},
      dispatchEvent: () => {},
    }),
  });

  // Mock IntersectionObserver
  global.IntersectionObserver = class IntersectionObserver {
    constructor() {}
    observe() {}
    unobserve() {}
    disconnect() {}
  };

  // Mock ResizeObserver
  global.ResizeObserver = class ResizeObserver {
    constructor() {}
    observe() {}
    unobserve() {}
    disconnect() {}
  };

  // Mock localStorage
  const localStorageMock = {
    getItem: (key) => localStorageMock[key] || null,
    setItem: (key, value) => { localStorageMock[key] = value; },
    removeItem: (key) => { delete localStorageMock[key]; },
    clear: () => {
      Object.keys(localStorageMock).forEach(key => {
        if (key !== 'getItem' && key !== 'setItem' && key !== 'removeItem' && key !== 'clear') {
          delete localStorageMock[key];
        }
      });
    }
  };
  Object.defineProperty(window, 'localStorage', { value: localStorageMock });

  // Mock sessionStorage
  const sessionStorageMock = {
    getItem: (key) => sessionStorageMock[key] || null,
    setItem: (key, value) => { sessionStorageMock[key] = value; },
    removeItem: (key) => { delete sessionStorageMock[key]; },
    clear: () => {
      Object.keys(sessionStorageMock).forEach(key => {
        if (key !== 'getItem' && key !== 'setItem' && key !== 'removeItem' && key !== 'clear') {
          delete sessionStorageMock[key];
        }
      });
    }
  };
  Object.defineProperty(window, 'sessionStorage', { value: sessionStorageMock });
});

// Cleanup after each test
afterEach(() => {
  cleanup();
  
  // Clear all mocks
  if (typeof vi !== 'undefined') {
    vi.clearAllMocks();
  }
  
  // Clear localStorage and sessionStorage
  if (window.localStorage) {
    window.localStorage.clear();
  }
  if (window.sessionStorage) {
    window.sessionStorage.clear();
  }
});

// Global teardown
afterAll(() => {
  console.log('🧹 Cleaning up frontend test environment...');
});

// Global error handler for unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});