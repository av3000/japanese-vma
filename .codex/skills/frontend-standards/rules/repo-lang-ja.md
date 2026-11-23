---
title: Japanese Text Carries lang="ja"
impact: MEDIUM
impactDescription: Japanese glyph forms and the Japanese font stack
tags: repo, typography, i18n, japanese
---

## Japanese Text Carries lang="ja"

**Impact: MEDIUM (Japanese glyph forms and the Japanese font stack)**

Put `lang="ja"` on every element that holds kanji, kana, readings or a Japanese article body. `03-elements/page.css` maps `[lang='ja']` to `--font-family-ja`, which gives the right font stack and Japanese (not Chinese) glyph forms in one step. Do not set the Japanese font family by hand.

**Incorrect:**

```tsx
<h1 className={styles.kanji}>{kanji.kanji}</h1>
```

```css
.kanji {
	font-family: 'Noto Sans JP', sans-serif;
}
```

**Correct:**

```tsx
<h1 className={styles.kanji} lang="ja">
	{kanji.kanji}
</h1>
```

Source: `client/DESIGN.md` §5.
