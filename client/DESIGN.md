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

Each hard rule is a citable rule in the `frontend-standards` skill (`.claude/skills/frontend-standards/rules/<id>.md`), with examples:

- Tokens for every value, and only tokens that exist: `repo-css-modules-tokens`.
- Layout through `Container`, `Stack`, `Cluster` and `Grid`: `repo-layout-primitives`.
- Colour is never the only cue: `repo-colour-not-only-cue`.
- Keep the global focus outline: `repo-keep-focus-outline`.
- Respect `prefers-reduced-motion`: `repo-reduced-motion`.
- Production habits, assets through the build, fallbacks for newer CSS: `repo-assets-through-build`.
- Japanese text carries `lang="ja"` (§5): `repo-lang-ja`. The accent colour means JLPT level (§3): `repo-accent-means-level`. Reuse the components in §7: `repo-shared-components-first`.

## 3) Colour

Colour comes in three layers. Components use **roles** only; they never name a palette step or a theme.

1. **Palette** (`colors.css`): the raw colours. A two-colour Japanese scheme, ai 藍 indigo and shu 朱 vermilion, on kinari 生成り (warm undyed-cloth white), plus neutrals, status colours and ink 墨 (warm near-black, the dark surface). `colors.css` documents contrast ratios inline.
2. **Roles** (`roles.css`): what a colour is for. Each is defined once on `:root` with the kinari theme's light value.
3. **Themes** (`src/styles/themes/<id>.css`, from #498 slice 4): override roles under `:root[data-theme='<id>']`. kinari, the default, has no file. A theme's own palette steps live only in its file.

| Role        | Tokens                                                                                                    | Use for                                                                                              | Do not use for              |
| ----------- | --------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | --------------------------- |
| Surface     | `--color-surface` (the page), `-raised` (cards, panels, menus, inputs), `-sunken` (wells)                 | Backgrounds                                                                                          | Text on a coloured fill     |
| Text        | `--color-text` (body and links), `-strong`, `-subtle`, `-muted`                                           | Text colour by emphasis                                                                              | —                           |
| Border      | `--color-border`, `--color-border-strong`                                                                 | Dividers and control edges                                                                           | —                           |
| Interactive | `--color-interactive`, `-hover`, `-disabled`, `-tint`; `--color-on-interactive`; `--color-focus`          | Navigation, buttons, links in UI chrome, selected state; text on an interactive fill; focus outlines | Level markers               |
| Level       | `--color-level`, `-text`, `-glyph`, `-tint`; `--color-badge-accent-*`                                     | **JLPT level only**: `LevelBadge`, `JlptBar`, glyph covers. The accent always means "level".         | Buttons, errors, decoration |
| Status      | `--color-success`, `--color-warning`, `--color-error` (each with `-hover`, `-disabled`, `-tint`, `-text`) | Feedback through `Alert`, `StatusPill` and form validation                                           | Branding                    |
| Like        | `--color-like-off`, `--color-like-on`                                                                     | The like icon (kinari: the colour of the button it sits in)                                          | —                           |
| Component   | `--color-pill-*`                                                                                          | `Pill` on indigo                                                                                     | —                           |

- Contrast target is WCAG 2.2 AA: 4.5:1 for body text, 3:1 for large text and non-text UI. `--color-level-glyph` passes only as a fill or for large text.
- Links default to the body text colour; mark them with underline or weight, not colour.
- Older names (`--color-text-primary`, `--color-text-neutral-*`, `--color-text-primary-default|hover`, `--color-link*`, `--page-background-color`, `--font-family`) are aliases of the roles and are being removed (#498). Do not use them in new code. Raw palette steps (`--color-neutral-*`, `--color-primary-*`, `--color-accent-*`, `--color-secondary-*`) in component CSS are being moved to roles under the same issue; do not add new ones.
- There is no dark scheme yet. The single `prefers-color-scheme: dark` rule in `colors.css` is a leftover, not a pattern to extend.

### Themes and schemes

A **scheme** is light or dark. A **theme** (kinari, sakura, sumi) changes appearance: colours, fonts, imagery, icons, favicon. The rule for both: **a theme or scheme never changes behaviour or which features exist.**

- Code never compares a theme id. No `theme.id === 'sakura'` in TS, no `[data-theme='sakura']` selector in component CSS.
- What must differ in markup is a named slot in the theme config (`theme.icons.likeOn`, `theme.logo`). What must differ in behaviour is a named flag, never the theme name.
- Dark values use `light-dark()`, always inside `@supports (color: light-dark(#000, #fff))`. Browsers without it get kinari light colours; that is the accepted fallback.

## 4) Spacing, Size And Shape

- Spacing: `--spacing-3xs` (2px) to `--spacing-4xl` (128px), in `spacing.css`. Use `md` (16px) as the default gap, `lg`/`xl` between sections, `xs`/`sm` inside dense controls.
- Sizes: `--size-*` for fixed element sizes, `--icon-size-*` for icons, `--container-xs`…`-lg` for max widths (reached through `Container size`).
- Controls: `--button-height-sm|md` and `--input-height-sm|md` keep buttons and inputs aligned in one row.
- Radius: `--border-radius-xs`…`-xl`. Borders: `--border-size-sm|md`. One shadow: `--box-shadow-default`.
- Layering: always use `--z-index-*` from `z-index.css`. The scale is relative, so add a new layer there rather than picking a number.
- Breakpoints have no tokens because custom properties cannot be used in media queries. Use the literal min-width values the layout primitives use: **768px**, **1024px** and **1320px**. Design mobile first.

## 5) Typography

- Body is IBM Plex Sans; counts, dates and readings use IBM Plex Mono (`--font-family-mono`). Both are bundled through `@fontsource` in `src/styles/global-styles.ts`, which `main.tsx` and the Storybook preview import. Components use the font roles in `roles.css` (`--font-family-body`, `--font-family-heading`) and `--font-family-ja`, `--font-family-mono`; themes override the roles.
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
| Community post in a list                                | `features/community/PostCard` (a `LibraryCard` with no cover)             |
| Modal or dialog                                         | `Modal`, `DialogModal`, `modals/ConfirmModal` (native `<dialog>`)         |
| Full-height sheet (mobile navigation, search)           | `Drawer` (native `<dialog>`, opened through `useModal`)                   |
| Popover                                                 | `src/components/ui/popover` (Radix)                                       |
| Icon                                                    | `Icon`                                                                    |
| Loading                                                 | `Spinner`, `PageLoading`                                                  |

`src/components/ui/badge` is an older status badge kept for the comment count. Use `StatusPill` for new status UI.

## 8) Before You Finish A Styling Change

- `npm run style:audit` is clean, and nothing new is hardcoded that has a token.
- New colours and fonts use roles (§3), not palette steps or font stacks.
- Stories exist or are updated for any new shared component or variant.
- Check at 375px and at 1400px or wider, using keyboard focus as well as the mouse.
- Japanese content has `lang="ja"`.
