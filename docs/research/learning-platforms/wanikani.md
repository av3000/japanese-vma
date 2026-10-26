# WaniKani: Audit

> **Category:** Japanese kanji and vocabulary, fixed curriculum with SRS
> **Researched:** 2026-10-02 from wanikani.com, knowledge.wanikani.com and docs.api.wanikani.com

## Snapshot

| Item | Value | Source |
|---|---|---|
| Content | "over 2,000 kanji" and "over 6,000 Japanese words, all carefully validated by a human", plus radicals | [1] |
| Structure | 60 levels; levels 1–3 free, 4–60 premium | [4] |
| Level-up rule | Get 90% of the current level's kanji to Guru | [4] |
| Price | USD 9.00/month, USD 89.00/year, USD 299.00 lifetime | [3] |
| Skipping | "There is no way to speed up or skip content on WaniKani." | [5] |
| API | Bearer-token REST API; reads plus writes for reviews, study materials and assignment starts | [6] |

## Feature assortment

- **Radicals → kanji → vocabulary.** Radicals are taught as "building blocks for learning kanji", and kanji unlock vocabulary [1], [4].
- **A mnemonic for every item** [1].
- **Lessons, then reviews.** New items are taught in lessons, then drilled in reviews [1].
- **Nine-stage SRS ladder** grouped as Apprentice → Guru → Master → Enlightened → Burned. Intervals run 4 h, 8 h, 1 d, 2 d, 1 w, 2 w, 1 month and 4 months, and levels 1–2 run faster [2].
- **Wrong-answer penalty.** `new_stage = current - ceil(incorrect/2) * factor`, where the factor is 2 at stage ≥ 5 and 1 below that, with stage 1 as the floor [2].
- **Unlocking by mastery.** A radical at Guru unlocks the kanji that use it, and a kanji at Guru unlocks its vocabulary [4].
- **Public API.** Subjects, assignments, reviews, review statistics, study materials (user notes and synonyms), voice actors [6].

## Strengths

- **Clear progress.** Every day the learner knows exactly what to do: "do your reviews, then your lessons". There is no planning burden.
- **Dependency-ordered content.** Components come before the characters that use them, so each new kanji is built from parts already known. That cuts the effort of memorising it.
- **The penalty rule is simple and explainable.** Items you have learned well drop further when you miss them, which surfaces items that only looked learned.
- **An API ecosystem.** Because reviews and study materials can be written through the API, third-party apps and userscripts grew around WaniKani. That extends the product at no cost to the maker.

## Weaknesses

- **No skipping.** Someone who already knows N4 still starts at level 1 (sourced, [5]). This is the most common reason intermediate learners bounce off fixed curricula (analysis).
- **Narrow scope.** It teaches no grammar and offers no reading in context, so learners pair it with Bunpro and a reader.
- **Fixed pace.** Unlocks are gated by the SRS timers, so a motivated learner cannot go faster.
- **The made-up radical names** only make sense inside WaniKani's own mnemonics. The source itself says this is why skipping is impossible [5].

## Lessons for Japanese VMA

- **Kanji detail should show "built from" and "used in".** Japanese VMA already stores kanji–radical relationships, so it can present the dependency idea without forcing a fixed path.
- **Use unlocking as a suggestion, not a gate.** For example: "you know 4 of the 5 radicals in 働, so try it next".
- **User notes per item.** WaniKani's study materials let a learner attach their own meaning note, reading note and synonyms to an item. A small Japanese VMA equivalent is a personal note on any kanji or word.
- **Do not copy the no-skip rule.** Let learners bulk-mark items as known instead. The Known* catalogue types already exist for this.
- **A personal-token API** is a later, cheap multiplier once the v1 contracts settle.

## Sources

1. WaniKani home page, https://www.wanikani.com/
2. WaniKani's SRS Stages, https://knowledge.wanikani.com/wanikani/srs-stages/
3. Subscription Plans, https://knowledge.wanikani.com/account-and-membership/payment-and-billing/subscription-plans/
4. Is WaniKani Free? / How Do I Level Up?, https://knowledge.wanikani.com/getting-started/payment-and-billing/wanikani/wanikani-free/ , https://knowledge.wanikani.com/wanikani/getting-started/level-up/
5. Skipping Content, https://knowledge.wanikani.com/wanikani/skip-content/
6. WaniKani API Reference, https://docs.api.wanikani.com/20170710/
</content>
</invoke>
