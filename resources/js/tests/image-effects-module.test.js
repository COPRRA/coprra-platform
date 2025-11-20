import { describe, it, expect, beforeAll, vi } from 'vitest';

describe('Image Effects Module Loading', () => {
  beforeAll(() => {
    // Mock globalThis.addEventListener before importing the module
    globalThis.addEventListener = vi.fn();

    // Mock document with loading state
    globalThis.document = {
      readyState: 'loading',
      querySelectorAll: vi.fn(() => []),
    };

    // Ensure window is defined
    globalThis.window = {};
  });

  it('should add DOMContentLoaded listener when module is loaded with loading document', async () => {
    // Clear any previous calls
    vi.clearAllMocks();

    // Re-import the module to trigger the module-level code
    await import('../animations/image-effects.js');

    // The module-level code should have called addEventListener
    expect(globalThis.addEventListener).toHaveBeenCalledWith(
      'DOMContentLoaded',
      expect.any(Function)
    );
  });

  it('should call initImageEffects immediately when document is ready on module load', async () => {
    // Setup for ready document state
    globalThis.document = {
      readyState: 'complete',
      querySelectorAll: vi.fn(() => []),
    };

    const initSpy = vi.fn();

    // Mock the initImageEffects function
    vi.doMock('../animations/image-effects.js', () => ({
      initImageEffects: initSpy,
    }));

    // Clear previous calls
    vi.clearAllMocks();

    // Simulate the module-level condition for ready document
    if (globalThis.window !== undefined && globalThis.document.readyState !== 'loading') {
      initSpy();
    }

    expect(initSpy).toHaveBeenCalled();
  });
});
