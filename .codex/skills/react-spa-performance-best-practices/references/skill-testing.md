# Skill Testing Record

This record follows `superpowers:writing-skills`: pressure scenarios first, then skill drafting, then forward testing.

## RED Pressure Scenarios

### Scenario 1: Slow First Paint In Vite SPA

Prompt: review a Vite React SPA using TanStack Query, React Router, and client-side auth with slow first paint, large bundle, unnecessary rerenders, and confusing loading states.

Baseline result without this skill: good practical guidance, but no reusable rule index. It correctly emphasized app boot, public-route auth boundaries, route lazy loading, TanStack Query, and profiling before memoization.

### Scenario 2: Data Lifecycle And Startup Weight

Prompt: implement fixes in a Vite React SPA with stale data, duplicate fetches, broad Context rerenders, excessive memoization, route-level code-splitting opportunities, and third-party startup scripts.

Baseline result without this skill: strong SPA instincts, but still relied on ad hoc priority ordering. It highlighted data ownership, query keys, Context blast radius, route splitting, third-party deferral, and production build checks.

## Expected Forward-Test Behaviors

- Starts with measurement and bottleneck classification.
- Defaults to Vite, React Router, and TanStack Query.
- Avoids `next/dynamic`, server actions, RSC, and `React.cache()` unless a Next.js/runtime condition is explicit.
- Chooses route splitting, app boot simplification, and data ownership before blanket memoization.
- Uses the canonical rule index instead of loading a large compiled document.

## Forward Testing

### Scenario 1 Result

Passed. The agent used `SKILL.md`, `bundle-startup.md`, `async-data.md`, and `rerender-state.md`; it measured first, treated the app as a Vite SPA, used React Router lazy loading and TanStack Query, avoided Next.js/RSC/server-action assumptions, and prioritized startup/data ownership before memoization.

### Scenario 2 Result

Passed. The agent used `SKILL.md` plus all four SPA rule files; it ordered work as measurement, stale-data/invalidation fixes, duplicate fetch cleanup, public-route bundle cuts, third-party deferral, inline-component fixes, localStorage hot-path cleanup, and only then targeted rerender optimization.
