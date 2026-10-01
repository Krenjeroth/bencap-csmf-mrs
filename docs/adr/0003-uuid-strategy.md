# ADR 0003: Primary key strategy (UUID vs auto-increment)

- Status: Accepted
- Date: 2026-10-01

## Context

The specification requires UUIDs for `users` ("Make sure it uses uuid")
and for `role_user.user_id`, and asks for a recommendation for every other
table. The guest feedback form is public and anonymous, so any id it
exposes can be enumerated by anyone.

## Decision

| Table | Key | Reason |
|---|---|---|
| `users` | UUID | Required by the specification; prevents account enumeration |
| `feedback` | UUID | Created by anonymous guests; sequential ids would reveal each office's submission volume |
| `kiosk_devices` | UUID | Sanctum token owner alongside users |
| `offices`, `services`, `service_types`, `regions`, `sqd_questions` | Auto-increment BIGINT | Public lookup data already listed on the guest form; small keys keep the large feedback indexes compact |
| `roles`, `permissions` | Auto-increment BIGINT | Admin-only, few rows |
| `feedback_answers`, `feedback_issuances`, `audit_logs` | Auto-increment BIGINT | Internal child rows never addressed from outside |
| `role_user`, `permission_role` | Composite primary key | Pivot tables need no surrogate key |

- UUIDs come from Laravel's `HasUuids` trait, which generates time-ordered
  UUIDv7 values, so inserts stay in index order.
- Public URLs for offices use a slug (`/f/pho`), not the numeric id.
- Each feedback record also gets a random, human-readable reference number
  (`CSM-XXXX-XXXX`) for the thank-you screen.

## Consequences

- Laravel's default `sessions.user_id` (`foreignId`) and
  `personal_access_tokens.tokenable` (`morphs`) must change to
  `foreignUuid` and `uuidMorphs` in Sprint 1.
- UUID columns are `CHAR(36)` (see ADR 0002).
