# ADR 0001: Monorepo source layout and test placement

- Status: Accepted
- Date: 2026-10-01

## Context

CSMF-MRS is a three-client system: a Laravel 12 API, a Nuxt 4 web app
(admin dashboard and public guest form), and a Flutter kiosk app planned
for Sprint 7. The workspace's Golden Standard requires every `PRJ-*`
project to expose a single top-level `/src`, `/tests`, `/config`, `/docs`,
`/infra`, and `README.md`. The specification also requires each app to
keep its framework's standard structure ("API: Use Laravel 12 standard
structure. Web: Use Nuxt 4 standard structure. Mobile: Use Flutter
standard structure.").

PRJ-itsms faced the same question and settled it in its own ADR 0001. The
author asked that CSMF-MRS mirror PRJ-itsms.

## Decision

**Source layout.** The applications live side by side under `/src`, each
in its framework's standard structure:

```
src/
├── api/      # Laravel 12 API
├── web/      # Nuxt 4 + Nuxt UI 4 web app
└── mobile/   # Flutter kiosk app (Sprint 7)
```

**Environment files.** Each app keeps its own environment file where its
framework looks for it (`src/api/.env`, `src/web/.env`), as in PRJ-itsms.
`/config/.env.example` is an index pointing to them.

**Test placement.** Same as PRJ-itsms:

- Laravel's test runner (Pest via `phpunit.xml`) expects its suite at
  `src/api/tests/{Feature,Unit}`, so API unit and feature tests live there.
- Nuxt's test runner (Vitest with `@nuxt/test-utils`) runs from
  `src/web/tests`.
- Flutter's `flutter test` expects `src/mobile/test` and
  `src/mobile/integration_test`.
- The top-level `/tests` holds only cross-cutting end-to-end tests that
  exercise the apps together (`tests/e2e`, Playwright).

## Consequences

- CI runs one job per app (`src/api`, `src/web`, later `src/mobile`), plus
  an end-to-end job once `tests/e2e` has a suite (Sprint 3).
- A Golden Standard audit should read `/tests` as "end-to-end tests live
  here; unit and feature tests live inside each app". This ADR is that
  documentation.
- GitHub Actions only reads workflows from `.github/workflows/` at the
  repository root, so the pipeline lives there and `/infra` documents it.
