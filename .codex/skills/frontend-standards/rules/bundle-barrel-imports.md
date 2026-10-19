---
title: Avoid Barrel File Imports
impact: MEDIUM
impactDescription: smaller lazy chunks, faster dev reloads
tags: bundle, imports, tree-shaking, barrel-files, performance
---

## Avoid Barrel File Imports

Import directly from source files instead of barrel files to avoid loading thousands of unused modules. **Barrel files** are entry points that re-export multiple modules (e.g., `index.js` that does `export * from './module'`).

Popular icon and component libraries can have **up to 10,000 re-exports** in their entry file. For many React packages, **it takes 200-800ms just to import them**, affecting both development speed and production cold starts.

**What Vite already covers:** Vite pre-bundles each dependency in `node_modules` into one module during development, and Rollup tree-shakes ES-module packages in the production build. So a named import from an ESM package such as `lucide-react` costs little here. The rule still bites for CommonJS packages, which Rollup cannot tree-shake, and for in-repo `index.ts` barrels: Vite serves those file by file in development, and a barrel with side effects pulls its whole folder into whichever lazy chunk imports it. This is why the upstream CRITICAL impact is lowered to MEDIUM for this stack.

**Incorrect (a folder-wide barrel pulls every feature component into the article list chunk):**

```tsx
// src/components/features/index.ts
export * from './LibraryCards/ArticleCard';
export * from './LibraryCards/CatalogueCard';
export * from './comment/CommentsBlock';
export * from './flashcards/SessionSummary';

// src/routes/ArticlesList/index.tsx
import { ArticleCard } from '@/components/features';
```

**Correct (import the module that owns the component):**

```tsx
import { ArticleCard } from '@/components/features/LibraryCards/ArticleCard';
```

A small barrel that only re-exports one cohesive folder, such as `src/components/shared/layout` (`Container`, `Stack`, `Cluster`, `Grid`), is fine: everything in it is used together.

Reference: [Vite dependency pre-bundling](https://vite.dev/guide/dep-pre-bundling)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, adapted for this Vite SPA._
