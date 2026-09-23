# ADR 0002: Real-time transport for processing status

- **Status:** Accepted (2026-09-04), pending one runtime check (see "Verification before implementation")
- **Deciders:** repository owner
- **Source:** [Async processing audit](../architecture/async-processing/README.md), findings F-01, F-10, F-12, F-20
- **Implements via:** issue specs P0-5, P2-2, P2-3, P2-6, P2-7 in [`issue-specs.md`](../architecture/async-processing/issue-specs.md)

## Context

The backend, worker, and frontend all contain Laravel Reverb integration, but no repository-managed runtime enables it: the GCP worker env has no `BROADCAST_DRIVER` or `REVERB_*` variables (events go to the `null` driver), the worker Compose file runs only Horizon, and the frontend production build receives no `VITE_REVERB_*` variables (the provider never connects). The deleted `docs/render-backend-pipeline-plan.md` recorded Reverb as intentionally out of the first production rollout. A Reverb service may exist in the Render dashboard, but nothing in the repository points the worker or the browser at it. Git history never contained a Reverb host in CI.

## Decision

1. **Polling is the correctness baseline.** The UI must reach the right state with no socket, via `refetchInterval` while a status is non-terminal and the socket is not connected (P0-5). Sockets only make it faster.
2. **Keep Laravel Reverb, hosted as a dedicated Render web service** running the existing backend image with start command `php artisan reverb:start --host=0.0.0.0 --port=$PORT`, on an always-on instance type. Render provides TLS and the public hostname; no reverse proxy or firewall work on the GCP VM.
3. **Wire every producer and consumer to it**, all managed in the repository:
   - worker env (`.gitlab-ci.yml` `prepare_queue_runtime_env`): `BROADCAST_DRIVER=reverb`, `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST=<reverb-service-host>`, `REVERB_PORT=443`, `REVERB_SCHEME=https`;
   - Render web service: the same broadcast variables (the web process broadcasts `pending` after ADR 0001);
   - Reverb service: `REVERB_APP_*`, `REVERB_SERVER_HOST=0.0.0.0`, `REVERB_SERVER_PORT=$PORT`, `allowed_origins` restricted to the frontend origin, scaling disabled;
   - frontend CI (`.github/workflows/frontend-ci.yml`): `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT=443`, `VITE_REVERB_SCHEME=https` as repository variables, with a hard failure when missing.
4. **Channel authorisation follows article visibility** (owner, admin, or public article), not "any authenticated user". Anonymous viewers rely on polling.
5. **Lists do not open one channel per article.** The owner dashboard uses one `App.User.{id}` channel; public lists rely on polling; the detail page keeps its per-article channel.
6. **Broadcasts are synchronous from the producer** (`ShouldBroadcastNow`, snapshotted payload), per P0-3.

## Verification before implementation

In the Render dashboard, record for the audit README:

- whether a Reverb service already exists, its start command, instance type, and hostname;
- the web service's `QUEUE_CONNECTION` and `BROADCAST_DRIVER`. If `QUEUE_CONNECTION=sync`, article creation runs both jobs inline in the HTTP request; switching it to `redis` (Upstash) is a Phase 0 blocker and takes precedence over any socket work.

## Consequences

- One more always-on Render service (cost). Free/sleeping tiers are not acceptable for Reverb because connections drop when the instance sleeps.
- The GCP VM stays private; only Horizon runs there.
- Local Compose keeps its `reverb` container; `laravel-app` gains the `REVERB_*` env it needs for web-side `pending` broadcasts (P2-7).
- If Reverb is unavailable, users see the polling cadence (5 s, backing off to 15 s) instead of a broken promise.
- `SocketConnectionBanner` becomes informational; the "refresh after operations complete" instruction is removed once polling ships.

## Alternatives considered

- **Reverb on the GCP worker VM** behind Caddy/nginx. Rejected: needs TLS, a certificate, a public firewall rule on a host that is currently private, and one more process to babysit on a VM without a platform health model.
- **Hosted Pusher-protocol provider.** Viable drop-in (same client, same env shape). Rejected for now to avoid a new vendor while Render already hosts the image; revisit if Reverb operations become a burden.
- **Polling only, remove sockets.** Simplest and honest. Rejected as the end state because the integration already exists and the detail page benefits from sub-second updates, but it is the fallback and the interim production behaviour until P2-2 lands.
