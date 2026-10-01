# ADR 0002: Database server and driver

- Status: Accepted
- Date: 2026-10-01

## Context

The specification says MySQL. The author asked that CSMF-MRS use the same
database setup as PRJ-itsms, which connects with Laravel's `mysql` driver
to `127.0.0.1:3306`. On this development machine that port is served by
XAMPP's database server, which reports itself as `10.4.32-MariaDB`.
MariaDB is a MySQL fork and speaks the MySQL protocol, so Laravel's
`mysql` driver works with it.

Two details matter for the schema:

- Laravel's separate `mariadb` driver maps `$table->uuid()` to MariaDB's
  native `UUID` type, which only exists from MariaDB 10.7. With the
  `mysql` driver the same call creates `CHAR(36)`, which works on
  MariaDB 10.4 and MySQL 8.
- MariaDB 10.4 stores `JSON` columns as `LONGTEXT` with a `JSON_VALID`
  check; MySQL 8 has a native JSON type. Laravel reads both the same way.

## Decision

- Use `DB_CONNECTION=mysql` against `127.0.0.1:3306`, exactly as PRJ-itsms.
- Development database: `db_csmf_mrs_prj`. Test database:
  `db_csmf_mrs_test` (configured in `src/api/phpunit.xml`, as PRJ-itsms
  does with `db_itsms_test`). Character set `utf8mb4`, collation
  `utf8mb4_unicode_ci`.
- Write migrations using only features that both MariaDB 10.4 and MySQL 8
  support: `CHAR(36)` UUIDs, `CHECK` constraints (enforced by MariaDB 10.2+
  and MySQL 8.0.16+), standard indexes. No spatial types, no
  generated-column tricks that differ between the two.
- Tests run against the real database server, never SQLite, so `CHECK`
  constraints and foreign keys behave as they do in development.
- CI runs the API tests twice: on `mysql:8.0` (as PRJ-itsms) and on
  `mariadb:10.4` (matching development).

## Consequences

- The production engine stays open. Because the schema is portable, the
  hosting decision (playbook Q17) can pick MariaDB or MySQL 8 without
  code changes. MariaDB 10.4 is past its upstream end of life (June 2024),
  so a newer server is recommended for production.
- Anyone switching to the `mariadb` driver must first confirm the server is
  10.7 or newer, or UUID columns will fail to create.
