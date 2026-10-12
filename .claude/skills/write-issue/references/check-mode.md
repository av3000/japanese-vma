# Check mode: scoring an issue

Use this when asked to check, triage or "make ready" an existing issue, or when a bug report arrives from someone else. The rubric is adapted from other references (a private skills collection).

## 1. Read it

`gh issue view <n> --comments`. For an epic, read every sub-issue too.

## 2. Score three dimensions, 1 to 5

| Score | Clarity: could a developer tell what to do without asking? | Completeness: is everything needed to build and verify it present? | Testability: can "done" be checked? |
| --- | --- | --- | --- |
| 1 | No objective, contradictory, or unintelligible | Missing what, where, or how to reproduce | No way to verify |
| 2 | Vague objective, critical details missing | Problem stated, but no steps or expected behaviour | Only "works correctly" or "looks right" |
| 3 | Core objective clear, some ambiguity | Core information present, gaps can be inferred | Expected behaviour stated, verification unclear |
| 4 | Clear objective, minor details to confirm | All major information present | Main scenario verifiable |
| 5 | Unambiguous, all context provided | Complete: for bugs, what, where, expected vs actual, steps | Explicit criteria with clear verification |

Clarity red flags: "fix", "broken" or "doesn't work" without specifics; "it" with no subject; contradictions.

## 3. Sort the open questions

- **Blocking** (any one blocks): what the expected behaviour is; how to reproduce it; which route, component or endpoint is affected; which exact value (text, colour, count) is wanted when it can't be inferred.
- **Nice-to-have** (never blocks on its own): since when; which browser or device; console errors; whether it affects everyone. Many nice-to-have gaps do lower Completeness.

## 4. Verdict

| Verdict | Condition | Label | Comment to post |
| --- | --- | --- | --- |
| **Ready** | Every score ≥ 4, no blocking questions | `ready-for-agent` (or `ready-for-human`) | A short summary: problem, expected outcome |
| **Borderline** | Every score ≥ 3, at least one 3, no blocking questions | `ready-for-agent` (or `ready-for-human`) | Summary, then the nice-to-have questions marked optional |
| **Blocked** | Any score < 3, or any blocking question | `needs-info` | **Required** questions first, then **Helpful** ones |

A Ready or Borderline issue still needs acceptance criteria and an Out line before it gets `ready-for-agent`. If the issue lacks them but the text implies them, propose them (derived from the issue's own words) as part of the edited body.

## 5. Report and propose

Show the scores, the questions, the verdict, and the proposed edited body (existing text unchanged except the sections being added). Post the comment, change labels and edit the body only after the user confirms.

## Worked examples from this repo

- **#458 `[UI-DETAIL-01]` Filter the kanji and word indexes by catalogue.** Problem with numbers (a 500-item catalogue in one payload), exact change (validated filter, visibility rules per viewer, type check returning 422), tests listed case by case, acceptance-criteria checklist. Clarity 5, Completeness 5, Testability 5: **Ready**.
- **#497 Expose publicity as a generated `PublicityStatus` enum.** Clear Problem, Change and Notes, but no acceptance criteria and no definition of done. Clarity 4, Completeness 3, Testability 2: **Blocked** on testability. Propose criteria from its own text, for example: "`api.json` documents `publicity` as an enum with named cases", "the generated client exposes `PublicityStatus` and no caller compares raw integers".
