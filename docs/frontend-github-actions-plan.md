# Frontend GitHub Actions Release Tracker

## Tasks

- [x] Create `docs/frontend-github-actions-plan.md`
- [x] Add production frontend Docker build path
- [x] Add static runtime server config with SPA fallback
- [x] Add client `.dockerignore`
- [x] Add frontend GitHub Actions workflow
- [x] Add CI verification steps (`npm ci`, `typecheck`, `build`)
- [x] Add image smoke test in CI
- [x] Add GHCR publish step for default branch
- [x] Document required GitHub variable and image name
- [x] Run validation and update task statuses to complete

## Release Notes

- GitHub repository variable required: `CLIENT_VITE_API_URL`
- Published image name: `ghcr.io/<repo-owner>/japanese-vma-client`
- Tag policy:
  - `sha-<commit>`
  - `latest`

## Validation

- `npm ci` passed on March 15, 2026.
- `npm run typecheck` passed on March 15, 2026.
- `npm run build` passed on March 15, 2026 with `VITE_API_URL=https://api.example.com`.
