# ADR 0004: Role-based access control

- Status: Accepted
- Date: 2026-10-01

## Context

The specification defines `roles` (`title`, timestamps), `permissions`
(`title`, timestamps), and two pivots: `role_user` (accepting the UUID
user id) and `permission_role`. The author confirmed on 2026-10-01:

- `permission_role` columns are `(permission_id, role_id)`.
- Permission titles use `resource.action`, for example `dashboard.view`,
  `users.view`, `users.create`.
- **System Administrator** has every permission.
- **Admin** is a separate role, scoped to the user's own office: it sees
  the dashboard, feedback and reports for that office and encodes paper
  forms, and does not manage users, roles, offices or services.
- Only the System Administrator resets passwords in v1 (no outbound
  email). Two-factor login is mandatory for System Administrator and
  optional for Admin.

`spatie/laravel-permission` was considered. Its tables
(`model_has_roles`, `role_has_permissions`) and `name` column do not match
the specified schema.

## Decision

- Implement RBAC in-house with exactly the specified tables and columns:
  Eloquent `belongsToMany` relations, Laravel Gates and Policies.
- `roles.is_system` marks System Administrator. `Gate::before` grants a
  user with an `is_system` role every ability. The role's permission rows
  are also seeded, so the role editor shows the truth.
- Office scoping for Admin is enforced in Policies and query scopes on
  `users.office_id`, not by permissions alone.
- Seeders upsert roles, the permission catalog and the role-permission
  matrix by title, so they are safe to re-run.

## Consequences

- Every admin route needs a Policy check, and Sprint 1 ships an
  authorization matrix test (each endpoint × each role).
- Adding a permission in the UI has no effect until code checks it.
  Catalog permissions referenced in code are marked protected.
