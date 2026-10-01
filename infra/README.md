# Infra

## CI/CD

The CI pipeline lives at [`.github/workflows/ci.yml`](../.github/workflows/ci.yml).
GitHub Actions only runs workflows from that repo-root path, so this
`/infra` folder documents the pipeline rather than hosting it.

Runs on every pull request and on push to `main`:

- **api** (runs twice, on `mysql:8.0` and `mariadb:10.4`; see
  [ADR 0002](../docs/adr/0002-database-server.md)): `composer install`,
  `composer audit`, `vendor/bin/pint --test`, `php artisan migrate:fresh`
  twice (idempotency), `php artisan test` (Pest).
- **web**: `npm ci`, `npm audit --omit=dev --audit-level=high`,
  `npm run lint`, `npm run typecheck`, `npm test` (Vitest), `npm run build`.

[`.github/dependabot.yml`](../.github/dependabot.yml) opens weekly
dependency-update pull requests for Composer and npm.

## Planned additions

- `tests/e2e` Playwright job: Sprint 3, with the guest feedback form.
- Flutter job (`flutter analyze`, `flutter test`): Sprint 7.
- Nginx config, atomic deploy script, backup and restore scripts, and
  runbooks: Sprint 8, once hosting is decided (playbook Q17).
