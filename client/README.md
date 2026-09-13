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
- CSS Modules with design tokens in `src/styles` (Bootstrap is being retired; see Styling)
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

Use Node 24 for local frontend work. The repo-level version hint lives in `client/.nvmrc`.

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

## Generate API Client

```bash
npm run orval:article
docker compose run --rm react-app
```

## What To Look At First

If you are new to the frontend, these files are the best starting points:

- `src/App.tsx` for top-level providers and app bootstrapping
- `src/routes/routes.tsx` for route structure and lazy loading
- `src/services/axios.ts` for shared HTTP configuration
- `src/store/store.jsx` for remaining Redux-managed state

## Styling

Styling is browser-native CSS: design tokens as custom properties in `src/styles/00-settings`, global element styles in `src/styles`, and one CSS Module per component. Radix powers non-modal primitives such as popovers; `<dialog>` powers modals via `src/components/shared/DialogModal`.

Rules:

- No Bootstrap, Tailwind, or other utility-class frameworks. Layout belongs in CSS Modules or the shared layout primitives, not in `className` strings.
- `npm run style:audit` reports remaining Bootstrap/Tailwind class tokens and Sass files; `npm run style:audit -- --check` fails when usage exceeds `style-budget.json`. Lower the budget as you migrate files, never raise it.
- ESLint blocks new `react-bootstrap`, `react-router-bootstrap`, and `bootstrap` imports.
- `src/styles/legacy/bootstrap-compat.css` is a temporary, self-hosted subset of Bootstrap 4 kept only for consumers that are not migrated yet. Do not add rules to it; delete it when the audit reports zero Bootstrap tokens.
- Supported browsers are set by `build.target` in `vite.config.ts` (Baseline "widely available"). The `browserslist` block in `package.json` is not used by Vite.

## CI And Delivery

Frontend verification and deployment are defined in the repository root workflow at `.github/workflows/frontend-ci.yml`.

That workflow currently:

- uses Node 24 from `client/.nvmrc`
- installs dependencies with `npm ci`
- regenerates the Orval client from tracked `processor-api/api.json`
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
