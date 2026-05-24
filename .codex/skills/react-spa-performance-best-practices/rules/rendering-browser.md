# Rendering And Browser Rules

## Core Rule

Browser work matters after startup, data ownership, and rerender boundaries are sane. Target long lists, synchronous browser APIs, layout reads/writes, and repeated hot-path computations.

## Apply These Rules

- Use `content-visibility` or virtualization for large offscreen sections and long lists.
- Batch DOM writes and layout reads; do not interleave style changes with `offsetWidth`, `getBoundingClientRect()`, or `getComputedStyle()`.
- Prefer CSS classes over imperative inline style mutation in effects.
- Deduplicate global event listeners; use passive listeners for scroll and touch events when you do not call `preventDefault()`.
- Cache synchronous storage reads (`localStorage`, `sessionStorage`, cookies) in hot paths, and invalidate on writes or cross-tab changes.
- Version persisted localStorage schema and keep stored data minimal.
- Use `defer` or `async` for non-module script tags that are not part of the Vite bundle.
- Use resource hints sparingly for known critical domains or assets; do not preconnect to every possible third party.
- Build `Map` or `Set` indexes for repeated lookups.
- Use immutable array methods such as `toSorted()` where supported, or copy before sorting.
- Avoid sorting to find min/max; use a single pass.
- Combine repeated array passes only in hot paths where readability remains acceptable.

## Example

```tsx
function useWindowEvent(type: string, handler: EventListener) {
  useEffect(() => {
    window.addEventListener(type, handler, { passive: true })
    return () => window.removeEventListener(type, handler)
  }, [type, handler])
}
```

## Common Mistakes

- Micro-optimizing array loops before measuring bundle, network, and render costs.
- Reading storage repeatedly during render.
- Using `sort()` on props or state arrays, which mutates data React expects to be immutable.
- Adding many preloads/preconnects that compete with the resources the current route actually needs.
