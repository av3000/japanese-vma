---
title: Memoize Context Values and Split Contexts
impact: MEDIUM
impactDescription: prevents re-rendering every consumer on each provider render
tags: rerender, context, useMemo, useCallback, providers
---

## Memoize Context Values and Split Contexts

A context value written inline as an object or function literal gets a new identity on every provider render. Every component calling `useContext` for that context re-renders, even when the data it reads did not change. Wrap the value in `useMemo` and the functions in `useCallback`.

Providers usually sit near the root of the tree, so an unmemoized value turns any root-level state change into a full-subtree re-render.

**Incorrect (new object identity every render):**

```tsx
function AuthProvider({ children }: { children: React.ReactNode }) {
  const [currentUser, setCurrentUser] = useState<User | null>(null)

  function login(response: LoginResponse) {
    storeCredentials(response.credentials)
    setCurrentUser(response.user)
  }

  // New `{ currentUser, login }` object on every render of AuthProvider,
  // so every useContext(AuthContext) consumer re-renders too.
  return (
    <AuthContext value={{ currentUser, login }}>
      {children}
    </AuthContext>
  )
}
```

**Correct (stable identity):**

```tsx
function AuthProvider({ children }: { children: React.ReactNode }) {
  const [currentUser, setCurrentUser] = useState<User | null>(null)

  const login = useCallback((response: LoginResponse) => {
    storeCredentials(response.credentials)
    setCurrentUser(response.user)
  }, [])

  const value = useMemo(() => ({ currentUser, login }), [currentUser, login])

  return (
    <AuthContext value={value}>
      {children}
    </AuthContext>
  )
}
```

**Better for mixed read/write consumers: split the context.**

Components that only dispatch never need to re-render when the state changes. Splitting state from actions removes them from the re-render set entirely, and the actions context becomes a permanently stable value.

```tsx
const AuthStateContext = createContext<User | null>(null)
const AuthActionsContext = createContext<AuthActions | null>(null)

function AuthProvider({ children }: { children: React.ReactNode }) {
  const [currentUser, setCurrentUser] = useState<User | null>(null)

  // Never changes: components that only call login() never re-render.
  const actions = useMemo(() => ({
    login: (response: LoginResponse) => {
      storeCredentials(response.credentials)
      setCurrentUser(response.user)
    },
    logout: () => setCurrentUser(null),
  }), [])

  return (
    <AuthActionsContext value={actions}>
      <AuthStateContext value={currentUser}>
        {children}
      </AuthStateContext>
    </AuthActionsContext>
  )
}
```

`setCurrentUser` from `useState` is already stable, so the actions object has no dependencies.

**Note:** If your project has [React Compiler](https://react.dev/learn/react-compiler) enabled, the compiler memoizes the context value automatically and the manual `useMemo`/`useCallback` are unnecessary. Splitting contexts is still worthwhile — it narrows *which* components subscribe in the first place, which the compiler cannot do for you.

Reference: [Optimizing re-renders when passing objects and functions](https://react.dev/reference/react/useContext#optimizing-re-renders-when-passing-objects-and-functions)

_Source: local addition based on react.dev; not in the upstream Vercel skill._
