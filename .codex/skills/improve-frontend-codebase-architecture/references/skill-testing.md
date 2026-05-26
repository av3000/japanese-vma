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

Failure to prevent: modernizing legacy shape in place, copying `apiCall(...)`, route-owned pagination, magic labels, or temporary adapters without targets.

### Scenario 4: Provider/Startup Growth

Prompt: add a new authenticated feature and global provider data.

Failure to prevent: coupling public routes to auth/profile/private data, expanding provider rerender blast radius, or pulling heavy/private chunks into the initial public bundle.

## Expected Forward-Test Behaviors

- Defines scalable frontend architecture using locality, seams, coordination cost, stable data boundaries, state ownership, migration tolerance, testability, and feature-scaled performance.
- Uses the repo's target shape: `route -> feature orchestration -> feature API module/hook -> generated client`.
- Separates current-size fit, near-term scale, and long-term risk.
- Recommends seams only when the deletion test shows leverage.
- Treats legacy adapters as transitional debt with target/removal status.
