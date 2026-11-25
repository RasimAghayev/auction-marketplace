# Repository Audit — auction-marketplace

## Scope

Full read of `backend/` (controllers, services, repositories, models, routes, config, tests), `frontend/` (pages, stores, router, components, vitest/playwright tests), `infra/` (Dockerfiles, compose.yaml, compose.override.yaml, nginx), `.github/workflows/ci.yml`, `Makefile`, `package.json`, `composer.json`, `composer.lock`, `package-lock.json`, `docs/`.

## Business purpose

Online auction marketplace. Users register, authenticate, browse products, place bids, and complete auctions. Admins manage products, categories, users, and view dashboard statistics. Real-time bid updates via Reverb WebSockets. Laravel 13 backend with MongoDB; Vue 3 frontend with Vite.

## Stack

| Layer | Technology | Constraint | Current (lockfile) |
|-------|-----------|------------|-------------------|
| PHP | php | ^8.4 | — |
| Framework | laravel/framework | ^13.0 | v13.43.0 |
| Auth | laravel/sanctum | ^4.5 | v4.5.0 |
| DB driver | mongodb/laravel-mongodb | ^5.12 | 5.12.1 |
| Cache client | predis/predis | ^3.7 | v3.7.1 |
| WebSockets | laravel/reverb | ^1.14 | v1.14.0 |
| UUID | ramsey/uuid | ^4.10 | 4.10.0 |
| Math | brick/math | 1.0.0 | 1.0.0 |
| Swagger UI | darkaonline/l5-swagger | ^11.4 | 11.4.1 |
| Inertia | inertiajs/inertia-laravel | * | v3.7.0 |
| Fractal | league/fractal | ^0.21 | 0.21 |
| Activity log | spatie/laravel-activitylog | ^5.1 | 5.1.5 |
| Permissions | spatie/laravel-permission | ^8.5 | 8.5.0 |
| Query builder | spatie/laravel-query-builder | ^7.3 | 7.3.9 |
| Symfony UID | symfony/uid | ^8.1 | v8.1.9 |
| Frontend | vue | ^3.5.46 | 3.5.46 |
| Router | vue-router | ^4.5.0 | 4.6.5 |
| State | pinia | ^2.3.1 | 2.3.1 |
| HTTP client | axios | ^1.7.9 | 1.21.1 |
| Dates | date-fns | ^4.1.0 | 4.4.0 |
| Validation | zod | ^3.23.8 | 3.25.76 |
| Build tool | vite | ^5.4.11 | 5.4.21 |
| Type check | vue-tsc | ^2.2.0 | 2.2.12 |
| Linter | eslint | ^9.15.0 | 9.39.5 |
| Formatter | prettier | ^3.4.0 | 3.10.1 |
| CSS | tailwindcss | ^3.4.15 | 3.4.19 |
| Unit tests | vitest | ^2.1.5 | 2.1.9 |
| E2E | playwright | ^1.49.0 | 1.65.1 |
| PHP tests | pestphp/pest | ^4.0 | — |
| PHP unit | phpunit/phpunit | ^12.0 | — |

## Architecture

### Directory layout (ADR-001)

```
infra/          Docker images, Compose, Nginx config
backend/        Laravel API (controllers, services, repositories, models)
frontend/       Vue 3 SPA (pages, stores, router, components)
docs/           Architecture, ADRs, API, DB, infra, dev, reference docs
```

### Backend structure

- Entry: `backend/public/index.php`, `bootstrap/app.php`
- Routes: `backend/routes/api.php` (prefix `/api/v1`)
- Controllers: `backend/app/Http/Controllers/Api/V1/`
- Services: business logic layer between controllers and repositories
- Repositories: data access abstraction over MongoDB models
- Models: `backend/app/Models/` (MongoDB\Laravel Eloquent)
- Auth: Sanctum with custom `PersonalAccessToken` model
- Enums: `backend/app/Enums/` (UserRole, etc.)
- Events/Listeners: notification events, auction completion

### Frontend structure

- Entry: `frontend/src/main.ts`
- Router: `frontend/src/router/index.ts`
- Stores: `frontend/src/stores/` (Pinia)
- Views: `frontend/src/views/`
- Components: `frontend/src/components/`

### Infrastructure

- Compose: `infra/compose.yaml` (backend, frontend, MongoDB, Redis, Reverb, Nginx + test profiles)
- Override: `infra/compose.override.yaml` (dev mode with hot reload)
- Dockerfiles: `infra/docker/php/Dockerfile`, `infra/docker/frontend/Dockerfile`
- Nginx: `infra/docker/nginx/` (container config) + `infra/nginx/` (host/edge)
- Secrets: `secrets/` (runtime-mounted, CI-generated only)

### Data flow (product create)

Browser → Nginx → frontend → `POST /api/v1/products` → backend Nginx → Laravel `ProductController` → `ProductService` → `ProductRepository` → MongoDB `products` collection.

### Data flow (bid place)

Browser → Nginx → frontend → `POST /api/v1/bids` → backend → `BidController` → `BidService` → Reverb WebSocket broadcast → other clients receive real-time update.

## Existing tests

Pest tests cover: Auth, Users, Products, Auctions, Bids, Categories, Notifications, Search. Located in `backend/tests/`. Vitest unit tests and Playwright E2E/API tests in `frontend/`.

## Existing ADRs

- `docs/adr/ADR-001-layer-architecture.md` — infra/backend/frontend separation

## Findings summary

### P0 — Critical

1. **Hardcoded production `APP_KEY`** in `infra/compose.yaml` (commit `0cc37f8`) — compromised encryption key. Fixed in PR #21 (now `${APP_KEY:?...}`). Old value remains in git history.
2. **Hardcoded test MongoDB password** across 5 files (commit `0cc37f8`) — fixed in PR #21.
3. **Compose paths broken** — `./backend` instead of `../backend`, etc. `make up`/`make test` could never work. Fixed in PR #21 (verified: `docker compose config` exit 0, backend image builds).
4. **CI ran directly on runner** with `continue-on-error: true` masking failures — violates standing decision 2. Fixed in PR #21 (Docker-first via compose test profiles).
5. **`preview`/`test` 15 commits behind `master`** — PR #20 opened, blocked from merge.
6. **Default branch is `master`** — Gate 4 parked.

### P1 — High

7. **16/29 npm direct deps outdated** (55%) — see `projects/info/deps/auction-marketplace.md`.
8. **`phpunit/phpunit` ^12.0 → latest 13.4.8** — major version gap.
9. **`inertiajs/inertia-laravel` unpinned (`*`)**.
10. **Only 1 ADR** — architecture decisions undocumented.

### P2 — Medium

11. **Missing `docs/REPOSITORY_AUDIT.md`, `docs/TECHNICAL_DEBT.md`, `docs/IMPROVEMENT_ROADMAP.md`** (this document addresses #1).
12. **No `repo-manifest.yaml`**.
13. **No Mermaid diagrams** beyond basic flowchart.
14. **No `backend/.env.example`**.
15. **`@tanstack/vue-virtual`, `vue-demi` in lockfile but not package.json** — possible unused deps.

## Verification

- `docker compose -f infra/compose.yaml config --quiet` (with `APP_KEY=base64:dummy`) → exit 0
- `docker compose -f infra/compose.yaml --profile core build backend` → exit 0, `local/auction-backend:latest` built
- Frontend image build fails: `COPY nginx.conf` in Dockerfile references file outside frontend build context (pre-existing)

## Out of scope for this audit

- Runtime behavior verification (pending Docker verification)
- Dependency vulnerability scanning (npm audit, composer audit — Phase 6)
- Detailed per-endpoint security review (Phase 9)
