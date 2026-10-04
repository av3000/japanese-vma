# Capturing and hosting PR review screenshots

Read this when the PR changes anything visible. Goal: every materially changed state at desktop (1400px), tablet (768px) and mobile (360px), shown in the PR body so the reviewer sees it without checking anything out. The screenshots are for review only and are not kept after the PR closes.

## Contents

1. Pick the states
2. Render them from stories
3. Capture with Playwright
4. Look at every image
5. Host them on the temporary branch
6. Embed in the PR body
7. Clean up, and delete the branch after merge

## 1. Pick the states

One row per state a reviewer would otherwise have to imagine. For a form: the filled form, the error state, and any special state the change adds (locked field, empty list, unchanged edit). For a list: populated, empty, and a long-text item. Three to six rows is typical; add a row only when it shows something new.

Every row needs a story (SKILL.md section 3). If a state has none, add the story first rather than staging the state by hand in a browser: the story stays useful as a test after the screenshots are gone.

## 2. Render them from stories

Stories need no backend and no login, and give the same picture every time. Build once and serve the static output, which is faster and steadier than the dev server:

- `cd client && npm run build-storybook`
- serve `client/storybook-static` with `python -m http.server <port> --bind 127.0.0.1` as a background task, and stop it afterwards.
- Story URL: `http://127.0.0.1:<port>/iframe.html?viewMode=story&id=<story-id>`.

Story `play` functions run on load in the built Storybook. A story that submits a form in `play` is already in its error state; do not click Submit again, or client validation clears the server errors you meant to show.

## 3. Capture with Playwright

Use one Playwright script for all shots (through the Playwright MCP tool, or a script run with `npx playwright`):

```js
async (page) => {
  // Without this a rebuilt Storybook can be served from cache and show the old CSS.
  const cdp = await page.context().newCDPSession(page);
  await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
  const base = 'http://127.0.0.1:6147/iframe.html?viewMode=story&id=';
  const shots = [
    // [story id, file stem]
    ['features-articles-articleform--filled', 'article-filled'],
  ];
  for (const [id, stem] of shots) {
    for (const width of [1400, 768, 360]) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto(base + id, { waitUntil: 'networkidle' });
      await page.getByRole('heading', { level: 1 }).waitFor({ timeout: 30000 });
      await page.waitForTimeout(2000); // let play functions finish
      await page.screenshot({ path: `.playwright-mcp/${stem}-${width}.png`, fullPage: true });
    }
  }
}
```

- The Playwright MCP server can only write inside the main checkout, so save to `.playwright-mcp/` there, then move the PNGs to a temporary folder outside the repo and delete `.playwright-mcp/`.
- While capturing, check `document.documentElement.scrollWidth <= clientWidth` at each width. Horizontal scroll at 360px is a bug to fix before the PR, not something to screenshot.
- Blur focus (`document.activeElement.blur()`) for normal-state shots so a stray focus ring is not mistaken for a design choice. Keep focus for error-state shots: it shows where focus landed.

## 4. Look at every image

Open each PNG and look at it before publishing. Check for stale styles, a half-rendered story, a loading skeleton, cut-off content, or the wrong state. An image of superseded UI is worse than no image.

## 5. Host them on the temporary branch

GitHub only renders images that live at a URL, and `gh` cannot upload attachments, so the PNGs go to a throwaway branch. It is not storage:

- the branch is `screenshots/<feature-branch>` and holds only the current set, with no history;
- publishing again replaces it (re-shoots after review feedback);
- it is deleted when the PR is merged or closed, after which the images in the PR stop loading.

```bash
bash <this skill's folder>/scripts/publish-screenshots.sh <png-dir> <feature-branch>
```

The script commits the PNGs with git plumbing (the working tree is never touched), pushes the branch, and prints the URL prefix to embed. The prefix contains the commit id rather than the branch name only so a re-shoot shows up at once: GitHub caches raw files by URL for a few minutes. `DRY_RUN=1` builds the commit without pushing. The repository is public, so the links render for every reviewer.

## 6. Embed in the PR body

One table per screen, columns in Desktop, Tablet, Mobile order, with explicit widths so the three fit side by side:

```markdown
## Screenshots

From the built Storybook at 1400 / 768 / 360px. Temporary: these stop loading after the PR closes.

### Article form

| Desktop | Tablet | Mobile |
|---|---|---|
| <img src="https://raw.githubusercontent.com/av3000/japanese-vma/<commit>/article-filled-1400.png" width="420" alt="Article form, filled, 1400px"> | <img src="…/article-filled-768.png" width="260" alt="Article form, filled, 768px"> | <img src="…/article-filled-360.png" width="160" alt="Article form, filled, 360px"> |
| <img src="…/article-errors-1400.png" width="420" alt="Article form, server errors, 1400px"> | <img src="…/article-errors-768.png" width="260" alt="…"> | <img src="…/article-errors-360.png" width="160" alt="…"> |
```

- Name each row's state in the alt text, and in a line above the table when it isn't obvious.
- Say where the shots came from (stories, not a live backend), so reviewers know what was not exercised.

## 7. Clean up, and delete the branch after merge

Right away: stop the static server, close the Playwright browser, and confirm `.playwright-mcp/` is gone from the main checkout.

After the PR is merged or closed:

```bash
bash <this skill's folder>/scripts/publish-screenshots.sh --delete <feature-branch>
```
