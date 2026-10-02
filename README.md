# PRJ-csmf-mrs

Client Satisfaction Measurement Form Management and Reporting System
(CSMF-MRS) for the Provincial Government of Benguet. Clients fill in the
ARTA Client Satisfaction Measurement form on the web (by QR code at each
office) or on an office kiosk tablet, and offices generate their summary
reports instead of printing forms and tallying them by hand.

The full requirements, data dictionary, architecture, API contracts and
sprint plan are in the [Master Playbook](docs/CSMF-MRS-Playbook.html)
(open it in a browser).

## Structure

- `/src/api` — Laravel 12 API (Sanctum, Fortify, Pest)
- `/src/web` — Nuxt 4 + Nuxt UI 4 web app (admin dashboard and guest form)
- `/src/mobile` — Flutter kiosk app (added in Sprint 7)
- `/tests` — cross-cutting end-to-end tests; each app's own unit and
  feature tests live inside it (see [`tests/README.md`](tests/README.md)
  and [ADR 0001](docs/adr/0001-monorepo-source-layout.md))
- `/config` — index of the environment files (each app keeps its own `.env.example`)
- `/docs` — Architecture Decision Records and documentation
- `/infra` — CI/CD documentation, deployment and backup scripts

## Prerequisites

- PHP 8.2+ and Composer 2
- Node.js 22+ and npm
- XAMPP's database server running on `127.0.0.1:3306` (same as PRJ-itsms)
- A hosts entry: `127.0.0.1 csmf-mrs` in `C:\Windows\System32\drivers\etc\hosts`

## Quickstart

### Database (once)

```
C:\xampp\mysql\bin\mysql.exe -h127.0.0.1 -P3306 -uroot -e "CREATE DATABASE IF NOT EXISTS db_csmf_mrs_prj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS db_csmf_mrs_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

`db_csmf_mrs_prj` is the development database and `db_csmf_mrs_test`
is used by the test suite (`src/api/phpunit.xml`). Both commands are safe
to re-run.

### API (`src/api`)

```
cd src/api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan csmf:create-sysadmin
php artisan serve --host=csmf-mrs --port=8003
```

`migrate --seed` creates the System Administrator and Admin roles and the
permission catalog (safe to re-run). `csmf:create-sysadmin` creates the
first account; it asks for the password at a hidden prompt. On first
sign-in you will be asked to turn on two-factor login (any authenticator
app works).

Check it: <http://csmf-mrs:8003/api/v1/health> should return
`{"status":"ok", ...}`.

### Web (`src/web`)

```
cd src/web
npm install
npm run dev
```

Open <http://csmf-mrs:8030> and sign in. The status card on the sign-in
page shows **Online** when the API and database are reachable.

Ports are fixed per the workspace `CLAUDE.md`: web `8030`, API `8003`.
Never bind port 80, which XAMPP's Apache uses for other projects.

## Tests and linting

| App | Lint | Tests |
|---|---|---|
| API | `vendor/bin/pint --test` | `php artisan test` |
| Web | `npm run lint` · `npm run typecheck` | `npm test` |

CI runs all of these on every pull request and push to `main` (see
[`infra/README.md`](infra/README.md)).

## Environment

Each app manages its own environment file: copy `src/api/.env.example`
to `src/api/.env` (and optionally `src/web/.env.example` to
`src/web/.env`). Never commit `.env` files or hardcode secrets.

## Status

Sprint 1 (identity & access) complete: sign-in with two-factor, forced
password change for temporary passwords, Users / Roles / Permissions
screens, append-only audit log. API contracts: [docs/api](docs/api/README.md).
Sprint 2 (master data) in progress: offices with a one-level hierarchy
(the OG-* offices under OG), service types, the 241 services of the 2026
Citizen's Charter (6 Internal), region and SQD question lookups, the
`csmf:import-services` command, and the Offices / Services / Service types
screens. Still open: playbook Q5 (OG-OPA and OSMP) and Q9 (region list).
See the playbook's Tab 04 for the roadmap.
