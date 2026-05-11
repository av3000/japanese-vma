# Domain Docs

How engineering skills should consume this repo's domain and architecture guidance.

## Current guidance sources

Before exploring, read these in order:

- `AGENTS.md` at the repo root for repository-wide rules
- `processor-api/AGENTS.md` when working under `processor-api/`
- `client/AGENTS.md` when working under `client/`
- `CONTEXT.md` at the repo root if it exists in the future
- `docs/adr/` if ADRs are added in the future

If `CONTEXT.md`, `CONTEXT-MAP.md`, or `docs/adr/` do not exist, proceed silently and use the existing `AGENTS.md` files as the current guidance layer.

## Layout

Treat this repo as single-context by default unless a root `CONTEXT-MAP.md` is introduced later.

Current practical layout:

```text
/
├── AGENTS.md
├── client/
│   └── AGENTS.md
├── processor-api/
│   └── AGENTS.md
└── docs/
```

## Vocabulary rule

Use the repo's existing domain names from code and docs. If `CONTEXT.md` is added later, prefer its glossary terms over improvised synonyms.

## ADR conflicts

If future ADRs conflict with a proposal, surface that conflict explicitly instead of silently overriding the prior decision.
