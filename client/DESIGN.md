# DESIGN.md

How the client looks and how to style it. Read this before any visual, styling or component-choice work under `client/`.

The CSS is the source of truth for values. This file explains meaning and rules; it does not copy values, so it does not drift. When a token's value matters, open the file in `src/styles/00-settings/`.

## 1) Where Things Live

| Layer      | Path                                 | What belongs there                                                                     |
| ---------- | ------------------------------------ | -------------------------------------------------------------------------------------- |
| Tokens     | `src/styles/00-settings/*.css`       | CSS custom properties on `:root` only. No selectors.                                   |
| Generic    | `src/styles/02-generic/`             | Reset, normalize, box-sizing, focus outline.                                           |
| Elements   | `src/styles/03-elements/`            | Bare element defaults (`h1`–`h6`, `a`, `table`, form fields).                          |
| Utilities  | `src/styles/99-utilities/`           | The fixed `u-*` set: ellipsis, visually hidden, text alignment/tone. Do not add to it. |
| Components | `*.module.css` next to the component | Everything else.                                                                       |

`src/styles/index.css` imports the layers in order. Storybook renders the token groups under **Styleguide**.

## 2) Hard Rules

- Use a token for every colour, spacing, radius, font size, shadow, duration and z-index that has one. Do not hardcode hex values or pixel spacing in a CSS Module.
- If no token fits, add one to the right `00-settings` file with a comment saying why, instead of hardcoding the value.
- Only reference tokens that exist. An undefined `var(--x)` fails silently and falls back to the inherited value.
- No Bootstrap, Tailwind, Sass, CSS-in-JS or utility-class frameworks. `npm run style:audit` and `style-budget.json` hold these at zero, and ESLint rejects Bootstrap imports.
- Lay out with `Container`, `Stack`, `Cluster` and `Grid` from `src/components/shared/layout`. Their `gap` props take spacing token names (`'md'`, `'lg'`).
- Colour is never the only cue. Status, level and validity also carry text or an icon, and the text is visible (see `StatusPill`'s `label`).
- Keep the global focus outline from `02-generic/outline.css`. Change its colour if you need to, but never remove it.
- Respect `prefers-reduced-motion: reduce` for any animation or transition that moves things.
- Treat the app as production: keep browser defaults where they work, add fallbacks for newer CSS, and ship assets such as fonts through the build, not a CDN.

## 3) Colour

The palette is a two-colour Japanese scheme on a warm white page. `colors.css` documents the contrast ratios inline.

| Role                     | Tokens                                                                                                  | Use for                                                                                      | Do not use for              |
| ------------------------ | ------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- | --------------------------- |
| Primary, ai 藍 indigo    | `--color-primary`, `-hover`, `-disabled`, `-tint`, `-100`…`-600`, `--color-pill-*`                      | Navigation, buttons, links in UI chrome, selected state, focus tints                         | Level markers               |
| Accent, shu 朱 vermilion | `--color-accent`, `-hover`, `-glyph`, `-100`, `-200`, `-900`, `--color-badge-accent-*`                  | **JLPT level only**: `LevelBadge`, `JlptBar`, glyph covers. The accent always means "level". | Buttons, errors, decoration |
| Status                   | `--color-success`, `--color-warning`, `--color-error` (each with `-hover`, `-disabled`)                 | Feedback through `Alert`, `StatusPill` and form validation                                   | Branding                    |
| Neutral                  | `--color-neutral-0`…`-900`, `--color-secondary-*`                                                       | Text, borders, surfaces, muted UI                                                            | —                           |
| Text                     | `--color-text-primary` (default body and link text), `--color-text-neutral-*`, `--color-text-primary-*` | Text colour by state                                                                         | —                           |
| Page                     | `--page-background-color` (kinari 生成り, warm white)                                                   | The page surface                                                                             | —                           |

- Contrast target is WCAG 2.2 AA: 4.5:1 for body text, 3:1 for large text and non-text UI. `--color-accent-glyph` passes only as a fill or for large text.
- Links default to the body text colour; mark them with underline or weight, not colour.
- There is no dark theme. The single `prefers-color-scheme: dark` rule in `colors.css` is a leftover, not a pattern to extend.

## 4) Spacing, Size And Shape

- Spacing: `--spacing-3xs` (2px) to `--spacing-4xl` (128px), in `spacing.css`. Use `md` (16px) as the default gap, `lg`/`xl` between sections, `xs`/`sm` inside dense controls.
- Sizes: `--size-*` for fixed element sizes, `--icon-size-*` for icons, `--container-xs`…`-lg` for max widths (reached through `Container size`).
- Controls: `--button-height-sm|md` and `--input-height-sm|md` keep buttons and inputs aligned in one row.
- Radius: `--border-radius-xs`…`-xl`. Borders: `--border-size-sm|md`. One shadow: `--box-shadow-default`.
- Layering: always use `--z-index-*` from `z-index.css`. The scale is relative, so add a new layer there rather than picking a number.
- Breakpoints have no tokens because custom properties cannot be used in media queries. Use the literal min-width values the layout primitives use: **768px**, **1024px** and **1320px**. Design mobile first.

## 5) Typography

- Body is IBM Plex Sans; counts, dates and readings use IBM Plex Mono (`--font-family-mono`). Both are bundled through `@fontsource` in `main.tsx` and the Storybook preview.
- Japanese text: put `lang="ja"` on the element. `03-elements/page.css` maps `[lang='ja']` to `--font-family-ja`, so you get the font stack and Japanese (not Chinese) glyph forms in one step. Do this for kanji, kana, readings and Japanese article bodies. Use `--font-family-ja-serif` only for display type.
- Sizes: `--font-size-body-xs|sm|md|lg` and `--font-size-heading-1`…`-6`. The page body is `lg` (16px) at `--line-height-lg`; use `md`/`sm` for secondary text and metadata.
- Weights: `--font-weight-regular`, `--font-weight-semibold`. Line heights: `--line-height-sm|md|lg`; Japanese article text uses `--line-height-japanese-article-text`.

## 6) Motion

- Durations `--duration-sm|md|lg`; easing `--ease-base` by default, `--ease-elastic*` only for small playful feedback.
- Inside `@media (prefers-reduced-motion: reduce)`, turn motion off or replace it with an opacity change.

## 7) Which Component To Use

Reuse one of these before writing new markup. Each has stories in Storybook.

| Need                                                    | Component (`src/components/shared/…` unless noted)                        |
| ------------------------------------------------------- | ------------------------------------------------------------------------- |
| Page title, description, primary action                 | `PageHeader`                                                              |
| Search, filters, sort and reset above a list            | `FilterBar`                                                               |
| Action or navigation that looks like a button           | `Button` (`variant`, renders as a router link with `to`)                  |
| Inline text link                                        | `Link`                                                                    |
| Inline message (info, success, warning, danger)         | `Alert`                                                                   |
| Moderation or processing status                         | `StatusPill`, fed from `articleStatusPill` / `processingStatusPill`       |
| JLPT level marker                                       | `LevelBadge`                                                              |
| JLPT level distribution of a text                       | `JlptBar`                                                                 |
| Tag, removable or read-only                             | `Chip`                                                                    |
| Counter dot in the corner of another element            | `Badge`                                                                   |
| Form field with label, hint, error and counter wired up | `FormControls` → `FormField` (render prop for `register()`)               |
| Bare form controls                                      | `FormControls` → `Input`, `Textarea`, `Select`, `Checkbox`, `InputGroup`  |
| Pick one of a few options, shown as tiles               | `FormControls` → `ChoiceGroup` (native radios)                            |
| Create or edit page                                     | `FormPage` with `FormLayout`, `FormCard`, `FormNote`                      |
| Tabular list that stacks on mobile                      | `DataTable`                                                               |
| Article or catalogue card in a list                     | `features/LibraryCards` (`ArticleCard`, `CatalogueCard`, `LibraryLayout`) |
| Modal or dialog                                         | `Modal`, `DialogModal`, `modals/ConfirmModal` (native `<dialog>`)         |
| Full-height sheet (mobile navigation, search)           | `Drawer` (native `<dialog>`, opened through `useModal`)                   |
| Popover                                                 | `src/components/ui/popover` (Radix)                                       |
| Icon                                                    | `Icon`                                                                    |
| Loading                                                 | `Spinner`, `PageLoading`                                                  |

`src/components/ui/badge` is an older status badge kept for comments and post details. Use `StatusPill` for new status UI.

## 8) Before You Finish A Styling Change

- `npm run style:audit` is clean, and nothing new is hardcoded that has a token.
- Stories exist or are updated for any new shared component or variant.
- Check at 375px and at 1400px or wider, using keyboard focus as well as the mouse.
- Japanese content has `lang="ja"`.
