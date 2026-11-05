import pluginJs from "@eslint/js";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginReactConfig from "eslint-plugin-react/configs/recommended.js";
import pluginStorybook from "eslint-plugin-storybook";
import globals from "globals";
import tsEslint from "typescript-eslint";

// Repo rules from the frontend-standards skill (.claude/skills/frontend-standards/rules/<id>.md).
// Custom messages start with the rule ID so a lint error can be looked up and cited.
const NO_DIRECT_AXIOS = "repo-no-direct-axios";

// A theme changes appearance, never behaviour: code outside src/themes/ reads a slot from useTheme()
// and never compares a theme id (STYLING-03, #498; client/DESIGN.md §3). Keep this list equal to
// THEME_IDS in src/themes/manifest.ts; src/themes/eslintThemeIds.test.ts checks it.
const THEME_IDS = ["kinari"];
const THEME_ID = `/^(${THEME_IDS.join("|")})$/`;
const THEME_NAME_CHECK =
  "repo-theme-slots-not-names: a theme changes appearance only. Read a slot from useTheme() (or add one to src/themes/registry.ts) instead of comparing a theme id.";
const themeNameChecks = [
  `BinaryExpression[operator=/^[!=]==?$/] > Literal[value=${THEME_ID}]`,
  `SwitchCase > Literal.test[value=${THEME_ID}]`,
  `MemberExpression[computed=true] > Literal.property[value=${THEME_ID}]`,
].map((selector) => ({ selector, message: THEME_NAME_CHECK }));

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
  {
    files: ["src/**/*.{ts,tsx}"],
    ignores: ["src/themes/**"],
    rules: {
      "no-restricted-syntax": ["error", ...themeNameChecks],
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
  // Story files (CSF): see src/storybook/docs/Authoring.mdx.
  ...pluginStorybook.configs["flat/recommended"],
];
