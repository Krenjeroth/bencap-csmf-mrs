# ADR 0006: Guest form fields follow Annex A with structured answers

- Status: Accepted
- Date: 2026-10-02

## Context

The playbook's form design (1.7) was based on the tally sheet
(`CSMF blank form.xlsx`). The 2026 Citizen's Charter's Annex A, the
Provincial Government of Benguet's official Client Satisfaction
Measurement Form, asks some questions differently
(`docs/citizens-charter-review.md`, "The official CSM form"):

- a blank **Control No.** at the top;
- **client type** with one Government option ("Employee or another agency");
- **date**, **age**, **region of residence** and **service availed** as
  free text;
- no 5-to-1 "Very Satisfied … Very Dissatisfied" scale (`Picture.jpg`).

The tally sheet, which the reports must reproduce, counts age in brackets,
splits Government into Employee and Other Agency, and lists region options
that are office levels ("Central Office", "Regional Office 1" …), not places
of residence. Free-text answers cannot be counted.

Open playbook questions answered on 2026-10-02 by the author (who also acts
as Data Protection Officer): Q8, Q9, Q13, Q18, Q19.

## Decision

The guest form asks Annex A's questions, with structured answers wherever
a report counts them. Reports keep the tally sheet's layout.

| Field | Decision |
|---|---|
| Control No. | The random reference number `CSM-XXXX-XXXX` (playbook 1.10), shown on the thank-you screen. Not sequential, so it does not reveal an office's volume (ADR 0003). |
| Client type | Citizen · Business · Government – Employee · Government – Other agency. Reports can merge the two Government rows into Annex A's single option. |
| Date of transaction | Date picker, default today; not in the future, at most 30 days back. |
| Sex | Male · Female · not answered. |
| Age | Whole number, optional (`feedback.age TINYINT UNSIGNED NULL`, 10–120), replacing the planned `age_group`. Reports group it into the tally sheet's brackets (19 or lower, 20–34, 35–49, 50–64, 65 or higher, Did not specify). |
| Region of residence | Pick list of the 18 Philippine regions (CAR first, then PSA order, including the Negros Island Region re-created by RA 12000 in 2024), plus "Did not specify". Optional. **Q9.** |
| Service availed | Pick from the office's active services. |
| CC1–CC3, SQD0–SQD8, suggestions, email | Unchanged from the playbook. |
| 5-to-1 rating scale | **Not on the form (Q8).** It is not on Annex A or in the ARTA method; SQD0 already measures overall satisfaction. |

Other answers:

- **Q13, how guests reach the form:** a printed QR poster per office
  pointing to `/f/{slug}`, plus the kiosk (Sprint 7).
- **Q18, retention:** feedback is kept 5 years; `email` and `ip_hash` are
  cleared 1 year after submission. Confirmed by the author as DPO.
- **Q19, languages:** English only in v1; every string lives in i18n files
  so Filipino can be added without code changes.

## Consequences

- `feedback` stores `age` instead of `age_group`; the report maps ages to
  brackets in one place, so the brackets can change without new data.
- The region list is seed data (`RegionSeeder`); the tally sheet's office
  levels are not used.
- Retention needs a scheduled job (pruning feedback older than 5 years,
  clearing email and IP hash after 1 year) and a privacy notice on the form
  that states both periods.
- The guest form has no overall 1-to-5 rating; adding one later is a
  single nullable column.
