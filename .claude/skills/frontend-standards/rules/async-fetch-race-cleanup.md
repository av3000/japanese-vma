---
title: Guard Fetch Effects Against Race Conditions
impact: HIGH
impactDescription: prevents stale responses overwriting fresh data
tags: async, fetching, useEffect, race-conditions, cleanup, abortcontroller
---

## Guard Fetch Effects Against Race Conditions

An effect that fetches on a changing dependency will have several requests in flight at once. Responses arrive in completion order, not request order, so a slow early response can land *after* a fast later one and overwrite it. The user sees results for a query they already moved on from.

Every fetch effect needs a cleanup function. Prefer a TanStack Query hook first (see `client-query-dedup`) — they handle this for you — but when you do write the effect by hand, the cleanup is not optional.

**Incorrect (last response wins, not last query):**

```tsx
function SearchResults({ query }: { query: string }) {
  const [results, setResults] = useState<Result[]>([])

  useEffect(() => {
    fetchResults(query).then(setResults)
  }, [query])

  // Type "re" then "react": if the "re" request resolves second,
  // the user sees results for "re" while the input reads "react".
}
```

**Correct (ignore stale responses):**

```tsx
function SearchResults({ query }: { query: string }) {
  const [results, setResults] = useState<Result[]>([])

  useEffect(() => {
    let ignore = false

    fetchResults(query).then(json => {
      if (!ignore) {
        setResults(json)
      }
    })

    return () => {
      ignore = true
    }
  }, [query])
}
```

**Better (also cancel the in-flight request):**

The ignore flag prevents the bad write but still lets the obsolete request finish, consuming a connection and server work. `AbortController` stops it at the network layer.

```tsx
function SearchResults({ query }: { query: string }) {
  const [results, setResults] = useState<Result[]>([])

  useEffect(() => {
    const controller = new AbortController()

    fetchResults(query, { signal: controller.signal })
      .then(setResults)
      .catch(error => {
        if (error.name !== 'AbortError') {
          throw error
        }
      })

    return () => controller.abort()
  }, [query])
}
```

Aborting is what makes a fast-typing user cost one request's worth of server load instead of one per keystroke. Always swallow `AbortError` — it is the expected outcome of cleanup, not a failure.

Reference: [Fetching data](https://react.dev/learn/you-might-not-need-an-effect#fetching-data)

_Source: local addition based on react.dev, adapted for this Vite SPA; not in the upstream Vercel skill._
