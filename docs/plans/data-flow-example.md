# Data Flow Guide: Search Results & Pagination

> A focused guide on how search and pagination data flows from server fetch through client state management to rendered UI. Extracted from Blueprint's `ProductList/Results` component tree. Includes Vercel React best-practice annotations.

---

## Component Tree & Responsibility Map

```
Results/
├── Results.tsx              SERVER COMPONENT ─ Fetches, maps, calculates pagination
├── ResultsSkeleton.tsx      SKELETON ─ Loading placeholder (matches real grid layout)
├── actions.ts               SERVER ACTIONS ─ Load more / load previous (same pipeline)
├── types.ts                 CONTRACTS ─ Discriminated union response types + type guard
├── index.tsx                BARREL ─ Re-exports Results + ResultsSkeleton
│
└── ClientResults/
    ├── index.tsx             CLIENT STATE MANAGER ─ Accumulated state, URL sync
    ├── ProductGrid.tsx       PRESENTATIONAL ─ Pure grid renderer, zero side effects
    ├── ClientLoadMore/       PRESENTATIONAL ─ Progress bar + "Show next" button
    └── ClientLoadPrev/       PRESENTATIONAL ─ "Show previous" button
```

**Each file has exactly one job.** If you need to understand _what_ data is fetched — read `Results.tsx`. If you need to understand _how_ state accumulates — read `ClientResults/index.tsx`. If you need to understand _what renders_ — read `ProductGrid.tsx`.

---

## The Full Data Flow

### Phase 1: Server Fetch & Mapping (Results.tsx)

```
URL params (parsed by page.tsx)
      │
      ▼
┌─ Results.tsx (async Server Component) ──────────────────────────┐
│                                                                  │
│  1. PARALLEL FETCH — Independent data in one round-trip          │
│     ┌────────────────────────────────────────────────────┐       │
│     │ const [translations, authSettings] =               │       │
│     │   await Promise.all([                              │       │
│     │     fetchTranslations(segmentationId),             │       │
│     │     fetchAuthenticatedSettings(segmentationId),    │       │
│     │   ]);                                              │       │
│     └────────────────────────────────────────────────────┘       │
│                          │                                       │
│  2. SEARCH — Uses auth token from step 1                         │
│     searchProducts(query, page=1, token, settings)               │
│                          │                                       │
│  3. MAP — Raw API response → domain types                        │
│     mapSearchResultServer(rawData, query, ...)                   │
│       └→ { products: ProductItemResponse[], pagingInfo }         │
│                          │                                       │
│  4. CALCULATE — Derived pagination scalars                       │
│     totalProducts = pagingInfo.totalNumberOfItems                │
│     pageSize = pagingInfo.pageSize                               │
│     hasNextPage = currentPage < ceil(total / pageSize)           │
│                          │                                       │
│  5. RENDER — Pass ONLY what the client needs                     │
│     <ClientResults                                               │
│       initialProducts={searchResult.products}   ← mapped array  │
│       query={query}                             ← for refetch    │
│       currentPage={currentPage}                 ← scalar         │
│       pageSize={pageSize}                       ← scalar         │
│       initialHasNextPage={hasNextPage}          ← boolean        │
│       totalProducts={totalProducts}             ← scalar         │
│     />                                                           │
└──────────────────────────────────────────────────────────────────┘
```

#### Why this matters

| Practice                                                    | Rule                   | Impact                                                              |
| ----------------------------------------------------------- | ---------------------- | ------------------------------------------------------------------- |
| `Promise.all` for translations + auth settings              | `async-parallel`       | **CRITICAL** — Eliminates waterfall; 2× faster than sequential      |
| Raw API types never cross the server→client boundary        | `server-serialization` | **HIGH** — Only serializes fields the client renders                |
| Pagination math done on server, passed as scalars           | `server-serialization` | **HIGH** — Client receives booleans/numbers, not raw paging objects |
| Search depends on auth token → sequential after Promise.all | `async-parallel`       | Intentional — `searchProducts` _needs_ the token from step 1        |

#### Blueprint source (complete)

```typescript
// Results.tsx
export async function Results({ query, settings, currentPage }: ResultsProps) {
  // Step 1: Parallel fetch — independent operations
  const [translations, authenticatedSettings] = await Promise.all([
    fetchTranslations(query.segmentationId),
    fetchAuthenticatedSettings(query.segmentationId),
  ]);

  // Step 2: Search — depends on auth token from step 1
  const searchData = await searchProducts(
    query, 1, authenticatedSettings?.searchToken || '', settings
  );

  // Step 3: Map raw API → domain types
  const searchResult = mapSearchResultServer(searchData, query, undefined, translations, settings);

  // Step 4: Calculate pagination
  const totalProducts = searchResult.pagingInformation.totalNumberOfItems;
  const pageSize = searchResult.pagingInformation.pageSize;
  const totalPages = Math.ceil(totalProducts / pageSize);
  const hasNextPage = currentPage < totalPages;

  // Step 5: Pass minimal, pre-computed props to client
  return (
    <ClientResults
      initialProducts={searchResult.products}
      query={query}
      currentPage={currentPage}
      pageSize={pageSize}
      initialHasNextPage={hasNextPage}
      totalProducts={totalProducts}
    />
  );
}
```

---

### Phase 2: Client State Management (ClientResults/index.tsx)

```
Server-rendered props arrive
      │
      ▼
┌─ ClientResults (Client Component, 'use client') ───────────────┐
│                                                                  │
│  STATE INITIALIZATION — Seeded from server-rendered props        │
│  ┌────────────────────────────────────────────────────────┐      │
│  │ products[]        ← initialProducts (server data)     │      │
│  │ currentPage       ← initialCurrentPage                │      │
│  │ nextPage          ← initialCurrentPage + 1            │      │
│  │ hasNextPage       ← initialHasNextPage                │      │
│  │ previousPage      ← initialCurrentPage - 1            │      │
│  │ hasPreviousPage   ← initialCurrentPage > 1            │      │
│  │ isLoadingNext     ← false                             │      │
│  │ isLoadingPrev     ← false                             │      │
│  │ error             ← null                              │      │
│  └────────────────────────────────────────────────────────┘      │
│                                                                  │
│  EFFECT 1: RESET — When server re-renders (filter/sort change)  │
│  ┌────────────────────────────────────────────────────────┐      │
│  │ useEffect(() => {                                      │      │
│  │   setProducts(initialProducts);                       │      │
│  │   setCurrentPage(initialCurrentPage);                 │      │
│  │   setNextPage(initialCurrentPage + 1);                │      │
│  │   setHasNextPage(initialHasNextPage);                 │      │
│  │   ...                                                 │      │
│  │ }, [initialProducts, initialCurrentPage,              │      │
│  │     initialHasNextPage]);                             │      │
│  └────────────────────────────────────────────────────────┘      │
│                                                                  │
│  EFFECT 2: URL SYNC — Side effect of page state, not trigger     │
│  ┌────────────────────────────────────────────────────────┐      │
│  │ useEffect(() => {                                      │      │
│  │   // Only update URL if page changed                  │      │
│  │   if (currentPage !== urlPage) {                      │      │
│  │     history.replaceState(...)  // NOT navigation      │      │
│  │   }                                                   │      │
│  │ }, [currentPage, pathname, searchParams]);             │      │
│  └────────────────────────────────────────────────────────┘      │
│                                                                  │
│  CALLBACKS: Load More / Load Previous                            │
│  ┌────────────────────────────────────────────────────────┐      │
│  │ handleLoadMore = useCallback(async () => {            │      │
│  │   setIsLoadingNext(true)                              │      │
│  │   const result = await loadMoreProducts(query, next)  │      │
│  │   if (isServerActionError(result)) → setError(msg)    │      │
│  │   else → APPEND to products[], advance page cursors   │      │
│  │   setIsLoadingNext(false)                             │      │
│  │ }, [query, nextPage])                                 │      │
│  │                                                       │      │
│  │ handleLoadPrevious = useCallback(async () => {        │      │
│  │   setIsLoadingPrev(true)                              │      │
│  │   const result = await loadPreviousProducts(query, p) │      │
│  │   if (isServerActionError(result)) → setError(msg)    │      │
│  │   else → PREPEND to products[], retreat page cursors  │      │
│  │   setIsLoadingPrev(false)                             │      │
│  │ }, [query, previousPage])                             │      │
│  └────────────────────────────────────────────────────────┘      │
│                                                                  │
│  RENDER — Composes three presentational children                 │
│  ┌────────────────────────────────────────────────────────┐      │
│  │ {error &&  <ErrorAlert />}                            │      │
│  │ <ClientLoadPrev  ← hasPrevious, onLoadPrev, loading   │      │
│  │ <ProductGrid     ← products[], translations           │      │
│  │ <ClientLoadMore  ← page, total, pageSize, hasNext,    │      │
│  │                    onLoadMore, loading                 │      │
│  └────────────────────────────────────────────────────────┘      │
└──────────────────────────────────────────────────────────────────┘
```

#### Key patterns & why

| Pattern                                | Implementation                                       | Why                                                                     |
| -------------------------------------- | ---------------------------------------------------- | ----------------------------------------------------------------------- |
| **Server-seeded state**                | `useState(initialProducts)`                          | First render is server HTML — no loading spinner, no CLS, SEO-friendly  |
| **Accumulated state**                  | `setProducts(prev => [...prev, ...new])`             | Products array grows; user sees all loaded pages seamlessly             |
| **Reset on upstream change**           | `useEffect([initialProducts])`                       | When sort/filter triggers RSC re-render, client state resets cleanly    |
| **URL as side effect**                 | `history.replaceState` in useEffect                  | URL reflects state but doesn't _drive_ data fetching — prevents loops   |
| **Functional setState**                | `setProducts(prev => [...prev, ...result.products])` | Per Vercel rule `rerender-functional-setstate`: prevents stale closures |
| **Separate loading states**            | `isLoadingNext` + `isLoadingPrev`                    | Correct spinner shown for each independent action                       |
| **Discriminated union error handling** | `isServerActionError(result)` type guard             | Type-safe branching; errors handled without try/catch at render level   |

---

### Phase 3: Server Actions — Same Pipeline, Client-Callable (actions.ts)

When the user clicks "Load More" or "Show Previous", the client calls a **server action** that runs the exact same fetch→map pipeline as the initial render.

```
ClientResults calls loadMoreProducts(query, nextPage)
      │
      ▼
┌─ actions.ts ('use server') ─────────────────────────────────────┐
│                                                                  │
│  1. PARALLEL FETCH (same as Results.tsx)                         │
│     Promise.all([fetchSettings, fetchTranslations, fetchAuth])   │
│                                                                  │
│  2. SEARCH                                                       │
│     searchProducts(query, nextPage, token, settings)             │
│                                                                  │
│  3. MAP (same mapping function)                                  │
│     mapSearchResultServer(rawData, query, ...)                   │
│                                                                  │
│  4. CALCULATE pagination for response                            │
│     hasNextPage = nextPage < ceil(total / pageSize)              │
│                                                                  │
│  5. RETURN typed response                                        │
│     ┌─── success ───────────────────────────┐                    │
│     │ { products[], hasNextPage, nextPage,   │                   │
│     │   totalProducts }                      │                   │
│     └───────────────────────────────────────┘                    │
│     ┌─── error ─────────────────────────────┐                    │
│     │ { error: true, message: string }       │                   │
│     └───────────────────────────────────────┘                    │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
      │
      ▼
ClientResults checks with isServerActionError(result)
  → success: append/prepend products, update pagination state
  → error: display error message via role="alert"
```

#### Contract types (types.ts)

```typescript
interface LoadMoreResult {
  products: ProductItemResponse[];
  hasNextPage: boolean;
  nextPage: number;
  totalProducts: number;
}

interface ServerActionError {
  error: true;
  message: string;
}

type LoadMoreResponse = LoadMoreResult | ServerActionError;

// Type guard — the only way to distinguish success from error
function isServerActionError(
  response: LoadMoreResponse,
): response is ServerActionError {
  return "error" in response && response.error;
}
```

**Why discriminated unions?** The server action never throws to the client. It always returns a typed result. The client uses a type guard to branch — no `try/catch` needed around the response, and TypeScript narrows the type after the check.

---

### Phase 4: Presentational Rendering

```
ClientResults passes props down
      │
      ├─────────────────────┬──────────────────────┐
      ▼                     ▼                      ▼
┌─ ClientLoadPrev ─┐  ┌─ ProductGrid ──────┐  ┌─ ClientLoadMore ─────────┐
│                   │  │                    │  │                           │
│ Props:            │  │ Props:             │  │ Props:                    │
│  hasPreviousPage  │  │  products[]        │  │  currentPage              │
│  onLoadPrevious   │  │  translations      │  │  totalProducts            │
│  isLoading        │  │                    │  │  pageSize                 │
│                   │  │ Renders:           │  │  hasNextPage              │
│ Renders:          │  │  Empty state check │  │  onLoadMore               │
│  null (if !has)   │  │  Grid of           │  │  isLoading                │
│  or Button        │  │  <ProductCard />   │  │                           │
│                   │  │  with lazy/eager   │  │ Renders:                  │
│                   │  │  image loading     │  │  null (if total === 0)    │
│                   │  │                    │  │  or progress text         │
│                   │  │                    │  │  + <ProgressBar />        │
│                   │  │                    │  │  + Button (if hasNext)    │
└───────────────────┘  └────────────────────┘  └───────────────────────────┘
```

#### ProductGrid — The core renderer

```typescript
export const ProductGrid: React.FC<ProductGridProps> = ({ products, translations }) => {
  // Rule: Handle empty state at the leaf, not the parent
  if (!products.length) {
    return <p>{translations?.shared.noProductsFound ?? 'No products found.'}</p>;
  }

  return (
    <div className={classNames(styles.productList, 'u-grid u-grid-cols-custom')}>
      {products.map((product, index) => (
        <ProductCard
          key={product.id}
          product={{ id, name, brand, url, image, fromPrice, listPrice, stock }}
          loading={index > 3 ? 'lazy' : 'eager'}   // First 4 images eager, rest lazy
          responsiveSizesSettings={`...breakpoints...`}
          stockTranslations={translations?.productList.availability}
        />
      ))}
    </div>
  );
};
```

**What makes this component excellent:**

- **Zero hooks, zero side effects** — pure `(props) → JSX`.
- **Handles its own empty state** — parent doesn't need to check.
- **Performance-aware** — `loading="lazy"` on images below the fold (index > 3).
- **Responsive images** — `responsiveSizesSettings` tells the browser the exact rendered size at each breakpoint so it fetches the optimal resolution.
- **Stable keys** — uses `product.id`, not array index.
- **Reusable in any context** — works inside both server components and client components because it has no `'use client'` directive or framework hooks.

#### ClientLoadMore — Progress + action button

```typescript
export function ClientLoadMore({
  currentPage, totalProducts, pageSize, hasNextPage, onLoadMore, isLoading, className,
}: ClientLoadMoreProps) {
  // Derived value calculated at render time (not stored in state)
  const viewedProducts = !hasNextPage ? totalProducts : currentPage * pageSize;

  if (totalProducts === 0) return null;    // Guard: render nothing for empty results

  return (
    <div className={classNames(styles.pagination, className)}>
      <p>Viewed {viewedProducts} of {totalProducts} products.</p>
      <ClientProgressBar progressPercent={(viewedProducts / totalProducts) * 100} />
      {hasNextPage && (
        <Button variant="secondary" onClick={onLoadMore} disabled={isLoading}>
          {isLoading ? 'Loading...' : 'Show next'}
        </Button>
      )}
    </div>
  );
}
```

**Key detail:** `viewedProducts` is a _derived value_ calculated during render — not stored in state. This follows Vercel rule `rerender-derived-state-no-effect`: derive state during render, not in effects.

---

## How the "Load More" Interaction Works End-to-End

```
                        USER CLICKS "SHOW NEXT"
                                │
                                ▼
┌──────────────────── ClientResults ─────────────────────────────┐
│                                                                 │
│  handleLoadMore()                                               │
│    1. setIsLoadingNext(true)        → spinner on button          │
│    2. setError(null)                → clear previous error       │
│    3. await loadMoreProducts(query, nextPage)                    │
│       └──→ SERVER ACTION executes on server                     │
│            ├─ fetch settings, translations, auth (parallel)     │
│            ├─ searchProducts(query, nextPage, token)            │
│            ├─ mapSearchResultServer(raw) → mapped products      │
│            └─ return { products[], hasNextPage, nextPage, ... } │
│    4. isServerActionError(result)?                               │
│       ├─ YES → setError(result.message)                         │
│       └─ NO  →                                                  │
│           setProducts(prev => [...prev, ...result.products])    │
│           setCurrentPage(nextPage)                               │
│           setNextPage(result.nextPage)                           │
│           setHasNextPage(result.hasNextPage)                     │
│    5. setIsLoadingNext(false)       → spinner off                │
│                                                                  │
│  useEffect [currentPage] fires:                                  │
│    → window.history.replaceState(null, '', '?page=3')            │
│    (URL updated without navigation)                              │
│                                                                  │
│  Re-render:                                                      │
│    <ProductGrid products={[page1 + page2 + page3]} />            │
│    <ClientLoadMore currentPage={3} hasNextPage={true/false} />   │
└──────────────────────────────────────────────────────────────────┘
```

---

## How the "Load Previous" Interaction Works

This activates when a user lands directly on `/search?page=3` via URL/bookmark:

```
User lands on ?page=3  →  Results.tsx fetches page 3
                       →  ClientResults initializes with:
                            previousPage = 2
                            hasPreviousPage = true

                        USER CLICKS "SHOW PREVIOUS"
                                │
                                ▼
  handleLoadPrevious()
    1. await loadPreviousProducts(query, previousPage=2)
    2. PREPEND: setProducts(prev => [...result.products, ...prev])
    3. setPreviousPage(1)     ← move cursor backward
    4. setHasPreviousPage(true)  ← page 1 still exists
    5. setCurrentPage(2)      ← URL now shows ?page=2

  User clicks again:
    1. await loadPreviousProducts(query, previousPage=1)
    2. PREPEND page 1 products
    3. setPreviousPage(0)
    4. setHasPreviousPage(false)  ← no more pages
    → ClientLoadPrev renders null (button disappears)
```

---

## Vercel Best Practices Applied

This section maps specific Vercel rules to how they're implemented in this data flow.

### CRITICAL Impact

| Rule                        | Where Applied                                                                    | How                                                                                                                                                                                     |
| --------------------------- | -------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `async-parallel`            | Results.tsx line 30, actions.ts line 25/72                                       | Independent fetches wrapped in `Promise.all()`. Search is sequential _because_ it depends on the auth token from the parallel batch.                                                    |
| `async-suspense-boundaries` | Parent (ProductList/index.tsx) wraps `<Results>` in `<Suspense key={queryHash}>` | The surrounding layout (toolbar, sidebar) renders immediately. Only the results section shows a skeleton while data loads. The `key` prop resets the boundary when query params change. |

### HIGH Impact

| Rule                       | Where Applied                     | How                                                                                                                                                                                                         |
| -------------------------- | --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `server-serialization`     | Results.tsx → ClientResults props | Only 6 props cross the server→client boundary: `products[]`, `query`, `currentPage`, `pageSize`, `initialHasNextPage`, `totalProducts`. Raw API response, translations, auth tokens — all stay server-side. |
| `server-parallel-fetching` | Results.tsx as async RSC          | The component itself is an async server component that fetches in parallel, rather than a parent passing fetched data down through multiple layers.                                                         |

### MEDIUM Impact

| Rule                               | Where Applied                                  | How                                                                                                                                                                  |
| ---------------------------------- | ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `rerender-functional-setstate`     | ClientResults line 120, 150                    | `setProducts(prev => [...prev, ...result.products])` — functional update prevents stale closures. The `useCallback` doesn't need `products` in its dependency array. |
| `rerender-derived-state-no-effect` | ClientLoadMore line 51                         | `viewedProducts` is computed during render from props, not stored in state or derived in a `useEffect`.                                                              |
| `rendering-conditional-render`     | ClientLoadMore line 67, ClientLoadPrev line 33 | Components return `null` early for empty/hidden states instead of wrapping in conditional containers.                                                                |

---

## Guidelines for Reproducing This Pattern

### Rule 1: Separate Fetch Boundary from State Boundary

The server component (`Results.tsx`) is a **fetch boundary** — it gets data and transforms it. The client component (`ClientResults`) is a **state boundary** — it manages interactive state. Never combine these into one component.

**In a non-SSR React app**, the same separation applies:

- A hook or wrapper component handles the initial `useQuery` fetch + mapping → produces `initialData`.
- A state component receives `initialData` and manages accumulated products, pagination cursors, and URL sync.

### Rule 2: Props at the Server→Client Boundary Should Be Minimal Scalars

Don't pass:

- ❌ Raw API responses
- ❌ Auth tokens or secrets
- ❌ Settings objects the client won't use
- ❌ Full pagination objects when the client only needs `hasNextPage`

Do pass:

- ✅ Pre-mapped domain arrays (`ProductItemResponse[]`)
- ✅ Pre-calculated booleans (`hasNextPage`)
- ✅ Scalar values (`pageSize`, `totalProducts`, `currentPage`)
- ✅ The query object (needed by client to call server actions)

### Rule 3: Server Actions Reuse the Same Pipeline

`loadMoreProducts()` uses the same `searchProducts()` → `mapSearchResultServer()` pipeline as `Results.tsx`. This guarantees:

- Consistent data shape between initial render and subsequent loads.
- No code duplication.
- Single place to fix mapping bugs.

### Rule 4: Discriminated Unions for Error Handling

Server actions **never throw** to the client. They return:

```typescript
type Response = SuccessResult | { error: true; message: string };
```

The client uses a type guard to branch. This is more predictable than try/catch and gives the type system full control over the error path.

### Rule 5: State Accumulates, Resets on Context Change

Two rules work together:

1. **Accumulate** — `setProducts(prev => [...prev, ...newProducts])` — the list grows.
2. **Reset** — `useEffect([initialProducts]) → setProducts(initialProducts)` — when the server re-renders with new data (filter/sort change), the accumulated list is replaced.

This combination prevents stale data while supporting the infinite-scroll UX.

### Rule 6: URL Is a Side Effect, Not a Trigger

```typescript
// ✅ Correct: state change → URL update (side effect)
useEffect(() => {
  history.replaceState(null, "", `?page=${currentPage}`);
}, [currentPage]);

// ❌ Wrong: URL change → data fetch (trigger)
// This would cause infinite loops or duplicate fetches
```

The URL reflects state for bookmarkability. It does **not** drive fetching. Initial page is parsed from URL in `page.tsx`; subsequent pages come from user interactions.

### Rule 7: Presentational Components Own Their Empty State

`ProductGrid` handles `products.length === 0` internally. `ClientLoadMore` handles `totalProducts === 0` internally. The parent (`ClientResults`) never checks these conditions — it just renders the children unconditionally.

### Rule 8: Loading States Are Per-Action, Not Global

```typescript
const [isLoadingNext, setIsLoadingNext] = useState(false);
const [isLoadingPrev, setIsLoadingPrev] = useState(false);
```

Not a single `isLoading` flag. This ensures that clicking "Load Previous" shows a spinner only on that button, not on the "Load More" button at the bottom.

---

## File-by-File Reference

| File                                     | Lines | Role                 | Inputs                               | Outputs                                                             |
| ---------------------------------------- | ----- | -------------------- | ------------------------------------ | ------------------------------------------------------------------- |
| `Results.tsx`                            | 57    | Server data fetcher  | `query`, `settings`, `currentPage`   | Renders `<ClientResults>` with mapped data                          |
| `actions.ts`                             | 105   | Server actions       | `query`, `pageNumber`                | `LoadMoreResponse \| LoadPreviousResponse`                          |
| `types.ts`                               | 30    | Type contracts       | —                                    | `LoadMoreResponse`, `LoadPreviousResponse`, `isServerActionError()` |
| `ClientResults/index.tsx`                | 189   | Client state manager | Server-rendered props                | Renders grid + pagination controls                                  |
| `ClientResults/ProductGrid.tsx`          | 61    | Pure renderer        | `products[]`, `translations`         | Grid of `<ProductCard>` components                                  |
| `ClientResults/ClientLoadMore/index.tsx` | 75    | Progress + button    | Pagination scalars + `onLoadMore`    | Progress bar + "Show next" button                                   |
| `ClientResults/ClientLoadPrev/index.tsx` | 45    | Button               | `hasPreviousPage` + `onLoadPrevious` | "Show previous" button or null                                      |
| `ResultsSkeleton.tsx`                    | 23    | Loading skeleton     | `count`                              | Grid of skeleton `<ProductCard>`                                    |
| `index.tsx` (barrel)                     | 2     | Re-exports           | —                                    | `Results`, `ResultsSkeleton`                                        |
