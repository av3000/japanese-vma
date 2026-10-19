---
title: Subscribe to External Stores With useSyncExternalStore
impact: MEDIUM
impactDescription: removes a post-mount render pass and prevents tearing
tags: client, useSyncExternalStore, subscriptions
---

## Subscribe to External Stores With useSyncExternalStore

For values that live outside React — browser APIs, a global event target, a third-party store — `useSyncExternalStore` is the correct hook. The hand-rolled `useState` + `useEffect` version has two problems the built-in hook solves:

1. It renders once with a placeholder value, then re-renders after the effect subscribes. Every consumer pays an extra pass on mount.
2. It can **tear**: with concurrent rendering, two components reading the same store mid-update can render different values in one commit.

**Incorrect (manual subscription):**

```tsx
function useMediaQuery(query: string) {
  const [matches, setMatches] = useState(false)

  useEffect(() => {
    const list = window.matchMedia(query)
    setMatches(list.matches)              // extra render on every mount

    const onChange = (e: MediaQueryListEvent) => setMatches(e.matches)
    list.addEventListener('change', onChange)
    return () => list.removeEventListener('change', onChange)
  }, [query])

  return matches
}
```

Every consumer renders `false` first and corrects itself a pass later — a visible flash when the value drives layout.

**Correct (useSyncExternalStore):**

```tsx
function useMediaQuery(query: string) {
  const subscribe = useCallback((onStoreChange: () => void) => {
    const list = window.matchMedia(query)
    list.addEventListener('change', onStoreChange)
    return () => list.removeEventListener('change', onStoreChange)
  }, [query])

  return useSyncExternalStore(
    subscribe,
    () => window.matchMedia(query).matches,
  )
}
```

The first client render already has the real value, and all consumers of the same query are guaranteed to see the same one in a given commit.

**`getSnapshot` must return a cached value.** Returning a fresh object or array each call makes React re-render forever, because the snapshot never compares equal to the previous one. Return primitives, or a reference the store itself holds and only replaces when the data actually changes.


`subscribe` must be stable — defined at module level or wrapped in `useCallback` — or React resubscribes on every render.

Related: `client-event-listeners` for sharing one listener across many subscribers; `rerender-derived-state` for subscribing to a boolean rather than a continuous value.

Reference: [Subscribing to an external store](https://react.dev/learn/you-might-not-need-an-effect#subscribing-to-an-external-store)

_Source: local addition based on react.dev, adapted for this Vite SPA; not in the upstream Vercel skill._
