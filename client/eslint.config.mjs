import pluginJs from "@eslint/js";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginReactConfig from "eslint-plugin-react/configs/recommended.js";
import globals from "globals";
import tsEslint from "typescript-eslint";

/** @type {import('eslint').Linter.FlatConfig[]} */
export default [
  {
    ignores: ["node_modules/", "dist/", "generated/", "**/*.js", "**/*.cjs", "vite.config.ts"],
  },
  {
    languageOptions: {
      globals: globals.browser,
      parserOptions: {
        project: "./tsconfig.json",
      },
    },
  },
  pluginJs.configs.recommended,
  ...tsEslint.configs.strict,
  {
    ...pluginReactConfig,
    settings: {
      react: { version: "detect" },
    },
  },
  // TypeScript config
  {
    files: ["**/*.ts", "**/*.tsx"],
    languageOptions: {
      parser: tsEslint.parser,
      parserOptions: {
        ecmaVersion: "latest",
        sourceType: "module",
      },
    },
    rules: {
      "@typescript-eslint/no-explicit-any": "warn",
      "@typescript-eslint/ban-ts-comment": "off",
      "@typescript-eslint/no-unused-vars": "off",
    },
  },
  // ...other configs
  {
    files: ["**/*.tsx", "**/*.jsx"],
    rules: {
      "react/react-in-jsx-scope": "off",
      "react/no-unescaped-entities": "off",
    },
  },
  {
    plugins: {
      // Should be updated to the new syntax once https://github.com/jsx-eslint/eslint-plugin-jsx-a11y/issues/978 is resolved.
      "jsx-a11y": pluginJsxA11y,
      // Should be updated to the new syntax once https://github.com/facebook/react/issues/28313 is resolved.
      "react-hooks": pluginReactHooks,
    },
    rules: {
      ...pluginJsxA11y.configs.recommended.rules,
      ...pluginReactHooks.configs.recommended.rules,
    },
  },
];
