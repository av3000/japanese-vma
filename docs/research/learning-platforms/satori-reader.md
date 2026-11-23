# Satori Reader: Audit

> **Category:** Japanese graded reading with audio and annotations
> **Researched:** 2026-10-02 from satorireader.com; free-tier terms from its App Store listing

## Snapshot

| Item | Value | Source |
|---|---|---|
| Library | "1650+ episodes" across "43 (and counting!) series" at different difficulty levels; new episodes weekly | [1] |
| Free access | First two episodes of each series; first month free | [3] |
| Price | Paid subscription for the full library and downloadable audio. A monthly price is not on the first-party pages read | [1] |

## Feature assortment

- **Display choices:** kanji only, kana only, kanji with furigana, or furigana "according to your knowledge" [1].
- **In-line grammar explanations** and sentence annotations with translations and notes from editors [1].
- **Contextual definitions.** A tapped word shows the meaning it has in this story, not a dictionary dump [1].
- **Audio from native voice actors,** playable per sentence or per episode, and downloadable [1].
- **Contextual flashcards** with the audio clip and the story sentence, reviewed by SRS [1].
- **A usage heatmap** of daily study [1].

## Strengths

- **Context everywhere.** The card is the sentence where the word was met, with its audio. That is far more memorable than a bare word.
- **Human-written and human-explained.** Annotations target the specific sentence, which beats generic grammar pages.
- **Serial stories give learners a reason to come back** for the next episode.
- **Adaptive furigana** reduces clutter as knowledge grows, without hiding help from beginners.

## Weaknesses

- **Expensive to produce.** Every episode needs writers, editors and voice actors, so the library grows slowly compared with import-based tools.
- **Closed content.** Learners cannot bring their own texts.
- **Pricing is not prominent** on the first-party pages read.

## Lessons for Japanese VMA

- **Contextual definitions are possible** because Japanese VMA already extracts words per article. The gap is that the article pivot (`article_word`) stores no position, so the reader cannot yet tell which occurrence in the text is which word. Either match word forms client-side, or store offsets during processing.
- **Context sentences on flashcards.** When a learner saves a word from an article, keep the source sentence and article reference. The card then shows it, as Satori Reader's does.
- **An "explain this sentence" tool for the planned chat assistant** is Japanese VMA's scalable stand-in for Satori Reader's human annotations. Label it as AI and keep the dictionary data as its tool source.
- **A heatmap is cheap** once study attempts are stored (flashcards FC-BE-03).

## Sources

1. Satori Reader features, https://www.satorireader.com/features
2. Satori Reader home page, https://www.satorireader.com/
3. Satori Reader App Store listing, https://apps.apple.com/bb/app/satori-reader/id1382950847
</content>
</invoke>
