import pluginJs from "@eslint/js";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginReactConfig from "eslint-plugin-react/configs/recommended.js";
import globals from "globals";
import tsEslint from "typescript-eslint";

// Repo rules from the frontend-standards skill (.claude/skills/frontend-standards/rules/<id>.md).
// Custom messages start with the rule ID so a lint error can be looked up and cited.
const NO_DIRECT_AXIOS = "repo-no-direct-axios";

const restrictedImportPaths = [
  {
    name: "@/services/axios",
    message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook. Only a temporary legacy adapter may import the transport (see repo-adapter-todo-shape).`,
  },
  {
    name: "axios",
    importNames: ["default"],
    message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook, not axios.`,
  },
];

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
  // Mechanical repo rules from the frontend-standards skill (STANDARDS-02).
  {
    files: ["src/**/*.{ts,tsx}"],
    rules: {
      "no-restricted-imports": ["error", { paths: restrictedImportPaths }],
      "no-restricted-globals": [
        "error",
        {
          name: "fetch",
          message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook, not fetch.`,
        },
      ],
      // repo-no-ts-nocheck
      "@typescript-eslint/ban-ts-comment": [
        "error",
        { "ts-nocheck": true, "ts-ignore": false, "ts-expect-error": false, "ts-check": false },
      ],
    },
  },
  // The transport itself and the Orval mutator are the only modules that own axios.
  {
    files: ["src/services/axios.ts", "src/services/orval-mutator.ts", "src/services/*.test.ts"],
    rules: {
      "no-restricted-imports": "off",
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
