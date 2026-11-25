# Improvement Roadmap — auction-marketplace

Derived from Phase 3 audit findings. Items ordered by severity then dependency order.

## Phase 4 — Git/GitHub hygiene (in progress, PR #21)

| Item | Description | Status |
|------|-------------|--------|
| 4.1 | Remove hardcoded `APP_KEY` from `infra/compose.yaml`; require via env | Committed (PR #21) |
| 4.2 | Replace hardcoded test MongoDB password everywhere | Committed (PR #21) |
| 4.3 | Fix compose context/volume/secrets paths (`../backend`, etc.) | Committed (PR #21) |
| 4.4 | Rewrite CI to Docker-first via compose test profiles; remove `continue-on-error` | Committed (PR #21) |
| 4.5 | Fast-forward `preview`/`test` to `master` (PR #20) | Blocked — `gh pr merge` denied |
| 4.6 | Rename default branch `master` → `main` | Parked — Gate 4 |
| 4.7 | Configure branch protection on `preview`/`test`/`main` | Blocked — private repo, non-Pro plan (403) |

## Phase 5 — Container baseline (in progress)

| Item | Description | Status |
|------|-------------|--------|
| 5.1 | Verify `docker compose config` exit 0 | Done |
| 5.2 | Verify backend image builds | Done (`local/auction-backend:latest`) |
| 5.3 | Fix frontend Dockerfile `COPY nginx.conf` (outside build context) | Open — pre-existing |
| 5.4 | Run test profiles (`test-backend`, `test-frontend`) | Open |
| 5.5 | Add `.env.example` placeholders | Open (root exists; backend/ missing) |
| 5.6 | Verify `make verify` / `make up` targets reference fixed compose | Open |

## Phase 6 — Upgrade to latest

| Item | Description | Current | Target |
|------|-------------|---------|--------|
| 6.1 | `typescript` | 5.9.3 | 7.0.2 (major) |
| 6.2 | `vite` | 5.4.21 | 8.3.8 (major) |
| 6.3 | `vitest` | 2.1.9 | 5.1.2 (major) |
| 6.4 | `vue-router` | 4.6.5 | 5.5.1 (major) |
| 6.5 | `zod` | 3.25.76 | 4.6.7 (major) |
| 6.6 | `eslint` | 9.39.5 | 10.15.0 (major) |
| 6.7 | `eslint-plugin-vue` | 9.33.0 | 10.12.1 (major) |
| 6.8 | `@vueuse/core` | 11.3.0 | 15.1.0 (major) |
| 6.9 | `phpunit/phpunit` | ^12.0 | 13.4.8 (major) |
| 6.10 | `tailwindcss` | 3.4.19 | 4.3.5 (major) |
| 6.11 | `inertiajs/inertia-laravel` | `*` | Pin to `^3.8` |
| 6.12 | `laravel/sail` | ^1.30 | v1.71.0 (major, dev tooling) |
| 6.13 | `predis/predis` | v3.7.1 | v3.8.0 (minor) |
| 6.14 | `php:8.5-fpm-alpine` → `php:8.6-fpm-alpine` | 8.5 | 8.6 (base image) |

Each bump: `make verify` in Docker, commit (`build(deps): ...`), next. Breaking changes migrated and logged in CHANGELOG.

## Phase 7 — Dead code & restructure

| Item | Description | Status |
|------|-------------|--------|
| 7.1 | Scan for unused npm deps (`@tanstack/vue-virtual`, `vue-demi`) | Open |
| 7.2 | Scan for unused Composer deps | Open |
| 7.3 | Remove dead controllers/routes/services | Open |
| 7.4 | Restructure to upgraded framework conventions | Open |

## Phase 8 — Architecture

| Item | Description | Rationale |
|------|-------------|-----------|
| 8.1 | ADR: Auth strategy (Sanctum + PersonalAccessToken) | Document current auth approach |
| 8.2 | ADR: Real-time updates (Reverb WebSockets) | Justify async bid notifications |
| 8.3 | ADR: Module boundaries (services/repositories) | Document DDD-inspired layering |
| 8.4 | Expand `docs/ARCHITECTURE.md` with C4 container view | Current doc is minimal |

## Phase 9 — Hardening

| Item | Description |
|------|-------------|
| 9.1 | Health endpoints `/health/live`, `/health/ready` in both apps |
| 9.2 | Input validation on all mutating endpoints |
| 9.3 | Rate limiting review (existing — verify coverage) |
| 9.4 | Docker image vulnerability scan (trivy/scout) |
| 9.5 | Secret scan pre-commit hook |

## Phase 10 — CI/CD

| Item | Description |
|------|-------------|
| 10.1 | PR #21 CI rewrite (Docker-first) — awaiting merge |
| 10.2 | Measure job durations; record in `docs/TEST_REPORT.md` |
| 10.3 | SHA-pinned actions audit |
| 10.4 | Image publishing to `ghcr.io/rasimaghayev/auction-marketplace` on tag |

## Phase 11 — Tests

| Item | Description |
|------|-------------|
| 11.1 | Coverage report for existing Pest test suite |
| 11.2 | Edge-case tests for auth, bids, auctions |
| 11.3 | Regression against Phase 5 baseline |
| 11.4 | `docs/TESTING_STRATEGY.md`, `docs/TEST_REPORT.md` |

## Dependencies on other phases/items

- Phase 6 upgrades depend on Phase 5 being green (Docker builds + tests).
- Phase 10 depends on PR #21 being merged (CI changes in PR).
- Phase 11 depends on Phase 5 Docker verification (tests run in compose).
