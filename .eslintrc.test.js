module.exports = {
  extends: [
    './.eslintrc.js',
    'plugin:vitest/recommended'
  ],
  env: {
    'vitest-globals/env': true
  },
  plugins: [
    'vitest'
  ],
  rules: {
    // Test-specific rules
    'vitest/expect-expect': 'error',
    'vitest/no-disabled-tests': 'warn',
    'vitest/no-focused-tests': 'error',
    'vitest/no-identical-title': 'error',
    'vitest/prefer-to-be': 'error',
    'vitest/prefer-to-contain': 'error',
    'vitest/prefer-to-have-length': 'error',
    'vitest/valid-expect': 'error',
    'vitest/valid-describe-callback': 'error',
    'vitest/no-conditional-expect': 'error',
    'vitest/no-conditional-in-test': 'error',
    'vitest/no-conditional-tests': 'error',
    'vitest/no-duplicate-hooks': 'error',
    'vitest/no-test-return-statement': 'error',
    'vitest/prefer-hooks-on-top': 'error',
    'vitest/require-top-level-describe': 'error',
    
    // Allow console in tests
    'no-console': 'off',
    
    // Allow unused vars in test files (for mocks)
    'no-unused-vars': 'off',
    '@typescript-eslint/no-unused-vars': 'off',
    
    // Allow any type in tests
    '@typescript-eslint/no-explicit-any': 'off',
    
    // Allow non-null assertion in tests
    '@typescript-eslint/no-non-null-assertion': 'off',
    
    // Allow empty functions in tests (for mocks)
    '@typescript-eslint/no-empty-function': 'off'
  },
  overrides: [
    {
      files: ['**/*.test.{js,ts,jsx,tsx}', '**/*.spec.{js,ts,jsx,tsx}'],
      rules: {
        // Additional test-specific overrides
        'max-lines': 'off',
        'max-lines-per-function': 'off'
      }
    }
  ]
};