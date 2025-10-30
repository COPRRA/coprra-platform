// eslint.config.js
import pluginJs from "@eslint/js";
import unicorn from "eslint-plugin-unicorn";
import prettier from "eslint-config-prettier";
import globals from "globals";

export default [
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.es2022
      },
      ecmaVersion: 2022,
      sourceType: "module"
    }
  },
  pluginJs.configs.recommended,
  {
    plugins: {
      unicorn
    },
    rules: {
      ...unicorn.configs.recommended.rules,

      // Maximum strictness rules (non-formatting)
      "no-console": "error",
      "no-debugger": "error",
      "no-alert": "error",
      "no-var": "error",
      "prefer-const": "error",
      "prefer-arrow-callback": "error",
      "prefer-template": "error",
      "prefer-destructuring": "error",
      "no-duplicate-imports": "error",
      "no-unused-vars": "error",
      "no-undef": "error",
      "no-unreachable": "error",
      "no-unused-expressions": "error",
      "no-constant-condition": "error",
      "no-dupe-keys": "error",
      "no-dupe-args": "error",
      "no-duplicate-case": "error",
      "no-empty": "error",
      "no-ex-assign": "error",
      "no-extra-boolean-cast": "error",
      "no-extra-semi": "error",
      "no-func-assign": "error",
      "no-inner-declarations": "error",
      "no-invalid-regexp": "error",
      "no-irregular-whitespace": "error",
      "no-obj-calls": "error",
      "no-regex-spaces": "error",
      "no-sparse-arrays": "error",
      "no-unexpected-multiline": "error",
      "use-isnan": "error",
      "valid-typeof": "error",
      "curly": "error",
      "eqeqeq": "error",
      "no-eval": "error",
      "no-implied-eval": "error",
      "no-new-func": "error",
      "no-script-url": "error",
      "no-self-compare": "error",
      "no-sequences": "error",
      "no-throw-literal": "error",
      "no-with": "error",
      "radix": "error",
      "wrap-iife": "error",
      "yoda": "error",
      "no-delete-var": "error",
      "no-label-var": "error",
      "no-shadow": "error",
      "no-shadow-restricted-names": "error",
      "no-use-before-define": "error",

      // Disable formatting rules that conflict with Prettier
      "array-bracket-spacing": "off",
      "comma-dangle": "off",
      "comma-spacing": "off",
      "comma-style": "off",
      "computed-property-spacing": "off",
      "func-call-spacing": "off",
      "indent": "off",
      "key-spacing": "off",
      "keyword-spacing": "off",
      "linebreak-style": "off",
      "no-mixed-spaces-and-tabs": "off",
      "no-multiple-empty-lines": "off",
      "no-trailing-spaces": "off",
      "object-curly-spacing": "off",
      "one-var": "off",
      "padded-blocks": "off",
      "quote-props": "off",
      "quotes": "off",
      "semi": "off",
      "semi-spacing": "off",
      "space-before-blocks": "off",
      "space-before-function-paren": "off",
      "space-in-parens": "off",
      "space-infix-ops": "off",
      "space-unary-ops": "off",
      "spaced-comment": "off",

      // Unicorn specific strict rules
      "unicorn/filename-case": ["error", { "case": "kebabCase" }],
      "unicorn/no-array-reduce": "off", // Allow reduce for functional programming
      "unicorn/prefer-module": "error",
      "unicorn/prefer-node-protocol": "error",
      "unicorn/prefer-query-selector": "error",
      "unicorn/prefer-dom-node-append": "error",
      "unicorn/prefer-dom-node-dataset": "error",
      "unicorn/prefer-dom-node-remove": "error",
      "unicorn/prefer-dom-node-text-content": "error",
      "unicorn/prefer-keyboard-event-key": "error",
      "unicorn/prefer-modern-dom-apis": "error",
      "unicorn/prefer-modern-math-apis": "error",
      "unicorn/prefer-number-properties": "error",
      "unicorn/prefer-reflect-apply": "error",
      "unicorn/prefer-string-replace-all": "error",
      "unicorn/prefer-string-slice": "error",
      "unicorn/prefer-string-starts-ends-with": "error",
      "unicorn/prefer-string-trim-start-end": "error",
      "unicorn/prefer-type-error": "error",
      "unicorn/require-array-join-separator": "error",
      "unicorn/require-number-to-fixed-digits-argument": "error",
      "unicorn/require-post-message-target-origin": "error",
      "unicorn/throw-new-error": "error"
    }
  },
  // Apply Prettier config to disable conflicting rules
  prettier,
  {
    ignores: [
      "vendor/**",
      "node_modules/**",
      "storage/**",
      "bootstrap/cache/**",
      "public/build/**",
      "public/hot",
      "*.min.js",
      "*.bundle.js",
      "resources/js/state/Store.js"
    ]
  }
];