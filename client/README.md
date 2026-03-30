# Japanese VMA Client

`client/` contains the contributor-facing frontend for Japanese VMA. It is the main browser application for browsing study material, managing personal content, and using the community features exposed by the backend API.

## Stack

- React 19
- Vite 6
- TypeScript
- React Router 6
- TanStack React Query 5
- Redux Toolkit
- Axios
- Bootstrap and React Bootstrap
- Storybook 8
- Vitest
- Sentry

## Frontend Architecture

The frontend is organized around routes, shared UI, and API-facing modules.

- `src/routes/` contains page-level route modules such as articles, lists, dashboard, Japanese material, and community flows.
- `src/components/` contains reusable UI and feature components.
- `src/api/` and `src/services/` contain HTTP access and query/mutation hooks.
- `src/providers/` contains app-wide providers such as auth and websocket context.
- `src/store/` contains Redux Toolkit slices for the parts of the app that still use Redux-managed client state.
- `src/storybook/` and component `*.stories.*` files support UI development and documentation.

Current state matters here:

- React Query is the main server-state pattern.
- Redux is still present and actively used in parts of the app.
- The app root wires together `QueryClientProvider`, `ReduxProvider`, router, auth, websocket, and Sentry boundaries.

## Local Setup

### Native Node workflow

1. Create `client/.env` from `client/.env.example`.
2. Install dependencies.
3. Start the Vite dev server.

```bash
cd client
npm install
npm run dev
```

Important local variables from `.env.example`:

- `VITE_API_URL=http://localhost:8080`
- `VITE_SENTRY_DSN=`
- `VITE_SENTRY_ENVIRONMENT=local`
- `VITE_SENTRY_RELEASE=local-dev`

The app runs on `http://localhost:3000` by default.

### Docker workflow

Use the local Docker setup when you want the frontend containerized or want Storybook exposed with the repo defaults.

```bash
cd client
docker compose up -d --build
```

This starts:

- `react-app` on `http://localhost:3000`
- `storybook` on `http://localhost:6006`

## Recommended Commands

```bash
cd client
npm run dev
npm run typecheck
npm run test
npm run test:coverage
npm run build
npm run storybook
npm run build-storybook
```

## What To Look At First

If you are new to the frontend, these files are the best starting points:

- `src/App.tsx` for top-level providers and app bootstrapping
- `src/routes/routes.tsx` for route structure and lazy loading
- `src/services/axios.ts` for shared HTTP configuration
- `src/store/store.jsx` for remaining Redux-managed state

## CI And Delivery

Frontend verification and deployment are defined in the repository root workflow at `.github/workflows/frontend-ci.yml`.

That workflow currently:

- installs dependencies with `npm ci`
- runs `npm run typecheck`
- runs a production build with the required `VITE_*` variables
- smoke-tests the production image
- publishes the frontend image
- triggers the Render deploy hook for production

## Notes For Contributors

- Keep request logic in existing API and service modules.
- Prefer React Query for new server-state work.
- Avoid introducing new Redux-first patterns for new features.
- Reuse existing route, provider, and shared-component patterns before adding parallel abstractions.
