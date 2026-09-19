import pluginJs from "@eslint/js";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginReactConfig from "eslint-plugin-react/configs/recommended.js";
import globals from "globals";
import tsEslint from "typescript-eslint";

/** @type {import('eslint').Linter.FlatConfig[]} */
export default [
  {
    ignores: ["node_modules/", "dist/", "generated/", "src/api/generated/**", "**/*.js", "**/*.cjs", "vite.config.ts"],
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
      "@typescript-eslint/no-explicit-any": "off",
      "@typescript-eslint/ban-ts-comment": "off",
      "@typescript-eslint/no-unused-vars": "off",
      "react/prop-types": "off",
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
  // Styling architecture guardrail (STYLING-01): Bootstrap is being retired in favour of
  // CSS Modules and the shared primitives under src/components/shared. New imports of the
  // Bootstrap packages are an error. Class-name usage is tracked by scripts/style-audit.mjs.
  {
    files: ["src/**/*.{ts,tsx}"],
    rules: {
      "no-restricted-imports": [
        "error",
        {
          paths: [
            {
              name: "react-bootstrap",
              message:
                "React-Bootstrap is retired. Use the shared primitives (Button, DialogModal, ConfirmModal, layout, FormControls) with CSS Modules.",
            },
            {
              name: "react-router-bootstrap",
              message: "react-router-bootstrap is retired. Use react-router-dom Link/NavLink or the shared Button with `to`.",
            },
            {
              name: "bootstrap",
              message: "Bootstrap is retired. Use tokens from src/styles and CSS Modules.",
            },
            {
              name: "tailwind-merge",
              message: "Tailwind is retired. Compose classes with classnames and CSS Modules.",
            },
            {
              name: "class-variance-authority",
              message: "Tailwind is retired. Map variants to CSS Module classes explicitly (see src/components/ui/badge.tsx).",
            },
            {
              name: "tw-animate-css",
              message: "Tailwind is retired. Use CSS Module keyframes with a prefers-reduced-motion guard.",
            },
            {
              name: "tailwindcss",
              message: "Tailwind is retired.",
            },
          ],
          patterns: [
            {
              group: ["react-bootstrap/*", "bootstrap/*"],
              message: "Bootstrap is retired. Use the shared primitives and CSS Modules.",
            },
          ],
        },
      ],
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
