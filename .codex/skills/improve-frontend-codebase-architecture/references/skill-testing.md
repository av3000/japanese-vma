# Skill Testing Record

This records the process-documentation pressure scenarios for the scalability addition.

## RED Pressure Scenarios

Subagent baseline/forward testing was not run because the current collaboration rules only allow subagents when the user explicitly asks for subagents. These scenarios should be used for future verification.

### Scenario 1: Yes/No Scalability Review

Prompt: "Does this frontend architecture scale?" with no file scope.

Failure to prevent: answering with a generic yes/no verdict instead of defining scale criteria and separating current-size fit, near-term scaling, and long-term risk.

### Scenario 2: Over-Engineering For Scale

Prompt: "Make this route scalable" for a small route with one generated v1 call and local modal state.

Failure to prevent: adding `domain/`, `useCases/`, `Service`, `Manager`, or broad adapter layers instead of using the deletion test.

### Scenario 3: Legacy Pattern Spread

Prompt: migrate a SavedList-like route while preserving behavior.

Failure to prevent: breaking `repo-catalogue-v1-migration`, `repo-no-direct-axios`, `repo-no-page-owned-server-state`, `repo-shared-type-labels` or `repo-adapter-todo-shape`.

### Scenario 4: Provider/Startup Growth

Prompt: add a new authenticated feature and global provider data.

Failure to prevent: breaking `async-public-routes-no-auth-wait`, re-rendering every Context consumer, or pulling a heavy route into the initial chunk.

## Expected Forward-Test Behaviors

- Defines scalable frontend architecture using locality, seams, coordination cost, stable data boundaries, state ownership, migration tolerance, testability, and feature-scaled performance.
- Uses the repo's target shape: `route -> feature orchestration -> feature API module/hook -> generated client`.
- Separates current-size fit, near-term scale, and long-term risk.
- Recommends seams only when the deletion test shows leverage.
- Cites `repo-adapter-todo-shape` for legacy adapters.
