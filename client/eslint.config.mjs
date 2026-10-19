import pluginJs from "@eslint/js";
import pluginJsxA11y from "eslint-plugin-jsx-a11y";
import pluginReactHooks from "eslint-plugin-react-hooks";
import pluginReactConfig from "eslint-plugin-react/configs/recommended.js";
import globals from "globals";
import tsEslint from "typescript-eslint";

// Repo rules from the frontend-standards skill (.claude/skills/frontend-standards/rules/<id>.md).
// Every message starts with the rule ID so a lint error can be looked up and cited.
const BOOTSTRAP = "repo-no-bootstrap-utility-classes";
const NO_DIRECT_AXIOS = "repo-no-direct-axios";
const NO_REDUX = "repo-no-redux";
const NO_CLASS_COMPONENTS = "repo-no-class-components";
const ROUTER_V6_HOOKS = "repo-router-v6-hooks";

const restrictedImportPaths = [
  {
    name: "react-bootstrap",
    message: `${BOOTSTRAP}: React-Bootstrap is retired. Use the shared primitives (Button, DialogModal, ConfirmModal, layout, FormControls) with CSS Modules.`,
  },
  {
    name: "react-router-bootstrap",
    message: `${BOOTSTRAP}: react-router-bootstrap is retired. Use react-router-dom Link/NavLink or the shared Button with \`to\`.`,
  },
  {
    name: "bootstrap",
    message: `${BOOTSTRAP}: Bootstrap is retired. Use tokens from src/styles and CSS Modules.`,
  },
  {
    name: "tailwind-merge",
    message: `${BOOTSTRAP}: Tailwind is retired. Compose classes with classnames and CSS Modules.`,
  },
  {
    name: "class-variance-authority",
    message: `${BOOTSTRAP}: Tailwind is retired. Map variants to CSS Module classes explicitly (see src/components/ui/badge.tsx).`,
  },
  {
    name: "tw-animate-css",
    message: `${BOOTSTRAP}: Tailwind is retired. Use CSS Module keyframes with a prefers-reduced-motion guard.`,
  },
  {
    name: "tailwindcss",
    message: `${BOOTSTRAP}: Tailwind is retired.`,
  },
  {
    name: "@/services/axios",
    message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook. Only a temporary legacy adapter may import the transport (see repo-adapter-todo-shape).`,
  },
  {
    name: "axios",
    importNames: ["default"],
    message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook, not axios.`,
  },
  {
    name: "react-redux",
    message: `${NO_REDUX}: server state goes in TanStack Query, local UI state in useState, app-wide dependencies in a Context provider.`,
  },
  {
    name: "@reduxjs/toolkit",
    message: `${NO_REDUX}: server state goes in TanStack Query, local UI state in useState, app-wide dependencies in a Context provider.`,
  },
  {
    name: "redux",
    message: `${NO_REDUX}: server state goes in TanStack Query, local UI state in useState, app-wide dependencies in a Context provider.`,
  },
];

const restrictedImportPatterns = [
  {
    group: ["react-bootstrap/*", "bootstrap/*"],
    message: `${BOOTSTRAP}: Bootstrap is retired. Use the shared primitives and CSS Modules.`,
  },
];

const restrictedSyntax = [
  {
    selector: ":matches(ClassDeclaration, ClassExpression)[superClass.name=/^(Pure)?Component$/]",
    message: `${NO_CLASS_COMPONENTS}: write a function component with hooks.`,
  },
  {
    selector: ":matches(ClassDeclaration, ClassExpression)[superClass.property.name=/^(Pure)?Component$/]",
    message: `${NO_CLASS_COMPONENTS}: write a function component with hooks.`,
  },
  {
    selector: "MemberExpression[object.name='props'][property.name=/^(match|history)$/]",
    message: `${ROUTER_V6_HOOKS}: React Router v6 passes no route props. Use useParams, useNavigate, useSearchParams or useLocation.`,
  },
];

// repo-no-ts-nocheck: no core or installed rule can name the rule ID in its message, so a
// one-rule local plugin reads the comments itself.
const repoPlugin = {
  rules: {
    "no-ts-nocheck": {
      meta: { type: "problem", schema: [] },
      create(context) {
        return {
          Program() {
            for (const comment of context.sourceCode.getAllComments()) {
              if (/@ts-nocheck\b/.test(comment.value)) {
                context.report({
                  loc: comment.loc,
                  message:
                    "repo-no-ts-nocheck: fix the types. For one line that cannot be typed, use @ts-expect-error with a reason.",
                });
              }
            }
          },
        };
      },
    },
  },
};

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
  // Mechanical repo rules from the frontend-standards skill (STANDARDS-02). Class-name usage of
  // the retired frameworks is tracked by scripts/style-audit.mjs.
  {
    files: ["src/**/*.{ts,tsx}"],
    plugins: { repo: repoPlugin },
    rules: {
      "no-restricted-imports": [
        "error",
        {
          paths: restrictedImportPaths,
          patterns: restrictedImportPatterns,
        },
      ],
      "no-restricted-syntax": ["error", ...restrictedSyntax],
      "no-restricted-globals": [
        "error",
        {
          name: "fetch",
          message: `${NO_DIRECT_AXIOS}: call the generated client (src/api/generated) through a feature hook, not fetch.`,
        },
      ],
      "repo/no-ts-nocheck": "error",
    },
  },
  // The transport itself and the Orval mutator are the only modules that own axios.
  {
    files: ["src/services/axios.ts", "src/services/orval-mutator.ts", "src/services/*.test.ts"],
    rules: {
      "no-restricted-imports": [
        "error",
        {
          paths: restrictedImportPaths.filter(({ name }) => name !== "@/services/axios" && name !== "axios"),
          patterns: restrictedImportPatterns,
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
