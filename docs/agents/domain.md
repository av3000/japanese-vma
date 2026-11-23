# Domain Docs

How engineering skills should consume this repo's domain and architecture guidance.

## Current guidance sources

Before exploring, read these in order:

- `AGENTS.md` at the repo root for repository-wide rules and the agent workflow
- `processor-api/AGENTS.md` when working under `processor-api/`
- `client/AGENTS.md` when working under `client/`
- `CONTEXT.md` at the repo root for domain vocabulary
- `docs/adr/` for recorded architecture decisions

## Layout

Treat this repo as single-context unless a root `CONTEXT-MAP.md` is introduced later.

```text
/
├── AGENTS.md
├── CONTEXT.md
├── client/
│   └── AGENTS.md
├── processor-api/
│   └── AGENTS.md
└── docs/
    ├── adr/
    └── agents/
```

## Vocabulary rule

Use the repo's existing domain names from code and docs. Prefer `CONTEXT.md` glossary terms over improvised synonyms.

## ADR conflicts

If an ADR conflicts with a proposal, surface that conflict explicitly instead of silently overriding the prior decision.
