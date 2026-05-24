# Rerender And State Rules

## Core Rule

First reduce the blast radius. Memoization is useful after ownership, component boundaries, and dependencies are already shaped correctly.

## Apply These Rules

- Profile the interaction before changing broad render behavior.
- Split Context by update frequency and consumer group: auth identity, auth loading, theme, websocket state, feature flags, settings.
- Memoize provider values when the shape is right, but do not use `useMemo` to hide an overloaded provider.
- Keep form drafts and high-frequency UI state local when possible.
- Do not define components inside components; React treats them as new component types and remounts them.
- Split combined `useMemo` or `useEffect` work when independent dependencies change at different times.
- Derive values during render instead of syncing derived state in effects.
- Use primitive effect dependencies where possible; avoid broad object dependencies that churn.
- Use functional `setState` to keep callbacks stable when they only need the previous value.
- Use `useDeferredValue` for expensive derived renders triggered by user input.
- Use `startTransition` for non-urgent state updates that should not block typing or direct manipulation.
- Use refs for transient values that do not affect rendering.

## Example

```tsx
const filteredProducts = useMemo(
  () => products.filter((product) => product.category === category),
  [products, category],
)

const sortedProducts = useMemo(
  () => filteredProducts.toSorted((a, b) => comparePrice(a, b, sortOrder)),
  [filteredProducts, sortOrder],
)
```

## Common Mistakes

- Wrapping cheap primitive expressions in `useMemo`.
- Passing fresh object literals through Context or into memoized children every render.
- Broadly applying `React.memo` before checking whether props or Context change every render anyway.
- Lifting server data into local state and then fighting stale derived values.
