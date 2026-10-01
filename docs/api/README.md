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
| GET | `/users` | `users.view` | Filters `role_id`, `status=active\|inactive`; sort `name`, `email`, `created_at`, `last_login_at` |
| POST | `/users` | `users.create` | `name`, `email`, `role_ids[]`, `is_active?` → `201 {"data": user, "temporary_password"}` (shown once) |
| GET | `/users/{uuid}` | `users.view` | |
| PUT | `/users/{uuid}` | `users.update` | `name?`, `email?`, `is_active?`; deactivating signs the user out everywhere |
| DELETE | `/users/{uuid}` | `users.delete` | Soft delete; `204` |
| PUT | `/users/{uuid}/roles` | `users.update` | `role_ids[]` |
| POST | `/users/{uuid}/reset-password` | `users.update` | `reset_two_factor?` → `{"temporary_password"}`; signs the user out everywhere |
| GET / POST | `/roles` | `roles.view` / `roles.create` | Create: `title`, `description?`, `permission_ids[]?` |
| GET / PUT / DELETE | `/roles/{id}` | `roles.view` / `roles.update` / `roles.delete` | |
| PUT | `/roles/{id}/permissions` | `roles.update` | `permission_ids[]` |
| GET / POST | `/permissions` | `permissions.view` / `permissions.create` | Create: `title` (`resource.action`), `description?`; System Administrator gets it automatically |
| GET / PUT / DELETE | `/permissions/{id}` | `permissions.view` / `permissions.update` / `permissions.delete` | |

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

Every change above, and every sign-in event, is written to the append-only
`audit_logs` table (who, IP, user agent, URL, old and new values; passwords
and two-factor secrets are never recorded).
