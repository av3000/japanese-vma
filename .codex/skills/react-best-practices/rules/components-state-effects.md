# Components, State, And Effects

## Core Rule

Keep one authoritative value, derive everything else during render, and use effects only to synchronize with systems outside React.

## Component Boundaries

- Put static strings, numbers, arrays, and objects at module scope. Only move values there when they are truly static; values that depend on props or state belong in render.
- Split a meaningful JSX section into a component when it makes the parent easier to read or gives the section a focused prop API. Do not extract one-line wrappers mechanically.
- Use fragments when a wrapper has no semantic or layout purpose. Let the parent own placement, spacing, flex, and grid; reusable components may accept and merge `className` when needed.
- Give props explicit TypeScript types. Keep presentational components generic: pass calculated values in rather than embedding feature-specific rules.
- Prefer `children` for structural composition. Prefer semantic event props such as `onSelectPost(id)` or `onAddTodo()` to passing a raw state setter through a reusable component.

## State And Forms

- Type nullable state explicitly: `useState<Post | null>(null)`. Render a loading, empty, or error branch before accessing asynchronous data.
- Store an ID for a selected item and derive the item from its authoritative collection or cache. Compute totals, filtered data, and labels during render; use `useMemo` only when profiling shows the calculation is expensive.
- Put bookmarkable filters, tabs, sorting, pagination, and selected IDs in URL query parameters when sharing or refresh persistence matters.
- Use functional updates whenever the next value depends on the previous value, including timers, async callbacks, queued updates, and object updates.
- Preserve object fields and immutable references: `setForm((previous) => ({ ...previous, title }))`. A fresh object or array has a new identity and causes a render even when its contents look unchanged.
- A shared form handler can use input names that match form keys. Keep the name set controlled and typed rather than accepting arbitrary keys.

```tsx
type Form = { title: string; body: string }

function handleChange(event: React.ChangeEvent<HTMLInputElement>) {
  const field = event.currentTarget.name as keyof Form
  const value = event.currentTarget.value

  setForm((previous) => ({ ...previous, [field]: value }))
}
```

## Effects And Data

- Derive state in render, not by synchronizing a second state variable in an effect. Split effects when they synchronize different systems.
- Clean up event listeners, subscriptions, timers, and in-flight manual requests. Extract repeated stateful effect logic into a `use...` hook only when it is genuinely shared.
- Prefer the project's server-state owner (for example, TanStack Query, SWR, or a framework loader). In a server-rendered runtime, use its server data pattern where appropriate.
- If manual client fetching remains necessary, abort obsolete work and distinguish aborts from real errors:

```tsx
useEffect(() => {
  const controller = new AbortController()

  void fetchPost(postId, { signal: controller.signal })
    .then(setPost)
    .catch((error) => {
      if (error.name !== "AbortError") setError(error)
    })

  return () => controller.abort()
}, [postId])
```

## Performance Boundary

Use `useCallback` with `React.memo` only for a measured expensive child with stable inputs. For Vite SPA startup, server cache ownership, rerender, and browser-work problems, use the corresponding files in this directory rather than adding blanket memoization.
