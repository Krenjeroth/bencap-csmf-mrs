# CSMF-MRS API contracts

Base URL in development: `http://csmf-mrs:8003`. All JSON. Admin and
account endpoints use Sanctum cookie sessions from the Nuxt app
(`http://csmf-mrs:8030`): call `GET /sanctum/csrf-cookie` first, then send
the `X-XSRF-TOKEN` header (nuxt-auth-sanctum does both).

Errors follow Laravel's defaults: `422 {"message", "errors": {field: [..]}}`;
`401`, `403`, `404`, `419` (CSRF), `423` (password confirmation needed) and
`429` return `{"message"}`. Admin refusals for a pending account step add a
`code`: `password_change_required` or `two_factor_required`.

## Public

| Method | Path | Notes |
|---|---|---|
| GET | `/up` | Framework liveness check |
| GET | `/api/v1/health` | `{"status":"ok"\|"degraded","app","database","time"}`; 503 when the database is unreachable |

## Sign-in (Laravel Fortify, prefix `/api`, same as PRJ-itsms)

| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/api/login` | `email`, `password`, `remember?` | `200 {"two_factor": false}` signed in, or `{"two_factor": true}` when a code is needed next. 5 attempts per minute per email+IP, then 429 |
| POST | `/api/two-factor-challenge` | `code` (6 digits) or `recovery_code` | `204` signed in; `422` wrong code |
| POST | `/api/logout` | | `204` |
| PUT | `/api/user/password` | `current_password`, `password`, `password_confirmation` | `200`; clears `must_change_password` |
| POST | `/api/user/confirm-password` | `password` | `201`; unlocks the routes below for a few minutes |
| POST / DELETE | `/api/user/two-factor-authentication` | | turn on (issues a secret) / off; `423` until the password is confirmed |
| GET | `/api/user/two-factor-qr-code` | | `{"svg","url"}` |
| GET | `/api/user/two-factor-secret-key` | | `{"secretKey"}` |
| POST | `/api/user/confirmed-two-factor-authentication` | `code` | `200`; two-factor is now on |
| GET / POST | `/api/user/two-factor-recovery-codes` | | list / regenerate recovery codes |

Not offered in v1: registration, email password reset, email verification,
profile self-edit, passkeys.

## Current user

`GET /api/v1/me` (signed in; allowed before the password and two-factor
steps). Returned **without** a `data` wrapper, because nuxt-auth-sanctum
stores the body as the user:

```json
{
  "id": "0199a3f2-8d10-71c4-a2e7-5b3f9c0d6e21",
  "name": "Office Clerk",
  "email": "clerk@benguet.gov.ph",
  "is_active": true,
  "must_change_password": false,
  "two_factor_enabled": false,
  "last_login_at": "2026-10-01T20:45:12+08:00",
  "roles": [{ "id": 2, "title": "Admin", "is_system": false }],
  "created_at": "2026-10-01T19:02:41+08:00",
  "updated_at": "2026-10-01T20:45:12+08:00",
  "is_system_administrator": false,
  "two_factor_required": false,
  "permissions": ["dashboard.view", "feedback.encode", "feedback.view", "offices.view", "reports.export", "reports.view", "service-types.view", "services.view"]
}
```

## Admin (`/api/v1/admin`, signed in, password changed, two-factor on for System Administrators)

Lists accept `?q=` (search), `?sort=` (column, `-column` for descending,
whitelisted per endpoint), `?per_page=` (1–100, default 15) and `?page=`,
and return Laravel's `{"data", "links", "meta"}`.

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/role-options` | `users.view` | `{"data":[{"id","title","is_system"}]}` for role pickers |
| GET | `/permission-options` | `roles.view` | `{"data":[{"id","title","resource","description"}]}` |
| GET | `/office-options` | `users.view`, `offices.view` or `services.view` | `{"data":[{"id","code","name","is_active"}]}` in charter order |
| GET | `/service-type-options` | `services.view` | `{"data":[{"id","type"}]}` |
| GET | `/users` | `users.view` | Filters `role_id`, `office_id`, `status=active\|inactive`; sort `name`, `email`, `created_at`, `last_login_at`. Rows include `office` (`{"id","code","name"}` or null) |
| POST | `/users` | `users.create` | `name`, `email`, `role_ids[]`, `is_active?`, `office_id?` → `201 {"data": user, "temporary_password"}` (shown once) |
| GET | `/users/{uuid}` | `users.view` | |
| PUT | `/users/{uuid}` | `users.update` | `name?`, `email?`, `is_active?`, `office_id?` (null removes the office); deactivating signs the user out everywhere |
| DELETE | `/users/{uuid}` | `users.delete` | Soft delete; `204` |
| PUT | `/users/{uuid}/roles` | `users.update` | `role_ids[]` |
| POST | `/users/{uuid}/reset-password` | `users.update` | `reset_two_factor?` → `{"temporary_password"}`; signs the user out everywhere |
| GET / POST | `/roles` | `roles.view` / `roles.create` | Create: `title`, `description?`, `permission_ids[]?` |
| GET / PUT / DELETE | `/roles/{id}` | `roles.view` / `roles.update` / `roles.delete` | |
| PUT | `/roles/{id}/permissions` | `roles.update` | `permission_ids[]` |
| GET / POST | `/permissions` | `permissions.view` / `permissions.create` | Create: `title` (`resource.action`), `description?`; System Administrator gets it automatically |
| GET / PUT / DELETE | `/permissions/{id}` | `permissions.view` / `permissions.update` / `permissions.delete` | |
| GET / POST | `/offices` | `offices.view` / `offices.create` | List: filter `status`; sort `sort_order` (default), `code`, `name`; rows include `services_count`, `active_services_count`, `users_count`. Create: `code`, `name`, `slug?` (guest form address `/f/{slug}`, made from the code when empty), `is_active?`, `sort_order?` |
| GET / PUT / DELETE | `/offices/{id}` | `offices.view` / `offices.update` / `offices.delete` | Delete only when no service or user account refers to it |
| GET / POST | `/service-types` | `service-types.view` / `service-types.create` | List is not paged: `{"data":[{"id","type","description","services_count"}]}`. Create: `type`, `description?` |
| GET / PUT / DELETE | `/service-types/{id}` | `service-types.view` / `service-types.update` / `service-types.delete` | Delete only when no service uses it |
| GET / POST | `/services` | `services.view` / `services.create` | List: filters `office_id`, `service_type_id`, `charter_year`, `status`; sort `sort_order` (charter order, default), `name`, `charter_year`. Create: `office_id`, `service_type_id`, `name` (unique per office and charter year), `charter_year?` (default this year), `is_active?`, `sort_order?` (default end of the office's list) |
| GET / PUT / DELETE | `/services/{id}` | `services.view` / `services.update` / `services.delete` | Delete only when no feedback refers to it |

### Rules that return 422 for everyone, including System Administrators

- You cannot delete, deactivate or change the roles of your own account.
- The last active System Administrator cannot be deactivated, deleted or
  stripped of that role.
- Only a System Administrator can grant or remove the System
  Administrator role.
- The System Administrator role cannot be renamed, deleted or have its
  permissions changed (it always has all of them).
- A role still assigned to users cannot be deleted.
- Catalog permissions (`is_protected`) cannot be renamed or deleted; their
  description can be edited.
- An office, service type or service still in use cannot be deleted;
  deactivate it instead.

### Office limit

A user with an assigned office who is not a System Administrator only sees
and changes that office's services: another office's service returns 404,
and creating a service in (or moving one to) another office returns 422.
Such a user can also only assign accounts to their own office.

### Importing a charter's services

`php artisan csmf:import-services <file> [--year=] [--dry-run] [--retire-previous]`
loads a `services-YYYY.json` list (format: `database/seeders/data/services-2026.json`).
Safe to re-run: services already present are left unchanged.
`--retire-previous` deactivates active services from earlier charter years.

Every change above, and every sign-in event, is written to the append-only
`audit_logs` table (who, IP, user agent, URL, old and new values; passwords
and two-factor secrets are never recorded).
