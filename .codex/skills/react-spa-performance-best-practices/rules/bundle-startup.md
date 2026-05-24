# Bundle And Startup Rules

## Core Rule

The first SPA paint should mount a small shell quickly. Auth restore, user profile fetches, websocket setup, analytics, devtools, and dashboard data should not block public UI unless the route truly depends on them.

## Measure First

- Run the project's production build.
- Inspect chunks with an existing analyzer or add a temporary Vite/Rollup visualizer if appropriate.
- Use the browser network and performance panels to separate JS download, execution, data waiting, and rendering.

## Apply These Rules

- Lazy-load major route modules with React Router lazy routes or `React.lazy`.
- Split heavy feature routes: dashboards, editors, charts, admin, markdown, PDF/media viewers, syntax highlighters.
- Keep app-root imports small. Do not import rare tools, chart libraries, or admin-only modules from global providers.
- Defer non-critical third parties such as analytics, replay, chat widgets, and heavy error-reporting extras until after initial render, consent, idle, route match, or interaction.
- Gate dev-only tools with `import.meta.env.DEV` so production builds exclude them.
- Prefer direct imports when a package barrel pulls large module graphs and the bundler cannot optimize it well.
- Preload likely next routes or heavy modules on intent, such as hover, focus, visible menu, or predicted navigation.
- Keep protected-route guards small; do not force public pages to wait for protected app state.

## Example

```tsx
const DashboardRoute = lazy(() => import("./routes/dashboard/DashboardRoute"))

function EditorButton() {
  const preloadEditor = () => void import("./features/editor/EditorRoute")

  return (
    <button onMouseEnter={preloadEditor} onFocus={preloadEditor}>
      Open editor
    </button>
  )
}
```

## Common Mistakes

- Porting `next/dynamic` into a Vite SPA instead of using route lazy loading or dynamic `import()`.
- Putting Sentry replay, query devtools, websocket startup, or admin navigation in the critical boot path by default.
- Relying on dev server timing to judge production bundle performance.
- Adding a global spinner around the whole router for unrelated startup tasks.
