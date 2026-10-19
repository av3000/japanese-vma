---
title: Lazy-Load Heavy Components With React.lazy
impact: CRITICAL
impactDescription: directly affects TTI and LCP
tags: bundle, dynamic-import, code-splitting, react-lazy, vite
---

## Lazy-Load Heavy Components With React.lazy

**Impact: CRITICAL (directly affects TTI and LCP)**

Split routes, and heavy components that the first paint does not need, into their own Vite chunk with `React.lazy` and `import()`. Routes go through `createLazyRoute` in `src/routes/routeLoading/createLazyRoute.tsx`, which pairs `lazy()` with a `PageLoading` fallback. Below-the-fold feature blocks use `lazy()` directly, inside their own `Suspense`.

**Incorrect (the comments block ships in the catalogue detail chunk although it sits below the fold):**

```tsx
import CommentsBlock from '@/components/features/comment/CommentsBlock';

const CatalogueContent = ({ catalogue }: CatalogueContentProps) => (
	<section aria-labelledby={commentsHeadingId}>
		<CommentsBlock parent="catalogue" entityId={catalogue.id} entityUuid={catalogue.uuid} />
	</section>
);
```

**Correct (its own chunk, loaded when the section renders):**

```tsx
import { lazy, Suspense } from 'react';

const LazyCommentsBlock = lazy(() => import('@/components/features/comment/CommentsBlock'));

const CatalogueContent = ({ catalogue }: CatalogueContentProps) => (
	<section aria-labelledby={commentsHeadingId}>
		<Suspense fallback={null}>
			<LazyCommentsBlock parent="catalogue" entityId={catalogue.id} entityUuid={catalogue.uuid} />
		</Suspense>
	</section>
);
```

**Correct (routes):**

```tsx
const ArticlesListPage = createLazyRoute(() => import('@/routes/ArticlesList'), { family: 'list' });
```

Reference: [React lazy](https://react.dev/reference/react/lazy), [Vite dynamic import](https://vite.dev/guide/features#dynamic-import)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
