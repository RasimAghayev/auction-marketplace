# Technical Debt Register — auction-marketplace

Format: ID | Severity | Location | Description | Impact | Status | Fix reference

| ID | Severity | Location | Description | Impact | Status | Fix |
|----|----------|----------|-------------|--------|--------|-----|
| TD-01 | P0 | `infra/compose.yaml` | Hardcoded production `APP_KEY` (commit `0cc37f8`) — compromised Laravel encryption key | Any deployment reusing this compose file as-is exposes session/cookie/encrypted-field encryption | Fixed (forward-fix) | PR #21: `APP_KEY=${APP_KEY:?...}` no fallback |
| TD-02 | P0 | `infra/compose.yaml` ×5 | Hardcoded test MongoDB password (commit `0cc37f8`) | Test DB credential exposure; violates secrets standard | Fixed (forward-fix) | PR #21: fresh password everywhere |
| TD-03 | P0 | `infra/compose.yaml` | Build context/volume/secrets paths `./backend` instead of `../backend` | `make up`/`make test` impossible — context not found | Fixed (forward-fix) | PR #21: all paths `../backend`, `../frontend`, etc. |
| TD-04 | P0 | `.github/workflows/ci.yml` | CI ran `composer`/`pest`/`npm`/`vite` directly on runner; `continue-on-error: true` masked failures | Violates standing decision 2; false-green CI | Fixed (forward-fix) | PR #21: Docker-first pipeline via compose test profiles |
| TD-05 | P0 | Git branches | `preview`/`test` 15 commits behind `master` | Integration branches stale; PRs into preview get stale CI | Open | PR #20 (fast-forward) — blocked from merge |
| TD-06 | P0 | Git default branch | Default branch is `master`, not `main` | Non-compliance with standing decision 5 (3-branch model) | Parked | Gate 4 — awaiting Rasim decision |
| TD-07 | P1 | `package.json` | 16/29 npm direct deps outdated; 7 major-version gaps (@types/node, @vitejs/plugin-vue, @vueuse/core, eslint, eslint-plugin-vue, jsdom, typescript, vite, vitest, vue-router, zod) | Security vulnerabilities, missing features, incompatibility risk | Open | Phase 6 upgrade pass |
| TD-08 | P1 | `composer.json` | `phpunit/phpunit` ^12.0 → latest 13.4.8 (major gap) | PHPUnit 12 EOL risk, missing PHP 8.5+ support | Open | Phase 6 upgrade pass |
| TD-09 | P1 | `composer.json` | `inertiajs/inertia-laravel` constraint `*` (unpinned) | Unpredictable dependency resolution | Open | Phase 6: pin to version range |
| TD-10 | P1 | `docs/adr/` | Only 1 ADR (ADR-001) | Architecture decisions undocumented (auth, Reverb, module boundaries) | Open | Phase 8: add ADRs |
| TD-11 | P2 | `docs/` | Missing `REPOSITORY_AUDIT.md`, `TECHNICAL_DEBT.md`, `IMPROVEMENT_ROADMAP.md` | No central record of audit findings, debt, or roadmap | Fixed (this doc) | Phase 3 |
| TD-12 | P2 | Repo root | No `repo-manifest.yaml` | No machine-readable service contract for ecosystem tooling | Open | Phase 15 |
| TD-13 | P2 | `docs/` | No Mermaid diagrams beyond basic flowchart | No sequence, ER, or deployment diagrams | Open | Phase 15 |
| TD-14 | P2 | `backend/` | No `backend/.env.example` | Developers must guess required env vars | Open | Phase 15 |
| TD-15 | P2 | `composer.lock` / `package-lock.json` | `@tanstack/vue-virtual`, `vue-demi` present but not in `package.json` dependencies | Possible unused/leftover dependencies | Open | Phase 7 dead-code scan |
| TD-16 | P3 | `infra/docker/frontend/Dockerfile` | `COPY nginx.conf` references file outside frontend build context | Frontend image build fails | Open | Pre-existing; fix by moving nginx.conf into build context or restructuring Dockerfile |
| TD-17 | P3 | Git history | Compromised `APP_KEY` and test MongoDB password remain in history (commit `0cc37f8` + descendants) | Secret exposure in historical clones | Parked | Requires Gate 1 history rewrite — not performed |

## Notes

- TD-01 through TD-04 are forward-fixed in PR #21 (committed, pushed, awaiting manual merge). Old values remain in git history.
- TD-05 and TD-06 are blocked by external factors (permission classifier, Gate 4).
- TD-07 through TD-09 are dependency upgrades owned by Phase 6.
- TD-10 through TD-15 are documentation/ecosystem items owned by Phase 8/15.
- TD-16 is a pre-existing Dockerfile issue not caused by current-phase work.
- TD-17 requires history rewriting (Gate 1) — flagged, not acted on.
