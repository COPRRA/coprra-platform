import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { trackError } from '../utils/error-tracker.js';

describe('Error Tracker', () => {
  let originalConsole;

  beforeEach(() => {
    // Save original console
    originalConsole = globalThis.console;

    // Mock console
    globalThis.console = {
      error: vi.fn(),
    };
  });

  afterEach(() => {
    // Restore original console
    globalThis.console = originalConsole;
    vi.clearAllMocks();
  });

  describe('trackError', () => {
    it('should log error to console with default context', () => {
      const testError = new Error('Test error');

      trackError(testError);

      expect(globalThis.console.error).toHaveBeenCalledWith('ErrorTracker:', testError, {});
    });

    it('should log error to console with custom context', () => {
      const testError = new Error('Test error');
      const context = {
        userId: '123',
        action: 'button_click',
        timestamp: Date.now(),
      };

      trackError(testError, context);

      expect(globalThis.console.error).toHaveBeenCalledWith('ErrorTracker:', testError, context);
    });

    it('should handle string errors', () => {
      const errorMessage = 'Something went wrong';

      trackError(errorMessage);

      expect(globalThis.console.error).toHaveBeenCalledWith('ErrorTracker:', errorMessage, {});
    });

    it('should handle undefined error', () => {
      trackError();

      expect(globalThis.console.error).toHaveBeenCalledWith('ErrorTracker:', undefined, {});
    });

    it('should handle complex context objects', () => {
      const error = new Error('Complex error');
      const complexContext = {
        user: {
          id: 123,
          name: 'John Doe',
          permissions: ['read', 'write'],
        },
        request: {
          url: '/api/data',
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
        },
        metadata: {
          timestamp: new Date().toISOString(),
          version: '1.0.0',
        },
      };

      trackError(error, complexContext);

      expect(globalThis.console.error).toHaveBeenCalledWith('ErrorTracker:', error, complexContext);
    });

    it('should be called exactly once per invocation', () => {
      const error = new Error('Test');

      trackError(error);
      trackError(error);

      expect(globalThis.console.error).toHaveBeenCalledTimes(2);
    });
  });
});
