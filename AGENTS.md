# AGENTS.md

This file provides **repository-wide** guidance for AI agents and contributors working in `japanese-vma`.

## 1) Repository Purpose & Shape
- **Product goal:** Japanese learning platform with content, community, and Japanese language study resources.
- **Main applications:**
  - `processor-api/` → Laravel API.
  - `client/` → React application.
  - `docs/` → documentation assets.
- **Scoped instructions:**
  - Use this root file for cross-cutting repo rules.
  - Use `processor-api/AGENTS.md` for backend-specific implementation rules.
  - Use `client/AGENTS.md` for frontend-specific implementation rules.

## 2) Developer Experience (DX) Baseline
- **Recommended local tooling:**
  - Git + conventional commit hygiene.
  - Node/npm for frontend workflows.
  - PHP/Composer for Laravel workflows.
  - Docker/docker-compose for full-stack local environment when needed.
- **Editor quality-of-life plugins (recommended):**
  - ESLint + Prettier extensions for frontend lint/format feedback.
  - EditorConfig support.
  - PHP Intelephense (or equivalent) for Laravel navigation.
  - Tailwind CSS IntelliSense for utility-class workflows.
- **Before opening PRs:**
  - Run targeted checks for touched surface area first.
  - Run broader checks when practical.
  - Keep outputs and assumptions explicit in summaries.

## 3) How to Work in This Repository
- **Change strategy:**
  - Keep diffs focused; avoid unrelated refactors.
  - Prefer incremental, reviewable changes.
  - Reuse existing local patterns before introducing new abstractions.
- **Architecture strategy:**
  - Maintain separation of concerns and avoid cross-layer leakage.
  - Prefer consistency with neighboring code over inventing parallel styles.
- **Migration strategy:**
  - Legacy and v1 paths may coexist; migrate intentionally and incrementally.
  - Preserve behavior unless change is intentional and documented.

## 4) Cross-Cutting Quality Standards
- **Clarity:**
  - State assumptions and constraints explicitly.
- **Traceability:**
  - Include file-level summaries and commands used for validation.
- **Reliability:**
  - Validate changed behavior with tests/checks where feasible.
  - If environment blocks a check, report the limitation clearly.
- **Safety:**
  - Do not silently alter contracts or conventions.
  - Highlight behavior-impacting changes and rollout implications.

## 5) Prompting Guidance (All Domains)
- **For planning tasks:**
  - Include scope, non-goals, required format, and acceptance criteria.
  - Request discovery summary before proposed implementation steps.
- **For implementation tasks:**
  - Specify target modules/files, constraints, and validation expectations.
  - Ask for “must-do now” vs “follow-up” recommendations when refactoring.

## 6) Domain-Specific Instruction Files
- Backend rules live in: `processor-api/AGENTS.md`
- Frontend rules live in: `client/AGENTS.md`

When touching files under either subtree, treat the scoped AGENTS file there as the primary implementation guide.
