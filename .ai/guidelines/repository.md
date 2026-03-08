# Repository Guidance

Use this repository as a multi-application workspace:

- `processor-api/` contains the Laravel backend.
- `client/` contains the React frontend.
- `docs/` contains project documentation assets.

When migrating backend endpoints:

- Preserve legacy behavior intentionally. Change contracts only when requested or when the change is explicitly documented.
- Keep diffs focused. Do not mix endpoint migration work with unrelated refactors.
- Treat `processor-api/AGENTS.md` as the backend source of truth for implementation constraints.
- Use `.ai/guidelines/` for always-on conventions and `.ai/skills/` for deeper, task-specific workflows.
