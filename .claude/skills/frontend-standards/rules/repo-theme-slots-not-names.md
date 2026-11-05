---
title: Themes Change Appearance Through Slots, Never by Name
impact: HIGH
impactDescription: theme names in code turn every theme into a fork of the app
tags: repo, styling, themes, colour-scheme
---

## Themes Change Appearance Through Slots, Never by Name

**Impact: HIGH (theme names in code turn every theme into a fork of the app)**

**Enforced by** ESLint (`no-restricted-syntax`, outside `src/themes/`) for TS and `npm run style:audit` (`themeSelectors`) for CSS.

A theme (`kinari`, `sakura`, `sumi`) or a colour scheme changes appearance only: colours, fonts, imagery, icons, favicon. It never changes behaviour or which features exist.

- Never compare a theme id in code, and never select on `[data-theme]` in a component's CSS.
- Markup that must differ per theme is a named slot in `src/themes/registry.ts`, read through `useTheme()`.
- Behaviour that must differ is a named flag, never a theme name.
- Colours and fonts come from the role tokens, which each theme overrides in `src/styles/themes/<id>.css`.

Project K tied a redesign rollout, a paging mode and image renditions to one theme value. It ended with about 750 name checks and dead branches when themes were removed.

**Incorrect:**

```tsx
const { theme } = useAppearance();
<Icon name={theme === 'sakura' ? 'blossomSolid' : 'thumbsUpSolid'} />
```

```css
:global([data-theme='sakura']) .likeButton {
	color: hotpink;
}
```

**Correct:**

```tsx
const theme = useTheme();
<Icon name={liked ? theme.icons.likeOn : theme.icons.likeOff} />
```

```css
.likeIcon {
	color: var(--color-like-on); /* each theme sets the role */
}
```

Source: `client/DESIGN.md` §3, issue #498.
