# Suggestions for Japanese VMA

> **Status:** Research recommendations, not accepted scope. Each suggestion would become a GitHub issue if accepted.
> **Researched:** 2026-10-02
> **Built from:** the platform audits in this folder, and the `develop` branch plus `feature/issue-413-flashcards` as inspected on 2026-10-02

## How to read this

Each suggestion answers the same questions, in the same order:

1. **What it is**, in plain words.
2. **Who already does it.** Links to the audits, with the evidence there.
3. **Why it works.** The learning mechanism, named and explained.
4. **What Japanese VMA already has** to build it on.
5. **What is missing**, on both backend and frontend.
6. **Size.** S = a slice or two, M = a small epic, L = a large epic.
7. **The other side.** The best argument against doing it.

The suggestions are ranked by value for effort, given what already exists. Tier 1 builds directly on finished work. Tier 3 waits on planned epics.

## The learning mechanisms, defined once

These ideas recur in the "why it works" sections. Each one is defined here so the later sections can just name it.

- **Retrieval practice.** Pulling an answer out of your memory strengthens it more than re-reading it does. This is why flashcards work, and why typing an answer beats picking one. Anki's manual: "The act of recalling something strengthens the memory" ([anki.md](./anki.md)).
- **Spacing.** Reviews spread over days beat the same number of reviews crammed into one session. A *scheduler* is the code that decides the next review date for each item; SRS (spaced repetition system) is the general name. The forgetting it fights is fast: Anki cites roughly 75% forgotten within 48 hours without review.
- **Comprehensible input, and "i+1".** People acquire a language by understanding messages slightly above their level. "i+1" means "what you know, plus one new thing". A sentence with exactly one unknown word is i+1. A text where 60% of the words are unknown is not input, it is a puzzle.
- **Encoding in context.** A word learned inside a sentence, with a situation and ideally audio, has more retrieval cues ("I met it in the Kyoto article") than a bare word on a card.
- **Habit and loss aversion.** A daily cue plus a visible streak turns study into routine. Losing a long streak feels bad, which keeps people going but can also make them quit when it breaks. That is why forgiveness, such as a streak freeze, matters. Duolingo publishes the numbers ([duolingo.md](./duolingo.md)).
- **Desirable difficulty.** Practice that feels harder (typing instead of choosing, mixed items instead of blocks) produces better long-term memory, as long as it is still achievable.

## Tier 1: Build on what already exists

### S1. A learner knowledge model: "what do I know?"

**What it is.** One backend answer to "which kanji, words and radicals does this learner know, and how well?" Every personalised feature below reads it.

**Who already does it.** Renshuu: "everything is built around one central dictionary that tracks the individual's knowledge". WaniKani uses SRS stages. Kanshudo uses a 0–4 mastery scale. LingQ and Migaku track known/learning/unknown ([renshuu.md](./renshuu.md), [kanshudo.md](./kanshudo.md), [migaku.md](./migaku.md)).

**Why it works.** It is not a learning mechanism itself. It is the precondition for comprehensible input (pick texts at the right level) and for spacing (know what is due). Without it, every personalised feature has to guess.

**What Japanese VMA already has.**
- The `KNOWN_RADICALS`, `KNOWN_KANJIS`, `KNOWN_WORDS` and `KNOWN_SENTENCES` catalogue types (`processor-api/app/Domain/Shared/Enums/CatalogueType.php`) are a manual "I know this" list.
- `ViewerCatalogueStateService` (`processor-api/app/Application/Catalogues/Services/ViewerCatalogueStateService.php`) already works out `is_saved` and `is_known` for a batch of item IDs. The kanji and word list and detail resources expose it as `viewer_catalogue_state`. The article word and kanji lists do not yet. **A boolean version of this suggestion therefore already exists.** The new parts are adding flashcard evidence and statuses beyond yes/no.
- The flashcards branch stores study sessions and attempts (FC-BE-03, #418), which is a record of real recall evidence.

**What is missing.**
- A read service in the `Study` module, for example `LearnerKnowledgeQuery`, that combines both sources into one status per item. The status could be `new`, `seen`, `learning` or `known`: "known" if the item is in a Known catalogue or its recent recall is strong, "learning" if it has attempts.
- One batch endpoint: given item IDs, return their statuses. The reader and the article list need this to stay fast.
- A "mark these as known" bulk action, so an intermediate learner is not treated as a beginner. WaniKani's no-skip rule is the counter-example ([wanikani.md](./wanikani.md)).

**Size.** M.

**The other side.** The Known catalogues are already a model; why add a second? Because a catalogue is a list the learner curates by hand, while knowledge is evidence that changes as they forget. Keeping them separate but combined in one query preserves the learner's explicit choice ("I know this, never quiz me") and adds the evidence. The risk is two sources of truth that disagree. The rule to write down: an explicit Known always wins.

### S2. Real scheduling for flashcards (FSRS)

**What it is.** Today the flashcards feature grades answers but does not decide when an item comes back. Add a scheduler, so each learner has a "due today" queue.

**Who already does it.** Everyone except Jisho. Anki ships FSRS, WaniKani a fixed 9-stage ladder, Bunpro a 12-step ladder plus ghost reviews, jpdb a machine-learned scheduler ([anki.md](./anki.md), [wanikani.md](./wanikani.md), [bunpro.md](./bunpro.md), [jpdb.md](./jpdb.md)).

**Why it works.** Spacing plus retrieval practice. The scheduler shows an item just before the learner would forget it, which makes the recall effortful (desirable difficulty) without failing.

**What Japanese VMA already has.** `study_attempts` (FC-BE-03), and the "SRS over study_attempts" Phase 2 candidate recorded in epic #413.

**What is missing.**
- A per-learner, per-item review-state table (stability, difficulty, due date, lapses). FSRS needs those fields.
- A scheduler library. `ts-fsrs` is maintained by the Open Spaced Repetition organisation, and PHP ports exist on Packagist. Running the scheduler on the backend keeps one source of truth, which argues for PHP if a port is mature. See [anki.md](./anki.md).
- A "Reviews due" entry point on the dashboard, and a session mode that pulls due items across catalogues.
- **Leech handling.** After N misses, give the item a short extra cycle (Bunpro's ghost reviews) instead of only resetting it.
- One learner-facing setting: desired retention (default 90%), plus a daily new-item cap.

**Size.** M.

**The other side.** A fixed ladder (4 h → 8 h → 1 d → 2 d → 1 w …) is easier to build, test and explain, and WaniKani succeeds with one. FSRS is a dependency with parameters most people cannot inspect. A reasonable split: ship the fixed ladder first behind a `Scheduler` interface, and swap in FSRS later. The interface is the part worth getting right now.

### S3. Article coverage and difficulty

**What it is.** Show each article card a line like "N4 · you know 82% of its words · 14 new words", and let learners sort or filter articles by it.

**Who already does it.** jpdb recommends media by vocabulary level. Migaku shows a comprehension score per piece of content. LingQ counts new words per lesson ([jpdb.md](./jpdb.md), [migaku.md](./migaku.md), [lingq.md](./lingq.md)).

**Why it works.** Comprehensible input. Hu & Nation (2000) found that about 98% known words was the desirable threshold for reading fiction without help, and 95% only the minimum. Nation (2006) used the 98% figure to estimate vocabulary sizes. The original study had 66 participants, and it measured English readers. Treat the figure as a guide, not a rule. Showing coverage lets the learner choose texts near that zone instead of bouncing off ones that are too hard. Sources: Nation, "How large a vocabulary is needed for reading and listening?" (2006), https://www.wgtn.ac.nz/__data/assets/pdf_file/0018/1626120/2006-How-large-a-vocab.pdf ; replication abstract noting the 66-participant base, https://aila2021.dryfta.com/program/abstract-archive/abstract/public/416/unknown-vocabulary-density-reading-comprehension-and-the-98-coverage-figure-replicating-hu-nation-2000

**What Japanese VMA already has.**
- Every processed article has its extracted words and kanji (the `article_word` and `article_kanji` pivots).
- Words and kanji carry JLPT levels. The word JLPT backfill is #399.

**What is missing.**
- A per-learner coverage read, which comes from S1: the share of the article's words with status `known`.
- An article JLPT profile (the share of words per level). It is the same for every learner, so it can be computed during processing and stored beside the article. That follows ADR "read enrichment lives beside the entity".
- An article-card badge and a "fits my level" filter in the FilterBar.

**Size.** S for the anonymous JLPT profile, M with personal coverage.

**The other side.** Coverage counts dictionary matches. Japanese VMA's extractor matches exact written forms, so conjugated verbs and adjectives may be missed, and the number can overstate or understate difficulty. Label it "approximate", or add a morphological analyser later (see S4).

### S4. An interactive reader: tap-to-look-up and known/unknown colouring

**What it is.** In the article text itself, each recognised word becomes tappable (reading, meaning, JLPT, "add to catalogue"). Optionally, words are tinted by status (new / learning / known).

**Who already does it.** Satori Reader (contextual definitions), LingQ (highlighting unknown words), Migaku (known/learning/unknown colouring on any page) ([satori-reader.md](./satori-reader.md), [lingq.md](./lingq.md), [migaku.md](./migaku.md)).

**Why it works.** It keeps the learner reading. A dictionary lookup that leaves the page breaks comprehension. The colouring makes progress visible on the page: last month's yellow words are white now. That reward comes from the reading itself, not from points.

**What Japanese VMA already has.**
- `WordExtractionService` (`processor-api/app/Application/JapaneseMaterial/Words/Services/WordExtractionService.php`) walks the text and keeps the longest dictionary match at each position.
- The article detail page shows the extracted words and kanji below the text (`client/src/routes/ArticleDetails/`).

**What is missing.**
- **Positions.** The extractor knows each match's start and length while it walks, but returns only word IDs, and `article_word` stores no offset. Recording `(start, length, word_id)` per occurrence during processing is a small backend change. One caveat: the walk runs on normalised text, so offsets must refer to the stored text or the normalisation must be reversible.
- A reader component that splits the article text into segments from those offsets, renders a popover per word, and reuses the existing add-to-catalogue widget.
- Colouring needs a known/unknown flag per article word. A boolean version only needs `ViewerCatalogueStateService` added to the article word and kanji lists, the same way `WordController` uses it. Full S1 adds "learning" as a third state.
- Accessibility: popovers must be keyboard-reachable, and colour must not be the only cue. Use underline styles too, per your "cues not colour-only" rule.

**Size.** M (offsets plus popover); colouring adds S once S1 exists.

**The other side.** Exact-form matching means 食べました will not resolve to 食べる. A Japanese tokenizer (MeCab/Sudachi on the backend, kuromoji in the browser) fixes that, but adds a dependency and dictionary licensing to check. Shipping exact-form first is honest if unmatched text simply stays plain. The bigger risk is a reader so busy with tints that it is harder to read. Make the colouring a toggle that is off by default for beginners.

### S5. Sentence mining: save a word with the sentence it came from

**What it is.** When a learner adds a word from an article, keep the sentence and the article link. Flashcards then show that sentence. Add one button per article: "Study this article's new words".

**Who already does it.** Migaku (one-click cards with sentence, audio, screenshot), Satori Reader (contextual flashcards with the story audio), MaruMori ("automatic homework based on what you just learned") ([migaku.md](./migaku.md), [satori-reader.md](./satori-reader.md), [marumori.md](./marumori.md)).

**Why it works.** Encoding in context: the sentence is a retrieval cue. Retrieval practice right after reading catches the words while the memory is fresh, before the 48-hour drop.

**What Japanese VMA already has.** Catalogues, the add-to-catalogue widget, and a flashcards route that studies any catalogue (#416).

**What is missing.**
- An optional `context` on a catalogue item (sentence text plus source article UUID). Catalogue items are currently pure references.
- A "new words from this article" deck: either a temporary deck built from S1 statuses, or a one-click "create catalogue from unknown words".

**Size.** S–M.

**The other side.** Storing sentence text on catalogue items duplicates article content, and goes stale if the article is edited or deleted. Storing only the article UUID plus offsets avoids duplication but breaks on edits. Pick "copy the sentence"; it is what the learner saw.

## Tier 2: High value, a bit more to build

### S6. Furigana that adapts to what the learner knows

**What it is.** Show furigana over kanji the learner has not learned, and hide it over kanji they have.

**Who already does it.** Satori Reader ("according to your knowledge"), Renshuu (furigana adjusted to the kanji the learner knows) ([satori-reader.md](./satori-reader.md), [renshuu.md](./renshuu.md)).

**Why it works.** Desirable difficulty, applied gently. Help disappears exactly where it is no longer needed, so the learner keeps recalling known readings without being blocked by unknown ones.

**What Japanese VMA already has.** Furigana on word resources (the generated client models include it), and Known kanji via S1.

**What is missing.** The S4 segmentation (to know which word a kanji sits in), plus a ruby-rendering rule. Three modes: always, never, by-knowledge.

**Size.** S once S4 exists.

**The other side.** A word's reading is not always the sum of its kanji readings (for example 今日). Decide per word, not per kanji: show furigana unless the learner knows the word, or knows all of its kanji *and* the word's reading is regular. The simpler "per word known" rule is a fine first version.

### S7. Sentence fill-in-the-blank practice (i+1 cloze)

**What it is.** A flashcards mode that shows a real sentence with one word blanked, and asks the learner to type or choose it. Prefer sentences where every other word is known.

**Who already does it.** Bunpro (typed answers in sentences), Clozemaster (frequency-ordered cloze), jpdb ("automatic i+1 sentence cards") ([bunpro.md](./bunpro.md), [clozemaster.md](./clozemaster.md), [jpdb.md](./jpdb.md)).

**Why it works.** Retrieval practice in context, and production rather than recognition. The i+1 filter keeps the sentence comprehensible, so the only difficulty is the target word.

**What Japanese VMA already has.** Tatoeba sentences linked to words, flashcard typed input and options modes, and "sentence cloze" already listed as an #413 Phase 2 candidate.

**What is missing.** A sentence-picker query that ranks a word's sentences by how many other words the learner knows (from S1), and a cloze prompt component.

**Size.** M.

**The other side.** Tatoeba quality varies, and a blank can have several valid answers (synonyms, particles). Accept any form that resolves to the same dictionary entry, and let learners flag bad sentences.

### S8. A forgiving daily-habit loop: streak, heatmap, "due today"

**What it is.** A dashboard panel showing today's due reviews, a calendar heatmap of study days, and a streak that one missed day does not break (a freeze).

**Who already does it.** Duolingo (streaks plus Streak Freeze, with published effects), Satori Reader and MaruMori (heatmaps) ([duolingo.md](./duolingo.md), [satori-reader.md](./satori-reader.md), [marumori.md](./marumori.md)).

**Why it works.** Habit formation. Duolingo reports that learners who reach a 7-day streak are "3.6 times more likely" to complete their course, and that *more* forgiveness (two freezes instead of one) raised daily active learners. Those are correlations and small A/B effects on Duolingo's audience, not laws of nature.

**What Japanese VMA already has.** Study sessions with timestamps (#418) and the Dashboard route. A "dashboard panel" is already listed as an #413 Phase 2 candidate.

**What is missing.** A daily activity rollup (a study day is any completed session or finished article), a heatmap component, freeze rules, and an off switch.

**Size.** S–M.

**The other side.** Streaks can make the streak the goal, and a broken one can make people quit. Renshuu lets learners switch gamification off ([renshuu.md](./renshuu.md)). Do the same, and never punish (no hearts, no lost progress).

### S9. Starter catalogues and "I already know these"

**What it is.** Public, curated catalogues, such as "JLPT N5 kanji", "N5 words" and "Kana", owned by a system user. Add a one-click "mark all as known" and a paste-a-list import for learners coming from elsewhere.

**Who already does it.** Renshuu (lesson packs for textbooks and JLPT), jpdb (21,434 prebuilt decks, Anki import), Migaku ("import words you already know") ([renshuu.md](./renshuu.md), [jpdb.md](./jpdb.md), [migaku.md](./migaku.md)).

**Why it works.** It removes the blank-slate problem that Anki suffers from ([anki.md](./anki.md)), and it makes S1–S7 useful on day one for intermediate learners.

**What Japanese VMA already has.** Catalogues, public visibility, JLPT data, and the system-user pattern planned for content import (IMPORT-01, #405).

**What is missing.** A seeder or console command that builds JLPT catalogues from existing data, a bulk "mark as known" endpoint (S1), and a text-paste import that matches lines to dictionary entries.

**Size.** S–M.

**The other side.** The JLPT stopped publishing its vocabulary, kanji and grammar lists with the 2010 revision (JLPT FAQ, https://www.jlpt.jp/sp/e/faq/), so any "N5 words" list is a community approximation. Say so on the catalogue. Avoid textbook chapter lists copied verbatim (licensing); reference chapters instead, as Bunpro does with page numbers ([bunpro.md](./bunpro.md)).

### S10. A richer kanji page: parts, "words you know", stroke order, data sources

**What it is.** On the kanji detail page (#352): the radicals it is built from, the kanji that use it, "words you know with this kanji", and an animated or stepped stroke-order diagram. Also a site-wide data-sources and attribution page.

**Who already does it.** WaniKani (radical → kanji → vocabulary unlocking), Duolingo (tap a kanji to see "the words you've already learned that use that character"), Jisho (KanjiVG stroke order, radical search, a published attribution page) ([wanikani.md](./wanikani.md), [duolingo.md](./duolingo.md), [jisho.md](./jisho.md)).

**Why it works.** Encoding in context, and building on prior knowledge. A new kanji linked to known parts and known words has many retrieval cues; an isolated character has one.

**What Japanese VMA already has.** Kanji–radical relationships, kanji→words relations, catalogues, and S1.

**What is missing.** KanjiVG data (CC BY-SA 3.0: attribute it, and share adapted diagrams under the same licence), a stroke-order component, a "known words" filter on the related-words list, and the attribution page (Tatoeba is CC-BY, the EDRDG files have their own licence).

**Size.** M.

**The other side.** KanjiVG adds a few thousand SVGs to host or serve. It fits the object-storage work in #235, or lazy-loading per kanji. Writing practice (tracing) is a further step that needs pointer and touch input handling. Defer it.

## Tier 3: After the planned epics land

### S11. "Explain this" through the chat assistant

**What it is.** A "Why?" button after a wrong flashcard answer, and an "Explain this sentence" action in the reader. Both call the planned chat assistant, which answers from Japanese VMA's own dictionary data through tools.

**Who already does it.** Duolingo (Explain My Answer, free since 2026-01-01 in the Japanese course), Kanshudo (AI TeachMe, AI Sentence Correct), Migaku (AI explanations on cards) ([duolingo.md](./duolingo.md), [kanshudo.md](./kanshudo.md), [migaku.md](./migaku.md)).

**Why it works.** Feedback at the moment of error. A learner who knows *why* an answer was wrong corrects the underlying rule, not just the item.

**What Japanese VMA already has.** A full proposed architecture in `docs/architecture/chat-assistant/`: the Laravel AI SDK, read tools over existing services, and approval for writes.

**What is missing.** Two read tools ("explain item given a wrong answer", "explain sentence"), and UI entry points.

**Size.** S once the assistant exists.

**The other side.** Language models are confidently wrong about Japanese nuance often enough to matter. Ground answers in dictionary data, show "AI" clearly, and let learners report bad answers. MaruMori's "NO AI!" claim shows some learners actively value human-only content ([marumori.md](./marumori.md)).

### S12. "Correct my Japanese" community posts

**What it is.** A community post type where a learner writes a few Japanese sentences and others reply with a corrected version, shown as a diff, and can mark a correction "helpful".

**Who already does it.** Busuu: exercises are corrected by fluent speakers; it reports 105+ million community interactions in 2024 ([busuu.md](./busuu.md)).

**Why it works.** Output practice with real feedback, which is the skill every app in this audit is weakest at.

**What Japanese VMA already has.** Community posts, comments and likes (the legacy-to-v1 migration is in progress).

**What is missing.** A post type or flag, a correction comment shape (original vs corrected), a diff display, and moderation hooks.

**Size.** M, and best after the community v1 migration (#351).

**The other side.** It needs a community big enough that posts get answered. Japanese VMA probably does not have that yet. AI correction (S11) can fill the gap until it does. Ship this only if community activity justifies it.

### S13. Personal notes and mnemonics on any item

**What it is.** A private note on any kanji or word (meaning hint, reading hint, mnemonic), optionally shareable, with likes on shared ones.

**Who already does it.** WaniKani (a mnemonic for every item; user study materials through its API), Kanshudo (4,000 kanji with mnemonics), MaruMori ([wanikani.md](./wanikani.md), [kanshudo.md](./kanshudo.md), [marumori.md](./marumori.md)).

**Why it works.** Elaborative encoding: linking a new item to a vivid story gives it a retrieval path. Learner-written mnemonics are the cheap, scalable way for a community platform to get them.

**Size.** S (private notes), M (shared notes with likes; reuses engagement).

**The other side.** Writing mnemonics for thousands of items is WaniKani's moat. Japanese VMA cannot match it with editorial effort. Shared community notes are the only realistic route, and they need moderation.

### S14. Export to Anki, and personal API tokens

**What it is.** A CSV/TSV export of a catalogue that Anki can import, and later personal access tokens for the v1 API.

**Who already does it.** WaniKani (a public API that grew a userscript ecosystem), jpdb and Kanshudo (import/export) ([wanikani.md](./wanikani.md), [jpdb.md](./jpdb.md), [kanshudo.md](./kanshudo.md)).

**Why it works.** It does not help learning directly. It lowers the fear of lock-in, which raises sign-ups, and it lets power users extend the product at no cost to you.

**What Japanese VMA already has.** Catalogue PDF exports, Passport, documented v1 routes.

**Size.** S (export), M (tokens with scopes and rate limits).

**The other side.** A public API freezes contracts that are still migrating from legacy. Export now; tokens after the legacy retirement work finishes.

### S15. Audio for words and sentences

**What it is.** A play button on words, sentences and article paragraphs. Start with the browser's built-in speech synthesis (the Web Speech API), and consider recorded audio later.

**Who already does it.** Satori Reader (native voice actors), WaniKani (voice actors), Bunpro (native audio on example sentences), Migaku and LingQ (text-to-speech) ([satori-reader.md](./satori-reader.md), [bunpro.md](./bunpro.md), [migaku.md](./migaku.md)).

**Why it works.** It adds a second channel (sound) to each item, and links written forms to pronunciation, which reading alone never trains.

**Size.** S (browser TTS), L (recorded or hosted neural audio).

**The other side.** The quality and availability of Japanese browser voices vary by operating system, and pitch accent is often wrong. Label it "computer voice", and do not present it as a pronunciation model.

## Things not to copy

| Pattern | Seen at | Why not |
|---|---|---|
| A fixed path with no skipping | WaniKani: "There is no way to speed up or skip content" | It drives away intermediate learners; Japanese VMA's strength is letting learners choose their material. |
| A free tier so small it is a demo | LingQ: 20 saved words in total | For a portfolio-grade production app, the free tier is the product most people see. Keep reading, lists and basic flashcards free. |
| Gamification you cannot switch off | (Renshuu shows the alternative) | Points and leaderboards push some learners away. Make every game element optional. |
| Unlabelled AI content | (MaruMori markets "NO AI!") | Trust is fragile. Label AI output and imported content; provenance work (#411) already points this way. |

## Pricing landscape, for the membership spec

| Platform | Free | Paid | Source |
|---|---|---|---|
| WaniKani | Levels 1–3 | USD 9/month, 89/year, 299 lifetime | [wanikani.md](./wanikani.md) |
| Bunpro | The whole reference library and 120+ readings; 30-day trial | USD 5/month, 150 lifetime | [bunpro.md](./bunpro.md) |
| MaruMori | Not stated | USD 8.99/month, 6.99/month yearly, 349.99 lifetime | [marumori.md](./marumori.md) |
| Kanshudo | Free account with monthly caps; Pro earnable | USD 9/month, 90/year | [kanshudo.md](./kanshudo.md) |
| Migaku | 10-day trial | USD 96/year standard (promotional at fetch time), 199 lifetime | [migaku.md](./migaku.md) |
| LingQ | 20 LingQs, 5 imports | USD 14.99/month; less on longer plans | [lingq.md](./lingq.md) |
| Satori Reader | First 2 episodes per series; first month free | Not stated on first-party pages read | [satori-reader.md](./satori-reader.md) |
| Jisho | Everything | — | [jisho.md](./jisho.md) |

The pattern that fits Japanese VMA best is Bunpro's: **reference and reading free, personal scheduling and stats paid.** That lines up with S1–S3 and S8 as natural premium candidates, while the dictionary, articles, catalogues and basic flashcards stay free.

## Ranked by frontend weight

This ranking serves one goal: build mostly-frontend features, while each one teaches a contained piece of backend or full-stack work.

**How to read the columns.**
- **FE share** is a rough estimate of how much of the total work is frontend.
- **FE size** and **BE size** use XS / S / M / L. S is about one slice, L is a small epic.
- **BE size counts only the feature's own backend work.** If the feature reuses another suggestion's backend, the dependency column says so.
- **Backend lesson** is the backend or full-stack concept the feature would teach. It is listed only where the backend work is real.

| # | Suggestion | FE share | FE size | Frontend work | BE size | Backend work | Backend lesson | Depends on |
|---|---|---|---|---|---|---|---|---|
| 1 | **S15 Audio (browser TTS)** | ~100% | S | Play buttons calling the Web Speech API; voice detection and a fallback when no Japanese voice exists; "computer voice" label | none | none | none | — |
| 2 | **S6 Adaptive furigana** | ~90% | M | `<ruby>` rendering per word; always / never / by-knowledge modes; the per-word rule for irregular readings (今日); a persisted setting; checking how screen readers announce ruby | XS | Nothing of its own | none | S4's backend slice |
| 3 | **S4 Interactive reader** | ~75% | L | Splitting the text into word segments from offsets; an accessible popover (focus trap, Escape, arrow keys between words, touch); known/unknown styles that are not colour-only; a toggle; keeping long articles fast (memoised segments) | S–M | Record `(start, length, word_id)` in the extractor's walk; a migration; expose offsets on the article resource; add `ViewerCatalogueStateService` to article word lists; reprocess existing articles to backfill offsets | Extending an async job; migrating and backfilling existing data; changing a contract safely (Scramble → Orval) | — |
| 4 | **S8 Habit loop** | ~70% | M | A 53×7 heatmap (CSS grid or SVG) with tooltips, keyboard access and a legend that does not rely on colour; a streak card; a "due today" panel; an off switch | S | One aggregate endpoint: study days per date for the viewer | Aggregation queries; **time zones** (the learner's local day vs UTC is a classic real-world bug) | #418 sessions |
| 5 | **S10 Kanji page** | ~65% | M–L | Stroke-order SVG animation (`stroke-dasharray`) with play / step controls; a built-from and used-in parts panel; a "words you know" filter | S–M | A console command importing KanjiVG; decide whether to store or serve the SVG files; an attribution page | Data-import commands; storage choices; licence compliance (CC BY-SA) | #352 |
| 6 | **S5 Sentence mining** | ~55% | M | Pick the sentence around a tapped word (split on 。！？); extend the bookmark widget; a "study this article's new words" button | S–M | Optional `context` fields on catalogue items (migration, validation, resource); maybe a "catalogue from unknown words" endpoint | Changing a write contract; validation; transactions | S4 |
| 7 | **S12 Correct my Japanese** | ~50% | M | A compose form; a **character-level** diff view (Japanese has no spaces, so word diffs fail); a "helpful" toggle | M | A post type or flag; a correction shape; moderation | Polymorphic engagement; authorisation policies; moderation | Community v1 (#351) |
| 8 | **S13 Personal notes / mnemonics** | ~50% | S–M | An inline editor on detail pages; optimistic save; a shared-notes list | S (private) – M (shared with likes) | A table, model, policy (owner only), FormRequest, Resource, plus engagement for shared notes | **The ideal first full vertical slice**: every layer once, small and with no surprises | — |
| 9 | **S2 Scheduler (FSRS)** | ~55% if the browser runs it, ~30% if the server does | M | Run `ts-fsrs` after each answer (flashcards already grade on the client, per #413); a due-queue session mode; a desired-retention setting | M | A review-state table; an "upsert card state" endpoint; an indexed "due for viewer" query; or the scheduler in PHP behind an interface | A domain service behind an interface; queries driven by an index; choosing whether to trust client data | #418 |
| 10 | **S7 i+1 cloze** | ~40% | S–M | A cloze prompt; answer matching through the existing grading helpers | M | A sentence picker ranking a word's sentences by how many other words the viewer knows | Real SQL design: joins, `NOT EXISTS`, indexes, query plans | S1 (boolean is enough) |
| 11 | **S3 Coverage / difficulty** | ~35% | S | An article-card badge, a "fits my level" filter, an explanation tooltip | S–M (anonymous JLPT profile) / M (personal coverage) | Compute the JLPT profile in the processing job and store it beside the article; a filter parameter; per-viewer coverage | Precompute vs compute on read: ADR 0001 ("read enrichment lives beside the entity") in practice | #399 |
| 12 | **S9 Starter catalogues + "mark as known" + import** | ~30% | S | A bulk action; a paste-a-list dialog previewing the matches | M | A seeder or command that is safe to re-run; a system user; a bulk insert endpoint; matching pasted text by reusing the extractor | Console commands; idempotency; bulk writes | #405 system user |
| 13 | **S14 Anki export / API tokens** | ~20% | XS–S | A download button; a token settings page | S (CSV) / M (tokens) | A streamed CSV response; Passport personal access tokens with scopes and rate limits | Streaming responses; API security | Legacy retirement (for tokens) |
| 14 | **S11 Explain via the chat assistant** | ~20% | S (M with streaming) | "Why?" buttons; an answer panel; a streaming text display | L | The whole assistant epic, plus two read tools | Tool-calling with a language model, but the backend is a big lift | Chat assistant epic |
| 15 | **S1 Knowledge model** | ~10% | XS | A `useItemStatus` hook | M | Merge Known catalogues with flashcard evidence into new / seen / learning / known statuses; a batch endpoint | Read-model design; batch endpoints that avoid N+1 queries | #418 |

### Suggested path for a frontend focus with backend learning

1. **S4 Interactive reader.** The largest frontend payoff, gated by a small, well-contained backend slice. That slice touches an async job, a migration with backfill, and an evolving contract. Ship tap-to-look-up first; colouring follows with the `ViewerCatalogueStateService` reuse.
2. **S6 Adaptive furigana.** Almost pure frontend, built on S4's segments.
3. **S13 Private notes.** Deliberately done as your full-stack exercise: one feature through every backend layer, small enough to finish in a sitting or two.
4. **S8 Habit loop.** Frontend-heavy, with the time-zone lesson on the backend.
5. **S10 Kanji page.** Pairs with the #352 detail-page redesign already planned.

Leave S1, S11 and S14 for later. They are mostly backend, and the boolean `is_known` that already exists covers what the frontend features above need.

## Suggested order

```
#413 flashcards (in review)
   └── S2 scheduler ──┐
S1 knowledge model ───┼── S3 coverage ── S6 adaptive furigana
   │                  ├── S4 interactive reader ── S5 sentence mining
   │                  ├── S7 i+1 cloze
   │                  └── S8 habit loop
   └── S9 starter catalogues + "mark as known" (unblocks S1 for intermediate learners)
S10 kanji page — alongside #352
S11–S15 — after chat assistant / community v1 / legacy retirement
```

The two foundations are **S1 and S2.** Nearly everything personalised depends on them. The first visible win that needs neither is **S3's anonymous JLPT profile on article cards**: small, and immediately useful to visitors who are not signed in.
</content>
</invoke>
