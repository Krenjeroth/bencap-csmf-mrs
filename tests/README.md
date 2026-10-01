# Tests

Per [ADR 0001](../docs/adr/0001-monorepo-source-layout.md), unit and
feature tests for each application live inside that application, where
their framework's tooling expects to find them (same layout as PRJ-itsms):

- API (Laravel + Pest): `../src/api/tests/{Feature,Unit}`
- Web (Nuxt + Vitest): `../src/web/tests`
- Mobile (Flutter, added in Sprint 7): `../src/mobile/test`

This top-level `tests/` directory holds only **cross-cutting end-to-end
tests** that exercise the API and the web app together.

- `e2e/` — end-to-end suite (Playwright, added in Sprint 3 with the guest
  feedback form, the first flow that spans both apps)
