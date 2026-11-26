# 2026 Best-Practice Reference

Consolidated, current (2026) best-practice reference for the auction-marketplace stack. Every framework, library, and product the project depends on has its own section below, plus module-level guidance. Derived from upstream docs and 2026 practitioner guidance. Use this as the standard when implementing features or refactors; re-search when a dependency is upgraded.

> Standing rule: search the latest guidance for the specific library before implementing, then build on top of it. This file is a snapshot, not a substitute for a fresh search.

## Stack versions (from `backend/composer.json`)

| Concern | Package | Version |
| --- | --- | --- |
| Framework | `laravel/framework` | `^13.0` |
| PHP | `php` | `^8.4` |
| MongoDB ODM | `mongodb/laravel-mongodb` | `^5.12` |
| Cache/queue driver | `predis/predis` | `^3.7` |
| WebSockets | `laravel/reverb` | `^1.14` |
| API auth | `laravel/sanctum` | `^4.5` |
| API resources | `league/fractal` | `^0.21` |
| RBAC | `spatie/laravel-permission` | `^8.5` |
| Audit log | `spatie/laravel-activitylog` | `^5.1` |
| Query filtering | `spatie/laravel-query-builder` | `^7.3` |
| API docs | `darkaonline/l5-swagger` | `^11.4` |
| UUID | `ramsey/uuid` / `symfony/uid` | `^4.10` / `^8.1` |
| Big numbers | `brick/math` | `^1.0` |
| Inertia | `inertiajs/inertia-laravel` | `*` |
| Test runner | `pestphp/pest` | `^4.0` |
| Test framework | `phpunit/phpunit` | `^12.0` |
| Linter/formatter | `laravel/pint` | `^1.18` |

---

# Part I — Modules (bounded contexts)

The application is a **modular monolith**: one deployable, divided into bounded contexts under `app/Modules/<Context>/{Models,Repositories,Services,Http}`. Folders alone do not make a module — a boundary exists only when crossing it goes through the module's public contract and a violation fails a check.

## Module boundary rules (apply to every module)

- **Own your tables/collections.** No module queries another module's models directly (no `OtherModule\Models\X::find()`, no cross-module `with()`/`whereHas`). Reference by ID and resolve through the owning module's contract/repository.
- **Cross-module access goes through a contract** (interface under `Contracts`, or a domain event). Synchronous contract call when the caller needs an answer now; event + queued listener when others only react.
- **Dispatch events after commit** (`DB::afterCommit` / after-commit listener settings) so listeners never act on rolled-back data. Make listeners idempotent (at-least-once delivery).
- **Keep the public surface small**: contracts + DTOs + events. Models, repositories, internal services stay private.
- **Enforce in CI**: architecture tests that fail when a module imports another module's namespace outside `Contracts`/`Events`; static analysis for inline FQCN references; per-directory code-review ownership.
- **Test through the contract**: module feature tests enter via the public contract with other modules' contracts bound to fakes; domain logic unit-tested without booting the framework.
- Avoid the pattern for CRUD-heavy admin/settings areas — a contract wrapping one Eloquent call is ceremony that hides code without protecting anything.

## Per-module checklist

| Module | Owns | Public contract surface | Notes |
| --- | --- | --- | --- |
| `Auctions` | auctions, auction lifecycle | `AuctionService`, `AuctionRepository`, domain events (`auction.started/completed/cancelled`) | lifecycle transitions are domain events; cache key `ACTIVE_AUCTIONS_CACHE_KEY` owned here |
| `Bids` | bids | `BidService`, `BidRepository`, `BidPolicy` | `place()` enforces seller-cannot-bid + amount rules; transaction-free on standalone Mongo (see §3) |
| `Products` | products, images | `ProductService`, `ProductRepository`, `ImageUploadService` | image upload limits are constants on `ImageUploadService` |
| `Categories` | categories | `CategoryRepository` | slug/parent uniqueness validated in FormRequest `afterValidation` |
| `Users` | users, dashboard | `UserService`, `DashboardService` | password change revokes Sanctum tokens via connection-aware DB handle |
| `Auth` | authentication | `AuthService` | token issuance via Sanctum `HasApiTokens` |
| `Notifications` | notifications | `NotificationService` | `NotificationPolicy` scopes to owner |
| `Search` | product search | `SearchDriver` contract + `SqlSearchDriver` | driver bound in `AppServiceProvider` |

---

# Part II — Libraries & products

## 1. Laravel 13 — controllers, validation, the 5-layer security model

The request lifecycle and the five security layers, each with a distinct failure status:

| Layer | Question it answers | Failure |
| --- | --- | --- |
| Middleware | Who are you? (auth, throttle) | 401 / 403 / 429 |
| FormRequest | What did you bring? (validation) | 422 / 403 |
| Policy | Is this specific record yours? (ownership/RBAC) | 403 / 404 |
| Service | Is the business state valid? (domain rules) | 422 (DomainException) |
| Resource/Fractal | What shape goes out? | — |

- **Validate in FormRequests**, never inline `$request->validate()` for anything beyond ~2 fields. Controller calls `$request->validated()` (or `$request->safe()->only(...)`) — never `$request->all()`.
- **Lifecycle order**: `prepareForValidation()` → `authorize()` → `rules()` → `after()` hooks → `passedValidation()`. `authorize()` returning `false` yields **403 before any 422** — the right place for ownership/permission gates.
- **Naming**: verb + entity + `Request` (`StoreAuctionRequest`, `UpdateProductRequest`). One class per endpoint.
- **`#[FailOnUnknownFields]`** (Laravel 13) rejects keys with no rule — extra mass-assignment protection; still configure `$fillable`/`$guarded`.
- **API JSON**: `shouldRenderJsonWhen(fn ($r) => $r->is('api/*') || $r->expectsJson())` in `bootstrap/app.php` so validation/auth render as JSON, not redirects.
- **One error document** on every endpoint: `{ "message": ..., "errors": {...}, "error": "<code>" }`. Status table: 201 create, 204 delete (empty body), 401 unauthenticated, 403 policy/authorize, 404 implicit binding, 422 validation, 429 throttle. Keep `APP_DEBUG` off in production.
- **Thin controllers**: read request → call one service/action → return response. Validation→FormRequest, authorization→Policy, work→Service.
- **Policies over role checks**: check permission names via `can()`/policies, not `hasRole()`, so behavior is stable if role names change.
- Prefer `Route::apiResource` for standard CRUD; drop to explicit routes only for custom verbs.

## 2. MongoDB (`mongodb/laravel-mongodb` 5.x) — modeling & transactions

- **Plan the schema early and iterate.** Flexible schema lets you evolve; start simple, optimize as the workload clarifies.
- **Embed vs reference** (decide by cardinality + access pattern):

  | Embed when | Reference when |
  | --- | --- |
  | "has-a"/"contains", read/written together | child side high cardinality |
  | queried together, archived together | unbounded growth |
  | data duplicated is immutable/temporal | write-heavy, updated at different times |
  | need single-document atomicity | child can exist without parent |

- **Atomicity**: single-document writes are atomic. Multi-document atomicity needs **distributed transactions**, which require a **replica set or sharded cluster** — standalone `mongod` rejects `DB::transaction` (*"Transaction numbers are only allowed on a replica set member or mongos"*). Do not write transactional paths against standalone; provision a single-node replica set or keep operations idempotent without multi-doc transactions. Prefer embedding to avoid needing transactions at all.
- **Index commonly queried fields** (filter/sort/`$lookup`/`$group`); monitor index use as the app grows.
- **Laravel MongoDB test traits**: `DatabaseTransactions` and `RefreshDatabase` are **not supported**; use `DatabaseMigrations` to reset between tests.
- Connection-aware helpers: `DB::connection($model->getConnectionName())` (see `MongodbConnection` concern); centralize `_id` route binding via `ObjectIdRouteBinding`.
- Prefer query scopes (`->winning()`, `->active()`) over repeated ad-hoc `where` chains.

## 3. Redis (`predis/predis` 3.x) — cache, tags, locks, stampede

- **Cache tags only work on the `redis` driver** (not `file`/`database`/`dynamodb`/`storage`). Available here.
- **Tags are a grouping key, not a search index** — read back only with the same tags. Keep tag sets bounded; a tag holding hundreds of thousands of keys makes flush expensive. For very large, growing groups prefer a **version counter** (`INCR` one integer, orphan old keys, let TTL reclaim).
- **Invalidate next to the write, after commit** (`Cache::forget` / `tags()->flush()` inside the write path with `$afterCommit`), not at call sites.
- **Stampede protection**: `Cache::lock("rebuild:{$key}", N)->block(...)` with a **re-check inside the lock** so waiters reuse the winner's value; or `Cache::flexible($key, [fresh, stale], $cb)` for stale-while-revalidate on dashboards. Size the lock to outlive the slowest rebuild; write a `:last_good` long-TTL copy so a lock timeout degrades to slightly-stale data.
- **Atomic locks** (`Cache::lock`) for critical sections (placing a bid, completing an auction) instead of check-then-write.
- Centralize cache keys as constants on the owning repository; never hardcode the same key string in multiple places.
- `Cache::flush()` ignores the cache prefix and wipes the whole store (including other apps on the same Redis) — avoid; invalidate by tag/version.

## 4. Laravel Sanctum 4.x — API authentication

- **Two distinct modes:**
  - **SPA (first-party):** cookie/session via `statefulApi()` + `web` guard, CSRF (`/sanctum/csrf-cookie`), httpOnly cookie. **No tokens.** Share the same top-level domain; CORS `supports_credentials=true`; session cookie domain leading-dot.
  - **API tokens (mobile/third-party):** `Authorization: Bearer <token>`, issued via `HasApiTokens::createToken()`. Tokens SHA-256 hashed at rest; show plaintext once.
- **Never use API tokens for your own first-party SPA** — use cookie mode. Never store tokens in `localStorage` (XSS theft); mobile uses secure platform storage.
- **`tokenCan()` hole**: for session-auth requests Sanctum injects a `TransientToken` whose `can()` always returns `true`. If abilities are your only gate, every logged-in SPA user passes. Fix: treat abilities as token-only (`currentAccessToken() instanceof PersonalAccessToken && $token->can(...)`) and defer session requests to policies.
- **Expiry**: global `expiration` (minutes from `created_at`, a hard ceiling) + per-token `expires_at` (third `createToken` arg). Schedule `sanctum:prune-expired`. **Revocation = deletion** (no tombstone).
- **Revoke on credential change**: delete `personal_access_tokens` scoped by `tokenable_id` + `tokenable_type` via the connection-aware DB handle (see `UserService::updatePassword`).
- Keep `HasApiTokens` on `User`; custom token model bound in `AppServiceProvider`.

## 5. Rate limiting

- Define named limiters (`RateLimiter::for(...)`) keyed by user id (fall back to IP) and apply `throttleApi()` / `throttle:<name>` to the `api/*` group. The project's `RateLimitMiddleware` now keys by authenticated user id (fallback IP) and applies per-route limits (auth 5/10/3, auctions 10, bids 30). Prefer user-keyed limits so all users behind one NAT don't share a bucket.

## 6. Pest 4 / PHPUnit 12 — testing

- **Prefer Pest** for new tests; keep PHPUnit-compatible where the suite already uses it. Suites: `Unit`, `Feature`, `Modules`, `ATDD`.
- **Favor feature tests** (route + middleware + validation + policy + controller + DB together); unit-test only pure logic. Default split: feature for anything with a route, unit for branching logic, a few browser tests only where JS is the behavior.
- **One request per HTTP test**; name tests after behavior; assert outcomes (status, JSON shape, DB row, dispatched job), not private method calls.
- **Always test 403 and 422** (and the ownership pair: allowed owner + forbidden non-owner in the same test).
- **Isolate/fake external boundaries**: `Http::preventStrayRequests()` + `Http::fake()` in `tests/Pest.php`; `Queue::fake`, `Mail::fake`, `Notification::fake`, `Storage::fake`, `Event::fake`. Fake boundaries, not your own internals.
- **`phpunit.xml.dist` must `force="true"`** env values (real process env wins otherwise — a Docker-injected `DB_CONNECTION` can send tests at the real DB). Force `DB_CONNECTION`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`. Add a `TestCase` guard that fails loudly if the DB isn't the test DB.
- This project: `DB_CONNECTION=mongodb_testing`, `MONGODB_DATABASE=auction_marketplace_testing`; `failOnRisky`/`failOnWarning` intentionally unset (Laravel deprecation handler produces benign "risky" flags — treat as non-failing unless a real defect).
- **Turn every bug fix into a regression test.** Don't test framework behavior, private methods, exact SQL/HTML, getters/setters/config.

## 7. Laravel Reverb 1.x — WebSockets in production

- **Long-running process** → supervise with Supervisor/systemd (`autorestart=true`, `stopsignal=SIGTERM`, `stopwaitsecs=15`, `killasgroup/stopasgroup`). Never `nohup &`. Run `php artisan reverb:restart` after every deploy (long-running code doesn't reload).
- **Two host/port pairs, don't conflate**: `REVERB_SERVER_HOST`/`REVERB_SERVER_PORT` = where the process listens (e.g. `0.0.0.0:8080`); `REVERB_HOST`/`REVERB_PORT` = public host/port clients + Laravel broadcasts use (443). Mixing them is the top handshake failure.
- **TLS proxy**: Nginx `proxy_pass` with `Upgrade`/`Connection` headers; browsers on HTTPS need `wss://`.
- **Open files**: every connection is an fd; default `nofile` 1024 caps ~1000 connections. Raise `nofile` (65535) in `limits.conf`, Supervisor `minfds`, and Nginx `worker_rlimit_nofile`/`worker_connections` together. Without the `uv` extension the `stream_select` loop caps at 1024 — `pecl install uv`.
- **Horizontal scale**: `REVERB_SCALING_ENABLED=true` on every node + one central Redis (dedicated `REDIS_DB` index, not shared with cache/queue) + load balancer. No sticky sessions needed (one long-lived connection per client). Redis pub/sub is fire-and-forget — monitor client output buffer; don't co-locate cluster Redis with cache Redis.
- **Graceful drain on SIGTERM**: flip health unhealthy → send `pusher:error` 4200 to all connections → wait ~25s before SIGKILL. Prevents reconnect cascades on rolling deploys.
- Configure `allowed_origins` explicitly (no wildcard in prod); monitor via Pulse recorders + `pulse:check` on one node only.

## 8. spatie/laravel-permission 8.x — RBAC

- **Roles group users; permissions are capabilities.** Assign permissions **to roles**; users rarely get direct permissions. App checks **permission names** (`can()`, policies, `@can`), not role names, so role names can change freely.
- **Policies are the integration point**: combine ownership + permission in a policy (`$user->can('edit all posts')` OR ownership). Register in `AppServiceProvider`.
- **Cache**: role/permission data cached (24h default); built-in mutators auto-reset. Direct DB manipulation requires manual `forgetCachedPermissions()` / `permission:cache-reset`. Set a unique cache `prefix` per app on shared Redis. `register_octane_reset_listener` for Octane.
- **Performance**: for frequent permission churn, `$permission->assignRole($role)` can beat `$role->givePermissionTo()`. Bulk seeding: `Permission::insert(...)` (bypasses checks) then flush cache.
- **Middleware**: `role:` / `permission:` / `role_or_permission:` (pipe = OR), or Laravel's `can:` / `Authorize::using()`. Watch middleware priority vs `SubstituteBindings` (404 vs 403). Route macros `->role()`, `->permission()`, `->canAny()`, `->canAll()`.
- **Seeding order**: flush cache → create permissions → flush → create roles + assign.

## 9. spatie/laravel-activitylog 5.x — audit log

- **Log business events, not model churn.** Audit transitions someone may dispute (order shipped, role granted, refund issued); skip `updated_at` bumps and heartbeats (`dontLogIfAttributesChangedOnly`, `logExcept`).
- **Prefer `logOnly([...])` + `logOnlyDirty()` + `dontLogEmptyChanges()`** over `logAll()` — an explicit allowlist means a new sensitive column never leaks into the log.
- **Manual, named events from services** (not controllers): `activity('security')->performedOn($u)->causedBy($user)->event('role_changed')->withProperties([...])->log(...)`. Written on the same connection inside the transaction → rolls back with the change.
- **Never log secrets/PII**: passwords, tokens, 2FA secrets, raw `$request->all()`. Put sensitive names in `default_except_attributes` + model `logExcept()`; redact via `beforeActivityLogged`.
- **Index for the queries you run**: default stub only indexes morph columns. Add composite indexes `(subject_type, subject_id, created_at)`, `(causer_type, causer_id)`, `(log_name, created_at)`.
- **Retention**: per-`log_name` channel; `activitylog:clean --force` scheduled (optionally per channel with `--days`). Security logs kept longer than operational.
- **Queued jobs**: `auth()->user()` is null — pass the initiating user and `causedBy()` explicitly.

## 10. spatie/laravel-query-builder 7.x — filtering/sorting/includes

- **Whitelist everything**: `allowedFilters()`, `allowedSorts()`, `allowedIncludes()`. Disallowed params throw (`InvalidFilterQuery`/`InvalidSortQuery`/`InvalidIncludeQuery`).
- **Filters**: default is `partial` (`LIKE %v%`, can miss indexes) — prefer `beginsWith`/`endsWith` for index use; `exact` for IDs/booleans/literals; `operator` (`GREATER_THAN`, `DYNAMIC`); `groupOr`/`group` for OR/AND; `->ignore(...)` to drop sentinel values. Dot-notation filters relations (`posts.title` → `whereHas`).
- **Sorts**: `allowedSorts`, `defaultSort('-name')`, comma-separated multi-sort, `AllowedSort::custom()`.
- **Includes**: `allowedIncludes`, nested via dot, `postsCount`/`postsExists` auto-allowed, `AllowedInclude::count/sum/min/max/avg` with optional constraint closure. No default includes — use `with()` for always-on eager loads.
- **Security**: unbounded `include` on public APIs is a denial-of-wallet vector — keep whitelists tight; pair with Fractal `parseIncludes` → eager-load hints.

## 11. league/fractal 0.21 — API transformers

- **One transformer class per resource** (`transform()` returns array) — explicit, greppable, unit-testable without booting the framework. Keep presentation/derived fields (`reading_time`) out of the entity.
- **Includes**: `$availableIncludes` + `$defaultIncludes`; `Manager::parseIncludes($_GET['include'])`; nested dot-notation (recursion limit default 10, `setRecursionLimit()`); `parseExcludes()`; include params via `ParamBag`. **Drive eager-loading from the include string** to avoid N+1 lazy loads.
- **Serializers**: `JsonApiSerializer` for JSON:API-shaped output; array serializer otherwise. **Exclude by default, expose explicitly** (fail-safe direction). Handle circular references deliberately.
- **vs Laravel API Resources**: Fractal parses `include` natively and enforces JSON:API; Resources need explicit include parsing but are simpler for plain REST. For non-JSON:API APIs, explicit Resource include parsing is often preferred; migrate staged (v2 parallel) if leaving Fractal.

## 12. darkaonline/l5-swagger 11.x — OpenAPI

- **PHP 8 Attributes only** (`use OpenApi\Attributes as OA;`) — Doctrine annotations removed in v10. v11.x supports Laravel 13 / PHP 8.2+ / swagger-php 6.3.1+.
- **DRY**: define schemas once, reference via `ref: "#/components/schemas/X"`; group by tags; specify types/formats/examples; document **all** response codes (200/201/204/401/403/404/422/429/500).
- **Mirror FormRequest rules** in the schema (required/maxLength/pattern). Keep the OpenAPI file and feature tests reading the same `rules()`.
- **Generate**: `php artisan l5-swagger:generate`; `L5_SWAGGER_GENERATE_ALWAYS=true` in dev only (warns in prod). **Docs routes are public by default** — add `routes.middleware` (e.g. `auth:sanctum`) for prod.
- **Scan modes**: `classic` (default), `hybrid`, `spec` — swagger-php 7 deprecates classic, 8 removes it; `hybrid` is the migration path.

## 13. brick/math 1.x — money & big numbers

- **Never use float for money.** `0.1 + 0.2 !== 0.3`; errors compound across a ledger. Store as `DECIMAL(p,s)` or integer minor units; in PHP treat amounts as **strings or `BigDecimal`**, never a PHP number.
- **brick/math 0.15+ no longer accepts float inputs** to `of()`/arithmetic — pass `of((string) $float)`. Immutable `BigInteger`/`BigDecimal`/`BigRational`; auto-uses GMP/BCMath, pure-PHP fallback.
- **Rounding is explicit**: division needs a scale + `RoundingMode` (`HalfUp` for receipts, `HalfEven` banker's for ledgers). Round **once at a boundary** (store/pay/show), account for the remainder when splitting. PHP 8.4 `RoundingMode::fromNativeRoundingMode(...)` bridges native modes.
- **`brick/money`** (if added) adds currency-aware `Money` (ISO 4217 scale, `getMinorAmount()`/`ofMinor()`, split that sums back). Project uses `brick/math` directly — cast DB decimals to string before arithmetic (see `UserService::dashboardStats` revenue `(float)(string)` pattern; prefer `BigDecimal` for new money math).

## 14. Inertia (`inertiajs/inertia-laravel`) — server-driven SPA

- **Shared data** in `HandleInertiaRequests::share()` — use sparingly (included in every response); namespace to avoid collisions; lazy closures for per-request data (`'auth.user' => fn () => ...`); `Inertia::once()`/`shareOnce()` for data resolved once and remembered client-side.
- **Validation**: Inertia never receives 422 — the Laravel adapter redirects back with flashed errors shared via the `errors` prop (always present, `{}` when empty). Client `onError` fires when `errors` non-empty. `$withAllErrors = true` for all messages per field. Error bags (`X-Inertia-Error-Bag`) scope multiple forms. Old input auto-preserved for post/put/patch/delete.
- **FormRequests work unchanged** (`prepareForValidation` → `authorize` 403 → `rules`); use `validated()`. `Rule::unique(...)->ignore($this->user())` for profile forms (never `ignore()` a request-supplied ID).
- **Build HTML + JSON from one code path**; encode empty `errors` as `{}` not `[]`; render error pages through Inertia (`Inertia::handleExceptionsUsing`).

## 15. Docker / deployment (infra)

- **Multi-stage build**: `composer:2` (vendor, `--no-dev --no-scripts --no-autoloader` then `dump-autoload --optimize --classmap-authoritative --no-dev`), `node:*-alpine` (`npm ci`, `npm run build`), runtime `php:*-fpm-alpine`. `--no-scripts` on install because `package:discover` needs the full tree.
- **`.dockerignore`** (`.git`, `.env`, `.env.*` but `!.env.example`, `node_modules`, `vendor`, `public/build`, `storage/*`, `tests`) — keeps secrets and dev deps out of the image and shrinks the build context.
- **Layer order**: copy `composer.json`/`composer.lock` before install; `package.json` before `npm install`; BuildKit `--mount=type=cache` for downloads.
- **Build caches at container start, not build time** (entrypoint runs `config:cache`/`route:cache`/`view:cache`/`event:cache`) — `config:cache` bakes env (secrets) into a layer if run at build. Once cached, `env()` returns null outside config files — read via `config()`.
- **Run as non-root** (`www-data`/`app`); PHP-FPM `clear_env = no` (else `env()` is null in workers); size `pm.max_children` to the memory limit.
- **Secrets at runtime** via orchestrator (Compose `environment:`/env file not in image, ECS Secrets Manager/SSM), never baked into layers.
- **Project-specific**: `infra/compose.yaml` has a latent `dockerfile: ./docker/php/Dockerfile` path bug (resolves relative to `../backend` context) — direct `docker build` is the workaround. `backend-internal` is `internal: true`.

## 16. UUIDs (`ramsey/uuid` / `symfony/uid`) & IDs

- Use UUIDs as external identifiers; keep Mongo `_id` (ObjectId) internally and bind via `ObjectIdRouteBinding`. `symfony/uid` for `Ulid`/`Uuid` value objects; `ramsey/uuid` for RFC 4122 variants.

---

# Part III — Cross-cutting backend hygiene

- **DRY model concerns** over copy-paste: connection name + route binding in traits (`MongodbConnection`, `ObjectIdRouteBinding`) on `BaseModel` and non-`BaseModel` models.
- **No dead code**: remove unused services/methods/duplicate cache-key constants as found.
- **N+1 elimination**: `withCount`, batched aggregates, eager loading — never per-row queries in loops.
- **Money**: `brick/math`/string-cast decimals, never raw float on money.
- **Consistent error envelope + status codes** across all endpoints (see §1).
- **Centralized cache keys** as repository constants; invalidate by tag/version after commit.
- **Pint** after changes; `docker cp` fixed files back to the host (Windows/PowerShell workflow — no `&&`, no `head`).

---

# Per-library quick index

- Laravel 13 → §1, §5, §16
- mongodb/laravel-mongodb 5.x → §2, §16
- predis/predis 3.x (Redis) → §3
- laravel/sanctum 4.x → §4
- pestphp/pest 4 + phpunit 12 → §6
- laravel/reverb 1.x → §7
- spatie/laravel-permission 8.x → §8
- spatie/laravel-activitylog 5.x → §9
- spatie/laravel-query-builder 7.x → §10
- league/fractal 0.21 → §11
- darkaonline/l5-swagger 11.x → §12
- brick/math 1.x → §13
- inertiajs/inertia-laravel → §14
- Docker/infra → §15