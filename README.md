# Japanese VMA

Japanese VMA is a Japanese learning platform built as a monorepo with a Laravel backend and a React frontend. It combines reading and study workflows: learners can browse Japanese resources, create and save study lists, publish articles, and interact with community content.

![Application demo 1](./docs/assets/images/jpl-short-1.gif)

![Application demo 2](./docs/assets/images/jpl-short-2.gif)

## Repository Layout

- `client/` contains the React frontend.
- `processor-api/` contains the Laravel API, realtime, and queue-backed backend services.
- `docs/` contains screenshots and project planning artifacts.

For implementation details, start here and then continue into [client/README.md](./client/README.md) or [processor-api/README.md](./processor-api/README.md).

## Product Overview

The platform currently supports:

- browsing articles, kanji, radicals, words, sentences, and catalogues
- creating and editing articles and saved study lists
- user authentication and personal dashboards
- comments, likes, hashtags, and related engagement features
- kanji extraction and other Japanese-language processing workflows
- PDF generation and downloadable study material
- component development through Storybook

## Study Data Sources

Japanese reference data in this project comes from the Electronic Dictionary Research and Development Group datasets, including JMdict, KANJIDIC, JMnedict, and Radkfile. Sentence-oriented content in the product also references community-driven Japanese study material such as Tatoeba-linked examples where supported by the application.

## Architecture At A Glance

### Frontend

The frontend is a route-driven React 19 application built with Vite and TypeScript. It uses React Router for navigation, React Query for most server-state fetching and caching, and Redux Toolkit for some remaining client/global state. Shared providers wrap routing, auth, websockets, Sentry error boundaries, and query state at the app root.

### Backend

The backend is a Laravel 11 modular monolith moving toward a layered structure:

- `app/Http` and `app/Http/v1` handle controllers, requests, and API resources
- `app/Domain` holds domain models, DTOs, value objects, factories, enums, and errors
- `app/Application` contains services, actions, policies, jobs, and repository interfaces
- `app/Infrastructure/Persistence` contains Eloquent-backed repositories, mappers, and persistence models

The repo currently contains both legacy routes in `processor-api/routes/api.php` and newer `v1` routes in `processor-api/routes/api_v1.php`. The docs describe this current state rather than pretending the migration is finished.

### Deployment And Runtime

- Frontend verification and deploy flow lives in `.github/workflows/frontend-ci.yml`.
- Backend image build, release, and verification flow lives in `.gitlab-ci.yml`.
- The live backend web service runs on Render.
- Production queue workers run separately on a GCP VM via Docker Compose.
- Upstash Redis coordinates queue and cache behavior in production.

## Getting Started

### 1. Start the backend

The backend is Docker-first. Detailed instructions live in [processor-api/README.md](./processor-api/README.md), but the shortest path is:

```bash
cd processor-api
docker compose up -d --build
docker compose exec laravel-app composer install
docker compose exec laravel-app php artisan key:generate
docker compose exec laravel-app php artisan migrate
docker compose exec laravel-app php artisan migrate --path=database/migrations/japanese-data
docker compose exec laravel-app php artisan passport:install
docker compose exec laravel-app php artisan db:seed
```

Useful backend URLs once running:

- App/API: `http://localhost:8080`
- Health check: `http://localhost:8080/api/health`
- Scramble API docs: `http://localhost:8080/docs/api`

### 2. Start the frontend

The frontend can run natively with Node or inside Docker. Detailed instructions live in [client/README.md](./client/README.md).

Native development:

```bash
cd client
npm install
npm run dev
```

Frontend Docker development:

```bash
cd client
docker compose up -d --build
```

Useful frontend URLs once running:

- App: `http://localhost:3000`
- Storybook: `http://localhost:6006`

## Recommended Commands

### Frontend

```bash
cd client
npm run dev
npm run typecheck
npm run test
npm run build
npm run storybook
```

### Backend

```bash
cd processor-api
docker compose up -d --build
docker compose exec laravel-app php artisan test --compact
docker compose exec laravel-app vendor/bin/pint --test app
docker compose exec laravel-app vendor/bin/phpstan analyse app
docker compose exec laravel-app php artisan route:list
docker compose logs -f webserver
```

## Orval API Client

After making changes on backend related to api, the scramble auto type detection will run and generate new live `api.json`.

Then, to have the latest changes on the frontend run `npm run orval:live`.

## Documentation Map

- [client/README.md](./client/README.md) explains frontend structure, setup, and commands.
- [processor-api/README.md](./processor-api/README.md) explains backend layers, local runtime, and contributor workflows.
- `docs/assets/images/` stores the current product screenshots used in this README.

## Development notes

### Git work trees

Each work tree must have their own branch, and to avoid git tracking folders put them in .worktrees

```bash
git worktree add -b <worktree-branch> .worktrees/<worktree-folder> <start-point-or-current-branch>
```

Open work tree folder in new IDE.

After development is done in the worktree branch, we can either merge it locally

```bash
git switch <source-branch>
git merge <worktree-branch>
```

or the usual, push to origin and create a PR:

```bash
git push -u origin <worktree-branch>
```

And cleanup

```bash
git worktree remove .worktrees/<worktree-folder>
git branch -d <worktree-branch>
```
