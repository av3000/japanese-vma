# Conditional Next.js And Server Runtime Rules

Use this reference only when the target codebase actually uses Next.js, React Server Components, server actions, SSR streaming, or a comparable server-rendered React runtime.

## Keep Out Of Default Vite SPA Guidance

- `next/dynamic`
- server actions with `"use server"`
- `React.cache()` for per-request server deduplication
- RSC prop serialization and duplicate server-to-client payloads
- Next.js extended `fetch` request memoization
- `after()` for non-blocking server work
- hydration mismatch suppression as a routine SPA fix

## Conditional Rules

- Authenticate and authorize server actions inside the action, as public endpoints.
- Use `React.cache()` for repeated database, auth, file-system, or heavy async work within one server request.
- Minimize serialized data across RSC/client boundaries.
- Parallelize server fetches by starting independent promises early and composing components so one data dependency does not block the whole tree.
- Use framework-specific script/loading APIs only when that framework is present.

## Translation To Vite SPA

| Next.js assumption | Vite SPA default |
| --- | --- |
| `next/dynamic` | React Router lazy routes, `React.lazy`, dynamic `import()` |
| SWR default | TanStack Query if the app already uses it |
| server action auth | normal backend API auth and frontend mutation invalidation |
| RSC serialization | API response shape and client bundle boundaries |
| SSR hydration fixes | avoid app-wide client-only flicker; only handle hydration if SSR exists |
