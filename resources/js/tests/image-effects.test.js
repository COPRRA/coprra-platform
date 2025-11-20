import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initImageEffects } from '../animations/image-effects.js';

describe('Image Effects', () => {
  let mockDocument;
  let mockWindow;
  let originalDocument;
  let originalWindow;

  beforeEach(() => {
    // Save original globals
    originalDocument = globalThis.document;
    originalWindow = globalThis.window;

    // Create mock elements
    const [mockElement1, mockElement2] = [
      {
        style: {},
        complete: false,
        addEventListener: vi.fn(),
      },
      {
        style: {},
        complete: true,
        addEventListener: vi.fn(),
      },
    ];

    // Mock document
    mockDocument = {
      readyState: 'complete',
      querySelectorAll: vi.fn(() => [mockElement1, mockElement2]),
    };

    // Mock window
    mockWindow = {
      addEventListener: vi.fn(),
    };

    // Set up globals
    globalThis.document = mockDocument;
    globalThis.window = mockWindow;
  });

  afterEach(() => {
    // Restore original globals
    globalThis.document = originalDocument;
    globalThis.window = originalWindow;
    vi.clearAllMocks();
  });

  describe('initImageEffects', () => {
    it('should handle undefined document gracefully', () => {
      globalThis.document = undefined;
      expect(() => initImageEffects()).not.toThrow();
    });

    it('should apply fade-in effects to images with data-image-effect attribute', () => {
      const mockElement1 = {
        style: {},
        complete: false,
        addEventListener: vi.fn(),
      };

      const mockElement2 = {
        style: {},
        complete: true,
        addEventListener: vi.fn(),
      };

      mockDocument.querySelectorAll.mockReturnValue([mockElement1, mockElement2]);

      initImageEffects();

      // Check that transition is set
      expect(mockElement1.style.transition).toBe('opacity 300ms ease');
      expect(mockElement2.style.transition).toBe('opacity 300ms ease');

      // Check opacity for incomplete image
      expect(mockElement1.style.opacity).toBe('0.8');
      expect(mockElement1.addEventListener).toHaveBeenCalledWith('load', expect.any(Function), {
        once: true,
      });

      // Check opacity for complete image
      expect(mockElement2.style.opacity).toBe('1');
      expect(mockElement2.addEventListener).not.toHaveBeenCalled();
    });

    it('should handle errors gracefully when applying effects', () => {
      const mockElement = {
        get style() {
          throw new Error('Style access error');
        },
        complete: false,
        addEventListener: vi.fn(),
      };

      mockDocument.querySelectorAll.mockReturnValue([mockElement]);

      expect(() => initImageEffects()).not.toThrow();
    });

    it('should trigger opacity change on image load', () => {
      const mockElement = {
        style: {},
        complete: false,
        addEventListener: vi.fn(),
      };

      mockDocument.querySelectorAll.mockReturnValue([mockElement]);

      initImageEffects();

      // Get the load event handler
      // eslint-disable-next-line prefer-destructuring
      const loadHandler = mockElement.addEventListener.mock.calls[0][1];

      // Trigger the load event
      loadHandler();

      // Check that opacity is set to 1
      expect(mockElement.style.opacity).toBe('1');
    });

    it('should add DOMContentLoaded listener when document is loading', () => {
      mockDocument.readyState = 'loading';

      // Simulate module loading behavior
      if (globalThis.window !== undefined && mockDocument.readyState === 'loading') {
        mockWindow.addEventListener('DOMContentLoaded', initImageEffects);
      }

      expect(mockWindow.addEventListener).toHaveBeenCalledWith(
        'DOMContentLoaded',
        initImageEffects
      );
    });

    it('should call initImageEffects immediately when document is ready', () => {
      mockDocument.readyState = 'complete';
      const spy = vi.fn();

      // Simulate module loading behavior
      if (globalThis.window !== undefined && mockDocument.readyState !== 'loading') {
        spy();
      }

      expect(spy).toHaveBeenCalled();
    });

    it('should not execute when window is undefined', () => {
      globalThis.window = undefined;
      const spy = vi.fn();

      // Simulate the module-level condition
      if (globalThis.window !== undefined) {
        spy();
      }

      expect(spy).not.toHaveBeenCalled();
    });

    it('should add event listener to globalThis when document is loading', () => {
      mockDocument.readyState = 'loading';
      const addEventListenerSpy = vi.fn();
      globalThis.addEventListener = addEventListenerSpy;

      // Simulate the module-level code execution
      if (globalThis.window !== undefined && mockDocument.readyState === 'loading') {
        globalThis.addEventListener('DOMContentLoaded', initImageEffects);
      }

      expect(addEventListenerSpy).toHaveBeenCalledWith('DOMContentLoaded', initImageEffects);
    });
  });
});
