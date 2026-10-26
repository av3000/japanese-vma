# Anki: Audit

> **Category:** General open-source flashcards with a research-backed scheduler
> **Researched:** 2026-10-02 from the Anki manual (docs.ankiweb.net)

## Snapshot

| Item | Value | Source |
|---|---|---|
| Principles | Active recall ("The act of recalling something strengthens the memory") and spaced repetition (Ebbinghaus, SuperMemo) | [1] |
| Scheduler | FSRS, an alternative to SM-2, available since Anki 23.10 (also AnkiMobile and AnkiWeb) | [2] |
| Desired retention | Default 90%; "above 97% the workload can be overwhelming" | [2] |

## Feature assortment

- **Learners author their own cards** (any content).
- **FSRS** learns from the learner's review history to predict forgetting, and avoids SM-2's "ease hell" [2].
- **Deck options:** new cards per day, maximum reviews per day, learning steps (keep them under a day with FSRS), leech threshold [2].

## Strengths

- **The scheduler is the state of the art and open.** FSRS is the strongest freely available algorithm for this job.
- **Desired retention is one understandable knob.** "Remember 90% of cards when they come back" makes the trade-off between time and memory explicit [2].
- **Total flexibility** in what a card can be.

## Weaknesses

- **Blank-slate start.** Learners must find or build decks.
- **Complex settings.** Learning steps, ease and leeches confuse beginners.
- **No content.** It never shows the word in a real text.

## Lessons for Japanese VMA

- **Do not invent a scheduler.** For flashcards Phase 2 (SRS over `study_attempts`), use FSRS. The Open Spaced Repetition organisation maintains `ts-fsrs` (TypeScript), and community PHP packages exist on Packagist (`devdch/fsrs`, `scottlaurent/fsrs`) [3]. Check licence and maturity before picking one. A WaniKani-style fixed ladder is simpler to explain but schedules worse.
- **Expose one setting:** desired retention, defaulting to 90%, plus a daily new-item cap. Hide the rest.
- **Export to Anki** (CSV/TSV a learner can import) lowers lock-in fear and costs little. Catalogues already have PDF export.

## Sources

1. Anki manual: Background, https://docs.ankiweb.net/background.html
2. Anki manual: Deck Options (FSRS), https://docs.ankiweb.net/deck-options.html
3. Open Spaced Repetition organisation, https://github.com/open-spaced-repetition ; awesome-fsrs list, https://github.com/open-spaced-repetition/awesome-fsrs ; PHP packages, https://packagist.org/packages/devdch/fsrs , https://packagist.org/packages/scottlaurent/fsrs
</content>
</invoke>
