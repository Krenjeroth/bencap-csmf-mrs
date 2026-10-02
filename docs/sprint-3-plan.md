# Sprint 3 plan: guest feedback form (web)

**Goal (playbook S3, weeks 7–8):** a client scans the QR code at the
counter and submits a valid ARTA CSM form in under two minutes.

**Decisions this plan builds on:** [ADR 0006](adr/0006-guest-form-fields.md)
(Annex A questions with structured answers; Q8, Q9, Q13, Q18, Q19),
ADR 0003 (UUIDv7 feedback ids, office slugs in URLs) and ADR 0005 (SQD0
outside the overall score, pending Q14). Sprint 2 provides offices with
slugs, active services per office, 19 region options and SQD0–SQD8
(`ARTA-2022`).

## 1. API (`src/api`)

1. **Migrations**
   - `feedback`: as in the playbook's data dictionary (UUIDv7 id,
     `reference_no` `CSM-XXXX-XXXX`, office and service ids with name
     snapshots, `form_version`, `channel` web/kiosk/paper,
     `transaction_date`, `client_type`, `sex`, **`age`** (replaces
     `age_group`), `region_id`, `cc1`–`cc3`, `suggestions`, `email`,
     `email_consent`, `client_uuid`, `ip_hash`, `user_agent`,
     `submitted_at`, void columns). CHECK constraints for the coded
     columns; foreign keys RESTRICT.
   - `feedback_answers`: one row per SQD answer, `rating` 1–5 or NULL
     (N/A), unique per feedback and question.
   - The kiosk and paper columns (`kiosk_device_id`, `encoded_by`) are
     added with their sprints (7 and 4).
2. **Public endpoints** (no sign-in, `throttle:public-read`)
   - `GET /api/v1/public/offices/{slug}`: the form header; 404 for an
     unknown or inactive office.
   - `GET /api/v1/public/offices/{slug}/services`: active services.
   - `GET /api/v1/public/form-definition`: client types, sexes, regions,
     CC1–CC3 with the skip rule, the SQD scale and questions, and a signed
     `form_token` with its issue time.
3. **`POST /api/v1/public/feedback`** (`throttle:feedback-submit`: 10 per
   minute and 50 per day per IP)
   - `StoreFeedbackRequest`: the service belongs to the office and is
     active; date not in the future and at most 30 days back; age 10–120
     or empty; `cc1 = 4` forces `cc2 = 5` and `cc3 = 4`; all nine SQD
     answered (N/A counts); suggestions at most 2,000 characters; email
     needs the consent box.
   - Abuse controls: honeypot field, minimum fill time of 8 seconds from
     the `form_token`, `client_uuid` idempotency (a repeat returns 200 with
     the same reference number), IP stored only as an HMAC (new
     `FEEDBACK_IP_HASH_KEY` in `.env.example`).
   - `FeedbackSubmissionService`: one transaction, name snapshots, random
     reference number, `201 {"reference_no"}`.
4. **Retention (Q18):** a scheduled command that clears `email` and
   `ip_hash` after 1 year and deletes feedback older than 5 years; the
   privacy notice states both periods.
5. **Docs:** public endpoints in `docs/api/README.md`.

## 2. Web (`src/web`)

1. **Public layout** with no navigation or sign-in, and the
   `/f/[office]` page built from the form definition:
   - office name header; service picker; date; client type; sex; age;
     region; CC1–CC3 with the skip rule (CC2 and CC3 hidden when CC1 is 4);
     the SQD grid as radio groups with face icons and text labels;
     suggestions; email with the Data Privacy Act consent box.
   - works at 400 px wide and by keyboard alone.
2. **Thank-you page** showing the reference number (Annex A's Control No.).
3. **QR poster:** a "Download QR poster" action per office on the Offices
   screen, a PNG generated in the browser (new dependency: `qrcode`).
4. **i18n (Q19):** every guest-facing string in an English locale file
   (new dependency: `@nuxtjs/i18n`).

## 3. Tests

- **API (Pest):** happy path 201; repeated `client_uuid` returns 200 with
  the same id; the CC skip rule and its 422; a service from another office
  and an inactive office return 422; today accepted, tomorrow and 31 days
  ago rejected; age 9 and 121 rejected, 10 and 120 accepted; honeypot
  filled and a submit under 8 seconds return 422; the 11th submit in a
  minute returns 429; an XSS payload in suggestions is stored as text;
  retention command clears and deletes at the right ages.
- **Web (Vitest):** form rendering from the definition, the skip rule, the
  consent rule, the thank-you page.
- **End-to-end (Playwright, new `tests/e2e`):** the full form at 400 px
  and on desktop in Chromium, Firefox and WebKit; keyboard-only
  completion; Lighthouse accessibility at least 95. Added to CI.

## 4. Exit criteria

- A pilot office's QR code produces stored, valid feedback.
- Q8, Q9, Q13, Q18 and Q19 answered (done, ADR 0006).

## 5. Before starting

- Choose the **pilot office** for the exit test.
- Approve the three new dependencies: `qrcode`, `@nuxtjs/i18n`, and
  Playwright for the end-to-end suite.
- Confirm the privacy notice wording on the form, which names the
  Provincial Government as data controller and states the retention
  periods.

Suggested commits (playbook): `feat(api): public feedback submission`,
`feat(web): guest csm form`, `feat(web): office qr poster`.
