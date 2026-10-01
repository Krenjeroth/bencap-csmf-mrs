# ADR 0005: ARTA satisfaction scoring formula

- Status: Proposed (awaiting playbook Q14, before Sprint 5)
- Date: 2026-10-01

## Context

The summary report replaces the manual tally in `CSMF blank form.xlsx`.
That template maps a percentage to a rating (Poor, Fair, Satisfactory,
Very Satisfactory, Outstanding) in cells L39–M80, with two defects: cell
L48 reads `68` instead of `0.68`, and 0.51–0.59 has no rating.

## Decision

- Each SQD answer is stored as 1–5 (Strongly Disagree to Strongly Agree)
  or `NULL` for Not Applicable.
- Score for one question = (Agree + Strongly Agree) ÷ (Total − Not
  Applicable) × 100.
- Overall score pools SQD1–SQD8 the same way; SQD0 is reported on its own
  line. This basis is pending confirmation (Q14).
- Scores round half-up to 2 decimals, and the rating is taken from the
  rounded value, so a printed 95.00 is always Outstanding.
- Rating bands: Outstanding ≥ 95, Very Satisfactory ≥ 90, Satisfactory
  ≥ 80, Fair ≥ 60, Poor < 60.
- A question with no rated answers has no score (shown as "No responses"),
  never 0%.

## Consequences

- Scoring lives in one pure class (`SatisfactionScoreCalculator`) with
  band-edge tests; dashboard and report both use it.
- Changing a band is a change request with updated tests, not a setting.
