---
title: Public Routes Do Not Wait for Auth
impact: HIGH
impactDescription: public pages paint without waiting for the session check
tags: async, auth, routing, startup
---

## Public Routes Do Not Wait for Auth

**Impact: HIGH (public pages paint without waiting for the session check)**

Restoring the session (`/me`, token refresh) is slow and only protected pages need it. Let the auth provider render its children straight away and expose `isLoading`; only the protected-route guard (`src/helpers/PrivateRoute.tsx`) waits on it. The homepage, article list and dictionary must not sit behind a global "checking session" gate.

**Incorrect (every route, public or not, waits for the session check):**

```tsx
export const AuthProvider = ({ children }: { children: ReactNode }) => {
	const [isLoading, setIsLoading] = useState(Boolean(initialToken));
	// ...
	if (isLoading) {
		return <PageLoading />;
	}
	return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
```

**Correct (the provider always renders; the guard waits only for protected routes):**

```tsx
export const AuthProvider = ({ children }: { children: ReactNode }) => (
	<AuthContext.Provider value={value}>{children}</AuthContext.Provider>
);

const PrivateRoute: React.FC = () => {
	const location = useLocation();
	const { isAuthenticated, isLoading } = useAuth();

	if (isLoading) {
		return <Container className={styles.page} role="status" aria-label="Checking session" />;
	}

	return isAuthenticated ? <Outlet /> : <Navigate to="/login" state={{ from: location }} replace />;
};
```

_Source: carried over from the retired repo skill `react-best-practices` (TanStack Query and SPA guidance)._
