# Learning Platform Audits

> **Status:** Research notes. Not accepted scope: an idea becomes work only as a GitHub issue
> **Researched:** 2026-10-02
> **Audience:** Repository owner, product-minded contributors

## Question

What do other Japanese learning platforms, and a few general language platforms, offer? What do they do well and badly, and which of those ideas would make Japanese VMA more useful to learners?

## Method

- Facts come from each platform's own website, help centre, pricing page, API docs or company blog, fetched on 2026-10-02. Where a first-party page did not state a fact (usually a price), the audit says so instead of guessing. A few prices come from a third-party review and are marked as such.
- **Snapshot** and **Feature assortment** sections are sourced. **Strengths**, **Weaknesses** and **Lessons for Japanese VMA** are analysis: argue with them.
- Prices are in the currency the source used and change often. Treat them as orders of magnitude.

## Platforms audited

### Japanese-specific

| Platform | Core idea | Audit |
|---|---|---|
| WaniKani | Fixed 60-level kanji and vocabulary path with mnemonics and an SRS ladder | [wanikani.md](./wanikani.md) |
| Bunpro | Grammar SRS with typed fill-in-the-blank sentences | [bunpro.md](./bunpro.md) |
| Renshuu | One personal dictionary that tracks everything you know, with gamified quizzes | [renshuu.md](./renshuu.md) |
| Satori Reader | Graded serial stories with audio, annotations and furigana adapted to the reader | [satori-reader.md](./satori-reader.md) |
| jpdb | Media-ranked vocabulary decks with a machine-learned SRS and i+1 sentence cards | [jpdb.md](./jpdb.md) |
| Kanshudo | Large dictionary with usefulness rankings, games and an AI tutor | [kanshudo.md](./kanshudo.md) |
| MaruMori | Guided "world map" course covering grammar, kanji and vocabulary | [marumori.md](./marumori.md) |
| Migaku | Browser extension that turns Netflix, YouTube and web pages into study material | [migaku.md](./migaku.md) |
| Jisho | Free dictionary built on open datasets | [jisho.md](./jisho.md) |

### General language platforms

| Platform | Core idea | Audit |
|---|---|---|
| Duolingo | Short gamified lessons, streaks and leagues | [duolingo.md](./duolingo.md) |
| Anki | Bring-your-own flashcards with a research-backed scheduler (FSRS) | [anki.md](./anki.md) |
| LingQ | Import any content and read it with known/unknown word tracking | [lingq.md](./lingq.md) |
| Busuu | Structured CEFR course with corrections from native speakers | [busuu.md](./busuu.md) |
| Clozemaster | Fill-in-the-blank sentences ordered by word frequency | [clozemaster.md](./clozemaster.md) |

## Suggestions for Japanese VMA

The ranked suggestions list, with the learning reasons behind each one and the trade-offs against it, is in [suggestions.md](./suggestions.md).

## Feature matrix

✔ = stated by the sources read, ◐ = partial or limited, · = **not stated in the sources read**. A dot does not mean the platform lacks the feature; it means the audit did not confirm it. Japanese VMA's column reflects `develop` plus the flashcards branch (#413) as of 2026-10-02.

| Feature | WK | Bunpro | Renshuu | Satori | jpdb | Kanshudo | MaruMori | Migaku | Jisho | Duolingo | Anki | LingQ | Busuu | Cloze | **JVMA** |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Kanji / word dictionary | ◐ | ◐ | ✔ | · | ✔ | ✔ | ✔ | ◐ | ✔ | · | · | · | · | · | **✔ kanji, radicals, words, sentences** |
| Spaced repetition scheduler | ✔ | ✔ | · | ✔ | ✔ | ✔ | ✔ | ✔ | · | · | ✔ FSRS | ✔ | ◐ | ✔ | **— grading only, no scheduling** |
| Personal lists / decks | · | ✔ | ✔ | · | ✔ | ◐ | ✔ | · | · | · | ✔ | · | · | · | **✔ catalogues** |
| Tracks what the learner knows | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ◐ | ✔ | · | ◐ | ✔ | ✔ | · | ✔ | **◐ Known\* catalogue types, manual** |
| Reading material | · | ✔ 120+ | · | ✔ 1650+ | ◐ | ✔ | ✔ | ✔ any web | · | · | · | ✔ | · | · | **✔ articles; NHK import planned** |
| Tap a word in the text to look it up | · | · | · | ✔ | · | · | · | ✔ | · | · | · | ✔ | · | · | **— word lists sit below the text** |
| Known/unknown colouring in the text | · | · | · | · | · | · | · | ✔ | · | · | · | ✔ | · | · | **—** |
| Furigana adapted to what you know | · | · | ✔ | ✔ | · | · | · | · | · | · | · | · | · | · | **—** |
| Sentence fill-in-the-blank practice | · | ✔ | · | · | ✔ i+1 | ◐ | · | · | · | · | · | · | · | ✔ | **— sentences already stored** |
| Mnemonics | ✔ | · | · | · | ◐ | ✔ 4000 | ✔ | · | · | · | · | · | · | · | **—** |
| Stroke order / writing practice | · | · | · | · | · | ✔ | · | · | ✔ | ✔ | · | · | · | · | **—** |
| Audio | ✔ | ✔ | ◐ | ✔ | · | · | ✔ | ✔ TTS | · | · | · | ✔ | ✔ | · | **—** |
| Learner-written content | · | · | ✔ | · | · | · | ◐ | · | · | · | ✔ | ✔ | ◐ | · | **✔ articles, catalogues, posts** |
| Corrections from native speakers | · | · | · | · | · | · | · | · | · | · | · | · | ✔ | · | **— comments exist** |
| AI explanations | · | · | · | · | · | ✔ | · | ✔ | · | ✔ | · | ✔ | · | · | **— chat assistant planned** |
| Streaks / heatmap | · | · | · | ✔ | · | ◐ | ✔ | · | · | ✔ | · | ✔ | · | · | **—** |
| API / import-export | ✔ API | · | · | · | ✔ | ✔ | ◐ | ✔ | · | · | · | · | · | · | **◐ PDF export** |

## Where Japanese VMA already stands out

- **It already does what reading-first tools charge for.** It extracts kanji and words from articles automatically, links them to a full dictionary, lets learners save them to lists, and exports them to PDF. LingQ, Migaku and Satori Reader all charge for parts of this.
- **Learners can write the content.** Learner-written articles, catalogues and community posts all exist. Only Renshuu, MaruMori, Anki (shared decks) and LingQ (imports) open up content creation to a similar degree.
- **The dictionary is open, and it is all in one place.** Kanji, radicals, words and Tatoeba sentences are cross-linked in a single site, much like Jisho, which uses the same family of open datasets.

## Biggest gaps

1. **No scheduling.** The flashcards branch grades answers but does not decide when an item comes back. Every serious competitor does.
2. **The reader is passive.** Words are listed under the article instead of being interactive inside the text. The Known* catalogues are not used to show what the learner already knows.
3. **No daily-habit loop.** There is no streak, heatmap or "reviews due" prompt to bring a learner back tomorrow.
</content>
</invoke>
