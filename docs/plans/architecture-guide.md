# Architectural Pattern Guide: Layered Data Flow with TanStack Query and Component Composition

> **Purpose:** A reference document extracted from the Blueprint project's ProductList feature. Describes the data flow, separation of concerns, component composition, and design patterns using **React + TypeScript + TanStack Query**. This guide assumes a client-side React application (no SSR) and focuses on reproducible patterns for paginated list features with mature data management.

---

## 1. Problem Statement

Build a paginated product list (or any entity list) that:

- Fetches data via **TanStack Query** with proper caching, error handling, and loading states.
- Allows **client-side "load more" / "load previous"** without full page reloads.
- Keeps the **URL in sync** with the user's current pagination state (bookmarkable/shareable).
- Maintains **strict separation** between data fetching, data transformation, state management, and presentation.
- Is **type-safe end-to-end** from API response to rendered component.

---

## 2. Layered Architecture Overview

The architecture is divided into **five distinct layers**, each with a single responsibility:

```
┌──────────────────────────────────────────────────────────┐
│  Layer 1: PAGE / ROUTE COMPONENT                         │
│  Parses URL → builds typed query → delegates to feature  │
├──────────────────────────────────────────────────────────┤
│  Layer 2: FEATURE ORCHESTRATOR                           │
│  Composes layout, loading states, error boundaries       │
├──────────────────────────────────────────────────────────┤
│  Layer 3: DATA MANAGER                                   │
│  Fetches via TanStack Query, manages accumulated state,  │
│  URL sync, pagination logic                              │
├──────────────────────────────────────────────────────────┤
│  Layer 4: PRESENTATIONAL COMPONENTS                      │
│  Pure rendering — receives data via props only            │
├──────────────────────────────────────────────────────────┤
│  Layer 5: API / DATA ACCESS                              │
│  HTTP clients, query builders, response mapping           │
└──────────────────────────────────────────────────────────┘
```

---

## 3. Detailed Layer Breakdown

### 3.1 Layer 1 — Page / Route Component

**Blueprint Reference:** `page.tsx`

**Responsibility:** Parse raw URL parameters into a strongly-typed query object and delegate rendering to the feature component.

**Key Patterns:**

- **URL → Domain Type parsing:** A dedicated `parseQuery(rawParams)` function converts raw string key-value pairs into a typed query object. This is the _only_ place that touches raw URL params.
- **Thin shell:** The page component contains no business logic — it parses, fetches config, and renders the feature component.

**Blueprint implementation (Next.js):**

```typescript
// page.tsx — Next.js server component
const query = parseProductListQuery(resolvedSearchParams, segmentationId);
const currentPage = parseCurrentPage(resolvedSearchParams);

return (
  <ProductList
    segmentationId={segmentationId}
    query={query}
    settings={searchSettings}
    currentPage={currentPage}
    currencyName={currencyName}
    cultureCode={cultureCode}
  />
);
```

**React SPA equivalent:**

```typescript
// SearchPage.tsx — Route component
import { useSearchParams } from 'react-router-dom';
import { parseProductListQuery, parseCurrentPage } from '@/api/product/parseProductListQuery';
import { useSettings } from '@/hooks/useSettings';
import { ProductList } from '@/components/features/ProductList';

export function SearchPage() {
  const [searchParams] = useSearchParams();
  const { data: settings } = useSettings(segmentationId);

  // Parse all search parameters into typed query object
  const query = parseProductListQuery(searchParams, segmentationId);
  const currentPage = parseCurrentPage(searchParams);

  return (
    <ProductList
      segmentationId={segmentationId}
      query={query}
      settings={settings?.searchSettings}
      currentPage={currentPage}
      currencyName={settings?.currencySettings.currencyName ?? ''}
      cultureCode={settings?.cultureCode ?? ''}
    />
  );
}
```

**Key takeaway:** The route component should:

1. Parse and validate raw input (URL params, query strings) into a typed domain object using a dedicated parser function.
2. Fetch any global/shared configuration needed (via TanStack Query hooks).
3. Pass the typed query + config to a feature component.
4. Contain **zero business logic**.

**Query parser (reusable as-is):**

```typescript
// parseProductListQuery.ts
export type RawSearchParams =
  | URLSearchParams
  | Record<string, string | string[] | undefined>;

export function parseProductListQuery(
  searchParams: RawSearchParams,
  segmentationId: number,
  productCategoryId?: string,
): ProductListPageQuery {
  const getString = (key: string): string | undefined => {
    if (searchParams instanceof URLSearchParams)
      return searchParams.get(key) ?? undefined;
    const value = searchParams[key];
    return Array.isArray(value) ? value[0] : value;
  };

  const getNumber = (key: string): number | undefined => {
    const value = getString(key);
    if (!value) return undefined;
    const num = parseFloat(value);
    return isNaN(num) ? undefined : num;
  };

  const filters = parseFiltersFromUrl(searchParams);

  return {
    segmentationId,
    productCategoryId,
    phrase: getString("phrase"),
    sortBy: getString("sortBy") || SEARCH_CONSTANTS.defaultSortBy,
    sortDirection:
      getString("sortDirection") || SEARCH_CONSTANTS.defaultSortDirection,
    pageSize: getNumber("pageSize"),
    minPrice: getNumber("minPrice"),
    maxPrice: getNumber("maxPrice"),
    filters,
  };
}

export function parseCurrentPage(searchParams: RawSearchParams): number {
  const value =
    searchParams instanceof URLSearchParams
      ? searchParams.get("page")
      : searchParams.page;
  const pageStr = Array.isArray(value) ? value[0] : value;
  if (!pageStr) return 1;
  const page = parseInt(pageStr, 10);
  return isNaN(page) || page < 1 ? 1 : page;
}
```

---

### 3.2 Layer 2 — Feature Orchestrator

**Blueprint Reference:** `ProductList/index.tsx`

**Responsibility:** Compose the full feature layout — header, sidebar, toolbar, results — with loading states and error boundaries.

**Key Patterns:**

- **Composition over inheritance:** The orchestrator composes independent sub-components (Header, CategoryMenu, ListToolbar, Results) rather than implementing a monolithic component.
- **Error isolation:** Each section has its own ErrorBoundary so a failure in the sidebar doesn't crash the results.
- **Loading state keys:** The key on the loading boundary is derived from query parameters, so React unmounts/remounts when the query changes — resetting loading states correctly.
- **Conditional rendering based on context:** Different headers render depending on whether the user is browsing a category or searching.

**Blueprint implementation:**

```typescript
// ProductList/index.tsx
export const ProductList: React.FC<ProductListProps> = ({
  segmentationId, query, settings, category, currentPage, currencyName, cultureCode,
}) => {
  return (
    <>
      {category ? (
        <HeaderWithCategory query={query} settings={settings} ... />
      ) : (
        <Suspense fallback={<HeaderWithSearchSkeleton />}>
          <ErrorBoundary fallback={<div />}>
            <HeaderWithSearch query={query} settings={settings} ... />
          </ErrorBoundary>
        </Suspense>
      )}

      <article className={classNames(styles.contentWrapper, 'u-container-lg')}>
        <aside className={styles.categoryMenu}>
          <Suspense fallback={<SideMenu isLoading />}>
            <ErrorBoundary fallback={<div />}>
              <CategoryMenu segmentationId={segmentationId} ... />
            </ErrorBoundary>
          </Suspense>
        </aside>

        <main className={styles.mainContent}>
          <ListToolbar query={query} category={category} ... />

          <section className={styles.productListWrapper}>
            <Suspense
              key={`${query.sortBy}-${query.sortDirection}-${query.phrase}-${currentPage}`}
              fallback={<ResultsSkeleton count={28} />}
            >
              <ErrorBoundary fallback={<div />}>
                <Results query={query} settings={settings} currentPage={currentPage} />
              </ErrorBoundary>
            </Suspense>
          </section>
        </main>
      </article>
    </>
  );
};
```

**React SPA equivalent:**

```typescript
// ProductList/index.tsx
export const ProductList: React.FC<ProductListProps> = ({
  segmentationId, query, settings, category, currentPage, currencyName, cultureCode,
}) => {
  return (
    <>
      {category ? (
        <HeaderWithCategory query={query} settings={settings} ... />
      ) : (
        <ErrorBoundary fallback={<div />}>
          <HeaderWithSearch query={query} settings={settings} ... />
        </ErrorBoundary>
      )}

      <article className={classNames(styles.contentWrapper, 'u-container-lg')}>
        <aside className={styles.categoryMenu}>
          <ErrorBoundary fallback={<div />}>
            <CategoryMenu segmentationId={segmentationId} ... />
          </ErrorBoundary>
        </aside>

        <main className={styles.mainContent}>
          <ListToolbar query={query} category={category} ... />

          <section className={styles.productListWrapper}>
            <ErrorBoundary fallback={<div />}>
              <Results
                query={query}
                settings={settings}
                currentPage={currentPage}
              />
            </ErrorBoundary>
          </section>
        </main>
      </article>
    </>
  );
};
```

**Key takeaway:**

- Build features by **composing single-purpose sub-components**, not by creating one large component.
- Wrap data-dependent sections in **ErrorBoundary** independently.
- Make conditional rendering decisions (layout, device, category vs search) at this level, not inside leaf components.

---

### 3.3 Layer 3 — Data Manager (TanStack Query + State)

In Blueprint's Next.js architecture, this responsibility is split across two components:

- **Results.tsx** (server component) — fetches initial data
- **ClientResults/index.tsx** (client component) — manages accumulated state

In a React SPA, these merge into a **single component** that uses TanStack Query for fetching and `useState` for accumulated state.

**Blueprint Reference:** `Results/Results.tsx` + `Results/ClientResults/index.tsx`

**Blueprint server-side data fetcher (Results.tsx):**

```typescript
// Results.tsx — Server Component (Next.js)
export async function Results({ query, settings, currentPage }: ResultsProps) {
  const [translations, authenticatedSettings] = await Promise.all([
    fetchTranslations(query.segmentationId),
    fetchAuthenticatedSettings(query.segmentationId),
  ]);

  const searchData = await searchProducts(query, 1, authenticatedSettings?.searchToken || '', settings);
  const searchResult = mapSearchResultServer(searchData, query, undefined, translations, settings);

  const totalProducts = searchResult.pagingInformation.totalNumberOfItems;
  const pageSize = searchResult.pagingInformation.pageSize;
  const totalPages = Math.ceil(totalProducts / pageSize);
  const hasNextPage = currentPage < totalPages;

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

**Blueprint client-side state manager (ClientResults/index.tsx):**

```typescript
// ClientResults/index.tsx — Client Component
'use client';

export const ClientResults: React.FC<ClientResultsProps> = ({
  initialProducts, query, currentPage: initialCurrentPage,
  pageSize, initialHasNextPage, totalProducts,
}) => {
  // Accumulated products from all loaded pages
  const [products, setProducts] = useState<ProductItemResponse[]>(initialProducts);
  const [currentPage, setCurrentPage] = useState(initialCurrentPage);
  const [nextPage, setNextPage] = useState(initialCurrentPage + 1);
  const [hasNextPage, setHasNextPage] = useState(initialHasNextPage);
  const [previousPage, setPreviousPage] = useState(initialCurrentPage - 1);
  const [hasPreviousPage, setHasPreviousPage] = useState(initialCurrentPage > 1);
  const [isLoadingNext, setIsLoadingNext] = useState(false);
  const [isLoadingPrev, setIsLoadingPrev] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const pathname = usePathname();
  const searchParams = useSearchParams();

  // Reset state when props change (sorting, filtering, search phrase)
  useEffect(() => {
    setProducts(initialProducts);
    setCurrentPage(initialCurrentPage);
    setNextPage(initialCurrentPage + 1);
    setHasNextPage(initialHasNextPage);
    setPreviousPage(initialCurrentPage - 1);
    setHasPreviousPage(initialCurrentPage > 1);
  }, [initialProducts, initialCurrentPage, initialHasNextPage]);

  // URL sync
  useEffect(() => {
    const currentUrlPage = searchParams.get('page');
    const currentUrlPageNum = currentUrlPage ? parseInt(currentUrlPage, 10) : 1;
    if (currentPage !== currentUrlPageNum) {
      const newParams = new URLSearchParams(searchParams);
      if (currentPage === 1) newParams.delete('page');
      else newParams.set('page', currentPage.toString());
      const newUrl = newParams.toString() ? `${pathname}?${newParams.toString()}` : pathname;
      window.history.replaceState(null, '', newUrl);
    }
  }, [currentPage, pathname, searchParams]);

  const handleLoadMore = useCallback(async () => {
    setIsLoadingNext(true);
    setError(null);
    try {
      const result = await loadMoreProducts(query, nextPage);
      if (isServerActionError(result)) { setError(result.message); return; }
      setProducts((prev) => [...prev, ...result.products]);
      setCurrentPage(nextPage);
      setNextPage(result.nextPage);
      setHasNextPage(result.hasNextPage);
    } catch {
      setError('An unexpected error occurred. Please try again.');
    } finally {
      setIsLoadingNext(false);
    }
  }, [query, nextPage]);

  // ... handleLoadPrevious similar pattern

  return (
    <>
      {error && <div role="alert">{error}</div>}
      <ClientLoadPrev hasPreviousPage={hasPreviousPage} onLoadPrevious={handleLoadPrevious} isLoading={isLoadingPrev} />
      <ProductGrid products={products} translations={translations} />
      <ClientLoadMore currentPage={currentPage} totalProducts={totalProducts} pageSize={pageSize}
        hasNextPage={hasNextPage} onLoadMore={handleLoadMore} isLoading={isLoadingNext} />
    </>
  );
};
```

**React SPA equivalent (merged into one component with TanStack Query):**

```typescript
// Results/Results.tsx — Combined data fetcher + state manager
import { useQuery, useMutation } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { useCallback, useEffect, useState } from 'react';
import { searchProductsClient } from '@/api/product/search';
import { mapSearchResult } from '@/api/product/mapping';
import type { ProductListPageQuery } from '@/api/product/search';
import type { ProductItemResponse } from '@/api/product/types';
import { isApiError } from './types';
import { ClientLoadMore } from './ClientResults/ClientLoadMore';
import { ClientLoadPrev } from './ClientResults/ClientLoadPrev';
import { ProductGrid } from './ClientResults/ProductGrid';
import { ResultsSkeleton } from './ResultsSkeleton';

interface ResultsProps {
  query: ProductListPageQuery;
  settings?: SearchSettingsResponse;
  currentPage: number;
}

export function Results({ query, settings, currentPage: initialCurrentPage }: ResultsProps) {
  const [searchParams, setSearchParams] = useSearchParams();

  // ── TanStack Query: initial data fetch ──
  const queryKey = ['products', query.segmentationId, query.phrase, query.sortBy,
    query.sortDirection, query.productCategoryId, JSON.stringify(query.filters)];

  const { data: initialData, isLoading, error: fetchError } = useQuery({
    queryKey,
    queryFn: async () => {
      const rawData = await searchProductsClient(query, initialCurrentPage, authToken, settings);
      return mapSearchResult(rawData, query, undefined, translations, settings);
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  // ── Accumulated state ──
  const [products, setProducts] = useState<ProductItemResponse[]>([]);
  const [currentPage, setCurrentPage] = useState(initialCurrentPage);
  const [nextPage, setNextPage] = useState(initialCurrentPage + 1);
  const [hasNextPage, setHasNextPage] = useState(false);
  const [previousPage, setPreviousPage] = useState(initialCurrentPage - 1);
  const [hasPreviousPage, setHasPreviousPage] = useState(initialCurrentPage > 1);
  const [error, setError] = useState<string | null>(null);

  // ── Reset state when initial data changes (filters/sort changed) ──
  useEffect(() => {
    if (!initialData) return;
    const { products: initialProducts, pagingInformation } = initialData;
    const totalPages = Math.ceil(pagingInformation.totalNumberOfItems / pagingInformation.pageSize);

    setProducts(initialProducts);
    setCurrentPage(initialCurrentPage);
    setNextPage(initialCurrentPage + 1);
    setHasNextPage(initialCurrentPage < totalPages);
    setPreviousPage(initialCurrentPage - 1);
    setHasPreviousPage(initialCurrentPage > 1);
  }, [initialData, initialCurrentPage]);

  // ── URL sync ──
  useEffect(() => {
    const currentUrlPage = parseInt(searchParams.get('page') ?? '1', 10);
    if (currentPage !== currentUrlPage) {
      const newParams = new URLSearchParams(searchParams);
      if (currentPage === 1) newParams.delete('page');
      else newParams.set('page', currentPage.toString());
      window.history.replaceState(null, '', `?${newParams.toString()}`);
    }
  }, [currentPage, searchParams]);

  // ── Load more mutation ──
  const loadMoreMutation = useMutation({
    mutationFn: async (page: number) => {
      const rawData = await searchProductsClient(query, page, authToken, settings);
      return mapSearchResult(rawData, query, undefined, translations, settings);
    },
    onSuccess: (data) => {
      setProducts((prev) => [...prev, ...data.products]);
      const totalPages = Math.ceil(data.pagingInformation.totalNumberOfItems / data.pagingInformation.pageSize);
      setCurrentPage(nextPage);
      setNextPage(nextPage + 1);
      setHasNextPage(nextPage < totalPages);
    },
    onError: () => {
      setError('Failed to load more products. Please try again.');
    },
  });

  // ── Load previous mutation ──
  const loadPrevMutation = useMutation({
    mutationFn: async (page: number) => {
      const rawData = await searchProductsClient(query, page, authToken, settings);
      return mapSearchResult(rawData, query, undefined, translations, settings);
    },
    onSuccess: (data) => {
      setProducts((prev) => [...data.products, ...prev]);
      setPreviousPage(previousPage - 1);
      setHasPreviousPage(previousPage > 1);
      setCurrentPage(previousPage);
    },
    onError: () => {
      setError('Failed to load previous products. Please try again.');
    },
  });

  const handleLoadMore = useCallback(() => {
    setError(null);
    loadMoreMutation.mutate(nextPage);
  }, [nextPage, loadMoreMutation]);

  const handleLoadPrevious = useCallback(() => {
    setError(null);
    loadPrevMutation.mutate(previousPage);
  }, [previousPage, loadPrevMutation]);

  // ── Loading state ──
  if (isLoading) return <ResultsSkeleton count={28} />;
  if (fetchError) return <div role="alert">Failed to load products.</div>;
  if (!initialData) return null;

  return (
    <>
      {error && <div role="alert" style={{ color: 'var(--color-error, #dc2626)' }}>{error}</div>}
      <ClientLoadPrev
        hasPreviousPage={hasPreviousPage}
        onLoadPrevious={handleLoadPrevious}
        isLoading={loadPrevMutation.isPending}
      />
      <ProductGrid products={products} translations={translations} />
      <ClientLoadMore
        currentPage={currentPage}
        totalProducts={initialData.pagingInformation.totalNumberOfItems}
        pageSize={initialData.pagingInformation.pageSize}
        hasNextPage={hasNextPage}
        onLoadMore={handleLoadMore}
        isLoading={loadMoreMutation.isPending}
      />
    </>
  );
}
```

**Key patterns preserved from Blueprint:**

1. **TanStack Query for initial fetch:**
   - `useQuery` handles caching, background refetching, and loading/error states.
   - The query key is derived from query parameters — when filters/sort change, TanStack Query refetches automatically.
   - Stale time prevents unnecessary re-fetches.

2. **`useMutation` for load more/previous:**
   - Mutations handle the "load more" action — they aren't cached queries, they're one-off actions that modify accumulated state.
   - `onSuccess` / `onError` callbacks keep state management clean.
   - `isPending` provides granular loading states per mutation.

3. **Accumulated state with reset:**
   - `useEffect` watching `initialData` resets all state when filters/sorting change.
   - Products array grows via append (load more) or prepend (load previous).

4. **URL sync as side effect:**
   - `useEffect` on `currentPage` updates the URL via `history.replaceState` — no navigation, no re-render.
   - Page 1 removes the `?page=` param for clean URLs.

---

### 3.4 Layer 4 — Presentational Components

**Blueprint Reference:** `ProductGrid.tsx`, `ClientLoadMore/index.tsx`, `ClientLoadPrev/index.tsx`, `ResultsSkeleton.tsx`

**Responsibility:** Pure rendering. Receive data and callbacks via props. No data fetching, no state management, no side effects.

These components are **identical between Blueprint and a React SPA** — they have no framework-specific dependencies.

**ProductGrid — Pure presentational component:**

```typescript
// ProductGrid.tsx
export interface ProductGridProps {
  products: ProductItemResponse[];
  translations?: TranslationsWebsite;
}

export const ProductGrid: React.FC<ProductGridProps> = ({ products, translations }) => {
  if (!products.length) {
    return <p>{translations?.shared.noProductsFound ?? 'No products found.'}</p>;
  }

  return (
    <div className={classNames(styles.productList, 'u-grid u-grid-cols-custom')}>
      {products.map((product, index) => (
        <ProductCard
          key={product.id}
          product={{
            id: product.id,
            name: product.name,
            brand: product.brand,
            url: product.url,
            image: product.image,
            fromPrice: product.fromPrice,
            listPrice: product.listPrice,
            stock: product.stock,
          }}
          loading={index > 3 ? 'lazy' : 'eager'}
          responsiveSizesSettings={`
            (min-width: 1480px) 280px,
            (min-width: 1136px) 19vw,
            (min-width: 856px) 25vw,
            (min-width: 528px) 33vw,
            (min-width: 360px) 50vw,
            100vw
          `}
          stockTranslations={translations?.productList.availability}
        />
      ))}
    </div>
  );
};
```

**ClientLoadMore — Pagination progress + button:**

```typescript
// ClientLoadMore/index.tsx
export interface ClientLoadMoreProps {
  currentPage: number;
  totalProducts: number;
  pageSize: number;
  hasNextPage: boolean;
  onLoadMore: () => void;
  isLoading?: boolean;
  className?: string;
}

export function ClientLoadMore({
  currentPage, totalProducts, pageSize, hasNextPage, onLoadMore, isLoading = false, className,
}: ClientLoadMoreProps) {
  const viewedProducts = !hasNextPage ? totalProducts : currentPage * pageSize;

  if (totalProducts === 0) return null;

  return (
    <div className={classNames(styles.pagination, className)}>
      <p>You have viewed {viewedProducts} of {totalProducts} products.</p>
      <ProgressBar progressPercent={(viewedProducts / totalProducts) * 100} />
      {hasNextPage && (
        <Button variant="secondary" onClick={onLoadMore} disabled={isLoading}>
          {isLoading ? 'Loading...' : 'Show next'}
        </Button>
      )}
    </div>
  );
}
```

**ClientLoadPrev — "Load previous" button:**

```typescript
// ClientLoadPrev/index.tsx
export interface ClientLoadPrevProps {
  hasPreviousPage: boolean;
  onLoadPrevious: () => void;
  isLoading?: boolean;
  className?: string;
}

export function ClientLoadPrev({
  hasPreviousPage, onLoadPrevious, isLoading = false, className,
}: ClientLoadPrevProps) {
  if (!hasPreviousPage) return null;

  return (
    <div className={classNames(styles.paginationPrevious, className)}>
      <Button variant="secondary" onClick={onLoadPrevious} disabled={isLoading}>
        {isLoading ? 'Loading...' : 'Show previous'}
      </Button>
    </div>
  );
}
```

**ResultsSkeleton — Loading placeholder:**

```typescript
// ResultsSkeleton.tsx
export function ResultsSkeleton({ count = 12 }: { count?: number }) {
  return (
    <div className={classNames(styles.productList, 'u-grid u-grid-cols-custom')}>
      {Array.from({ length: count }).map((_, index) => (
        <ProductCard key={index} isSkeleton />
      ))}
    </div>
  );
}
```

**Key takeaways:**

- **Presentational components must be pure.** Props in, JSX out. No hooks, no fetching, no side effects.
- **Handle empty states explicitly** — don't let the parent worry about it.
- **Loading skeletons should mirror the real component's layout** (same CSS classes) for smooth transitions.
- **Share design system components** (ProductCard, Button, ProgressBar) — don't rebuild primitives per feature.
- **Performance hints belong in the renderer** — the component rendering images decides lazy vs. eager based on position.

---

### 3.5 Layer 5 — API / Data Access

**Blueprint Reference:** `api/product/search.ts`, `api/product/mapping.ts`, `api/product/types.ts`

**Responsibility:** HTTP client configuration, request building, response mapping, domain type definitions.

**Key Patterns:**

1. **Domain types defined independently:**

```typescript
// types.ts — Domain types, independent of any API SDK
export interface ProductItemResponse {
  id: string;
  name: string;
  brand: string;
  url?: string | null;
  image: ImageResponse;
  fromPrice: number;
  listPrice: number;
  stock: number;
}

export interface ProductListPageQuery {
  segmentationId: number;
  productCategoryId?: string;
  phrase?: string;
  minPrice?: number;
  maxPrice?: number;
  sortBy?: string;
  sortDirection?: string;
  pageSize?: number;
  filters: Filter[];
}

export interface PagingInformation {
  currentPage: number;
  pageSize: number;
  totalNumberOfItems: number;
}

export interface ProductSearchResult {
  products: ProductItemResponse[];
  filters: Filter[];
  currentSorting?: SortingOption;
  pagingInformation: PagingInformation;
  sorting: SortingOption[];
  priceSliderValues: PriceSliderValues;
  phrase?: string | null;
  didYouMean?: string | null;
}
```

2. **Dedicated mapping functions (pure, testable):**

```typescript
// mapping.ts — Pure functions: raw API type → domain type

export const mapProductItem = (p: SearchProductWithSkusModel): ProductItemResponse => {
  const productAttributes = mapProductAttributes(p.metadata);
  const mainImage = p.media?.find((m) => m.type === 'Image');
  const image = mapMediaModel(mainImage, p.name);

  let fromPrice: number | undefined = undefined;
  let listPrice: number | undefined = undefined;
  for (const s of p.skus || []) {
    if (!fromPrice || (s.price?.price && s.price.price < fromPrice)) {
      fromPrice = s.price?.price;
      listPrice = s.price?.listPrice;
    }
  }

  return {
    id: p.id || '',
    name: p.name || p.id || '',
    brand: productAttributes.brand,
    url: productAttributes.url,
    image: image,
    fromPrice: mapPrice(fromPrice),
    listPrice: mapPrice(listPrice),
    stock: mapStockStatus(productAttributes.stockStatus),
  };
};

export const mapSearchResult = (
  data: UnifiedSearchResultModel,
  query: ProductListPageQuery,
  ...
): ProductSearchResult => {
  return {
    products: data.products?.map(mapProductItem) || [],
    phrase: data.usedPhrase,
    filters: mapFacets(data.facets, settings) || [],
    pagingInformation: {
      currentPage: (data.productOffset || 0) / (query.pageSize || defaultPageSize) + 1,
      pageSize: query.pageSize || defaultPageSize,
      totalNumberOfItems: data.totalProducts || 0,
    },
    // ... other mapped fields
  };
};
```

3. **Builder pattern for API requests:**

```typescript
// searchRequestBuilder.ts
export class SearchRequestBuilder {
  static fromQuery(
    query: ProductListPageQuery,
    token: string,
    settings?: SearchSettingsResponse,
  ) {
    return new SearchRequestBuilder(query, token, settings);
  }

  withPagination(page: number, pageSize?: number): this {
    this.page = page;
    this.pageSize = pageSize;
    return this;
  }

  build(): SearchRequest {
    // Constructs the full API request object
  }
}
```

4. **API client function:**

```typescript
// search.ts — Client-side search function
export async function searchProductsClient(
  query: ProductListPageQuery,
  page: number,
  searchToken: string,
  settings: SearchSettingsResponse,
): Promise<UnifiedSearchResultModel> {
  const request = SearchRequestBuilder.fromQuery(query, searchToken, settings)
    .withPagination(page, query.pageSize)
    .build();

  const searchClient = new SearchClient({ baseUrl: settings.searchApiHost });
  const result = await searchClient.search.search(request);
  return result.data;
}
```

**Key takeaways:**

- **Define your domain types independently** of any external API types. Map at the boundary.
- **Use a builder pattern** for complex API requests with many optional parameters.
- **Mapping functions must be pure** — no side effects, no external dependencies. They are trivially testable.
- **Centralize URL parsing** in dedicated utility functions with proper validation and defaults.

---

## 4. Error Handling Pattern: Discriminated Union Responses

**Blueprint Reference:** `Results/types.ts`, `Results/actions.ts`

In Blueprint, server actions return discriminated unions. In a React SPA with TanStack Query, this pattern is still valuable for API functions that need to return structured errors without throwing.

**Type definitions:**

```typescript
// types.ts
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

export type LoadMoreResponse = LoadMoreResult | ServerActionError;

// Type guard for safe pattern matching
export function isServerActionError(
  response: LoadMoreResponse,
): response is ServerActionError {
  return "error" in response && response.error;
}
```

**React SPA approach:**

With TanStack Query, errors are typically handled via the `error` state from `useQuery` / `useMutation`. However, discriminated unions remain useful when:

- The API returns domain-level errors (e.g., "search index unavailable") alongside HTTP 200 responses.
- You want to distinguish between network errors and business logic errors.
- Multiple error types need different UI treatment.

```typescript
// In the mutation's onSuccess, check for business-logic errors:
const loadMoreMutation = useMutation({
  mutationFn: async (page: number) => {
    const response = await fetchProducts(query, page);
    if (isApiError(response)) {
      throw new BusinessError(response.message); // Let TanStack Query handle it
    }
    return mapSearchResult(response.data, query);
  },
  onError: (error) => {
    if (error instanceof BusinessError) {
      setError(error.message); // User-friendly message
    } else {
      setError("An unexpected error occurred. Please try again.");
    }
  },
});
```

**Key takeaways:**

- **Return discriminated unions** from API functions when the server can return structured errors.
- **Provide a type guard** function for safe pattern matching.
- **Never expose raw errors to the user.** Log details, show friendly messages.
- With TanStack Query, you can **throw business errors inside `mutationFn`** so they're caught by `onError`.

---

## 5. File Structure Pattern

```
feature/
├── index.tsx                    # Feature orchestrator (composes all sub-components)
├── Feature.module.scss          # Orchestrator styles
├── consts.ts                    # Shared constants (breakpoints, defaults)
│
├── SubFeatureA/                 # Sub-feature folder (colocated)
│   ├── index.tsx                # Barrel export
│   └── SubFeatureA.module.scss  # Scoped styles
│
├── Results/                     # Data-fetching + state boundary
│   ├── index.tsx                # Barrel: exports Results + ResultsSkeleton
│   ├── Results.tsx              # TanStack Query hooks + accumulated state
│   ├── ResultsSkeleton.tsx      # Loading skeleton matching real layout
│   ├── Results.module.scss      # Shared grid styles
│   ├── types.ts                 # Response types + type guards
│   │
│   └── ClientResults/           # Presentational sub-components
│       ├── ProductGrid.tsx      # Pure presentational grid
│       ├── ClientLoadMore/      # Pagination sub-component
│       │   ├── index.tsx
│       │   └── ClientLoadMore.module.scss
│       └── ClientLoadPrev/      # Pagination sub-component
│           ├── index.tsx
│           └── ClientLoadPrev.module.scss
│
├── header/                      # Header variants
├── filters/                     # Filter components
├── ListToolbar/                 # Toolbar (sort, filter controls)
└── CategoryMenu/                # Category navigation
```

**Key structural rules:**

1. **Barrel exports** (`index.tsx`) at each folder level — consumers import from the folder, not specific files.
2. **Colocate styles, types, and sub-components** with the components that use them.
3. **Skeleton components live alongside their real counterparts** (e.g., `Results.tsx` + `ResultsSkeleton.tsx`).
4. **Presentational components in a sub-folder** — `ClientResults/` contains pure components that receive data via props.

---

## 6. Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│ USER REQUEST                                                         │
│ URL: /search?phrase=shoes&sortBy=Price&page=2                        │
└──────────────┬──────────────────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ LAYER 1: Route Component             │
│                                      │
│ const [searchParams] = useSearchParams()
│ parseProductListQuery(searchParams)  │
│   → { segmentationId, phrase,        │
│       sortBy, sortDirection,         │
│       filters[], pageSize }          │
│                                      │
│ parseCurrentPage(searchParams) → 2   │
│                                      │
│ useSettings(segmentationId)          │
│   → { searchSettings, currency }     │
└──────────────┬───────────────────────┘
               │ query + settings + currentPage
               ▼
┌──────────────────────────────────────┐
│ LAYER 2: Feature Orchestrator        │
│                                      │
│ Composes:                            │
│  • Header (Search or Category)       │
│  • Sidebar (CategoryMenu)            │
│  • Toolbar (sort, filters)           │
│  • Results (ErrorBoundary-wrapped)   │
└──────────────┬───────────────────────┘
               │ query + settings + currentPage
               ▼
┌──────────────────────────────────────────────────────────┐
│ LAYER 3: Results (TanStack Query + State)                │
│                                                          │
│ useQuery(['products', ...queryKey])                       │
│   → searchProductsClient(query, page, token)             │
│   → mapSearchResult(rawData)                             │
│   → { products[], pagingInformation }                    │
│                                                          │
│ State:                                                   │
│   products[] ← initialized from useQuery data            │
│   currentPage, nextPage, previousPage                    │
│   hasNextPage, hasPreviousPage                           │
│                                                          │
│ useMutation → loadMoreMutation                           │
│   onSuccess: append products, update pagination          │
│                                                          │
│ useMutation → loadPrevMutation                           │
│   onSuccess: prepend products, update pagination         │
│                                                          │
│ useEffect → sync currentPage to URL                      │
│ useEffect → reset state when query data changes          │
└──────────────┬───────────────────────────────────────────┘
               │ products[], callbacks, loading/error states
               ▼
┌──────────────────────────────────────────────────────────┐
│ LAYER 4: Presentational Components                       │
│                                                          │
│ <ClientLoadPrev                                          │
│   hasPreviousPage, onLoadPrevious, isLoading />          │
│                                                          │
│ <ProductGrid                                             │
│   products={products}                                    │
│   translations={translations} />                         │
│     └─ maps to <ProductCard /> per item                  │
│                                                          │
│ <ClientLoadMore                                          │
│   currentPage, totalProducts, pageSize,                  │
│   hasNextPage, onLoadMore, isLoading />                  │
└──────────────────────────────────────────────────────────┘
```

---

## 7. Design Patterns Summary

### Pattern 1: TanStack Query for Initial Fetch + Mutations for Actions

> Use `useQuery` for the initial page load (with caching), `useMutation` for load-more / load-previous actions.

- `useQuery` handles caching, background refetch, and loading/error states automatically.
- Query key is derived from search parameters — changing filters/sorting triggers a refetch.
- `useMutation` is used for one-off actions that modify accumulated state.
- `isPending` from mutations provides granular loading states.

### Pattern 2: Accumulated State with Reset

> Client accumulates data across user actions; resets when upstream data changes.

- Products array grows as user loads more/previous pages.
- When filters/sorting change, `useQuery` refetches → new data in `initialData` → `useEffect` resets all state.
- This avoids stale data while preserving the growing-list UX.

### Pattern 3: Discriminated Union Responses

> API functions return `SuccessResult | ErrorResult` with type guard.

```typescript
type Response = SuccessData | { error: true; message: string };
function isError(r: Response): r is ErrorType {
  return "error" in r && r.error;
}
```

- Enables safe pattern matching for structured errors.
- Distinguishes between network errors (thrown) and business errors (returned).

### Pattern 4: Layered Mapping Boundary

> Raw API types never cross component boundaries. Map at the data-access edge.

- External SDK returns `SearchProductWithSkusModel`.
- `mapProductItem()` converts to `ProductItemResponse` (your domain type).
- Every downstream component works with `ProductItemResponse`.
- If the API changes, only the mapping function changes.

### Pattern 5: Colocated Concern Boundaries

> Each folder represents a concern boundary: data management vs. presentation.

- `Results/` = data fetching + state management.
- `Results/ClientResults/` = pure presentational components.
- Types and utilities are colocated with the components that use them.
- Barrel exports (`index.tsx`) provide clean import paths.

### Pattern 6: Parallel Data Fetching

> Fetch independent data concurrently, not sequentially.

```typescript
const [translations, authSettings] = await Promise.all([
  fetchTranslations(id),
  fetchAuthSettings(id),
]);
```

- Reduces total latency to the slowest single fetch.
- In React SPA, use multiple `useQuery` hooks — TanStack Query fetches them in parallel automatically.

### Pattern 7: URL Sync as Side Effect

> URL updates are a side effect of state changes, not a trigger for data fetching.

- `useEffect` on `currentPage` updates the URL via `history.replaceState`.
- No navigation, no re-render — just a URL update for bookmarkability.
- Page 1 removes the `?page=` param for clean URLs.

### Pattern 8: Presentational Component Reusability

> Pure components that work in any context.

- `ProductGrid` has no hooks, no API calls, no framework-specific dependencies.
- Its contract is: `products[] → rendered grid`. Nothing more.
- Can be tested in isolation with mock data.

---

## 8. TanStack Query Integration Guide

### Query Key Strategy

Derive query keys from the typed query object so TanStack Query automatically refetches when parameters change:

```typescript
const queryKey = [
  "products",
  query.segmentationId,
  query.phrase,
  query.sortBy,
  query.sortDirection,
  query.productCategoryId,
  JSON.stringify(query.filters),
];
```

### Generated Query Hooks

Blueprint uses **generated TanStack Query hooks** (e.g., `useGetTranslations`) from OpenAPI specs. In a React SPA:

```typescript
// Generated hook example (from OpenAPI codegen)
export function useGetTranslations(segmentationId: number) {
  return useQuery({
    queryKey: ["translations", segmentationId],
    queryFn: () => translationsApi.getTranslations(segmentationId),
  });
}
```

Use codegen tool `orval` to generate these hooks from your API specs.

### Caching Strategy

- **Initial page data:** `staleTime: 5 * 60 * 1000` (5 min) — product listings don't change frequently.
- **Translations:** `staleTime: Infinity` — translations rarely change during a session.
- **Settings:** `staleTime: 30 * 60 * 1000` (30 min) — settings change infrequently.

---

## 9. Checklist for Implementing This Pattern

### Developer Checklist

- [ ] **Query Parser:** Create a function that converts raw URL params → typed query object with validation and defaults.
- [ ] **Domain Types:** Define your entity types (`ProductItemResponse` equivalent) independent of any API/SDK types.
- [ ] **Mapping Layer:** Create pure mapping functions: raw API type → domain type. One for collections, one for individual items.
- [ ] **API Client:** Build a fetch function using a builder pattern for complex queries.
- [ ] **Results Component:** Create a component that uses `useQuery` for initial fetch, `useMutation` for load-more, and `useState` for accumulated products.
- [ ] **Error Contract:** Define discriminated union response types (`SuccessResult | ErrorResult`) with type guard functions.
- [ ] **Presentational Grid:** Create a pure component that renders items from props with empty state handling.
- [ ] **Skeleton:** Create a loading skeleton that matches the real component's grid layout.
- [ ] **Load More / Load Previous:** Create presentational components for pagination controls with progress indicators.
- [ ] **Feature Orchestrator:** Compose all pieces with independent error boundaries per section.
- [ ] **File Structure:** Organize with barrel exports, colocated styles and types, data/presentation folder separation.
- [ ] **URL Sync:** Add `useEffect` to sync pagination state to URL via `history.replaceState`.

### User Checklist

- [ ] Verify initial load shows products (TanStack Query cache hit or fresh fetch).
- [ ] Verify "Load More" appends products without losing existing ones.
- [ ] Verify "Load Previous" prepends products (when landing on page > 1).
- [ ] Confirm URL updates when loading more pages and is bookmarkable.
- [ ] Confirm that changing filters/sorting resets the product list.
- [ ] Verify error states show user-friendly messages.
- [ ] Check that loading skeletons match the real grid layout.
- [ ] Verify TanStack Query devtools show proper cache behavior.
