# ADR 0003: MCP as a second delivery adapter

- **Status:** Accepted (2026-11-24)
- **Deciders:** repository owner
- **Source:** epic [#693](https://github.com/av3000/japanese-vma/issues/693), first slice [#694](https://github.com/av3000/japanese-vma/issues/694)

## Context

The platform's study data is only reachable through the website. A learner who uses an AI assistant cannot ask it to look up a word, find reading at their level or run a drill from our lists, although the backend already has the services for all of it. The Model Context Protocol (MCP) is how assistants such as Claude Desktop and Claude Code call tools on a server.

## Decision

MCP is a second delivery adapter next to `app/Http/v1`. A tool in `App\Mcp` validates its input, calls the existing `*ServiceInterface` methods as a guest, and shapes the result through a presenter. There is no HTTP hop, no new persistence and no change to the v1 REST contract.

| Decision | Choice | Rejected | Cost |
|---|---|---|---|
| Where it lives | Inside `processor-api` on `laravel/mcp` | Standalone Node server over the REST API | The library hides the wire protocol |
| Data access | Application services directly | HTTP calls to our own v1 API | MCP must respect the same visibility rules itself |
| Output shape | Dedicated presenters | Reuse v1 JsonResources | A second shape to maintain; v1 resources are sized for the React app, not a model's context |
| Transport | One web route, used on localhost first | A separate command-line registration | The inspector and clients need the HTTP server running |
| Access | Public and throttled | OAuth through Passport | No personal data; Passport's consent screen needs a learner web login that does not exist yet |
| Rate-limit key | Client IP address | The MCP session id | Hosted connectors (claude.ai, ChatGPT) call from their vendor's servers and share one bucket; a session id is minted by the client's own `initialize` call, so keying on it can be bypassed. Locally run clients are the target; #716 revisits the limit before production |
| Tool shape | Six task-shaped tools | One tool per endpoint | Hand-written descriptions and schemas |
| Level filter | Passed through, plus a computed `main_level`: the easiest level whose kanji, with every easier level, cover at least 90% of the article's kanji | Server-side filtering by a derived level; `main_level` as the level with the most kanji, which labels most hard articles N4 | "N4" also returns harder articles; the assistant picks by `main_level` |

## Rules every tool follows

- **Layering:** `App\Mcp` may import `App\Application`, `App\Domain` and `App\Shared`. It never imports `App\Infrastructure`, `App\Http` or an Eloquent model, and only calls `*ServiceInterface` (`ARCH-cross-module-service-interface`). `tests/Unit/Architecture/McpLayerDependencyTest` enforces it.
- **Read-only, no login:** every tool is annotated read-only and runs as a guest. Nothing a guest cannot see on the website is returned.
- **Output:** items carry `uuid`, never integer ids, engagement counts, viewer state, author details or processing status. Levels are `N5` to `N1`, and `null` for unassigned or uncommon (`App\Mcp\Support\StudyLevel`).
- **Lists:** `limit` defaults to 5 with a maximum of 20; results include `total` and `has_more`; a tool that can answer `has_more: true` also takes `page`.
- **Failures:** a tool never throws to the client. Validation and `Result` failures become an error response through `App\Mcp\Support\ToolFailure`.
- **Route:** one web server at `/mcp/study`, registered only when `study_mcp.enabled` (`STUDY_MCP_ENABLED`) is true. The setting is on unless `APP_ENV` is `production`, so production stays off until a maintainer sets it.

## Consequences

- `laravel/mcp` loads `routes/ai.php` outside the `web` and `api` route groups, so the route has no CSRF check and no throttle. That suits a stateless public JSON-RPC endpoint, and it means the rate limit (MCP-07) has to be added on the route itself.
- `laravel/mcp` moves from a transitive dev dependency of Laravel Boost (0.6.7) to a `require` entry (^1.2). Boost 2.4.4 pins `laravel/mcp` below 1.0, so Boost moves to ^2.10 in the same change.
- Every item MCP returns is shaped twice, once for the website and once for the assistant. A change to what a module exposes has two places to land.
