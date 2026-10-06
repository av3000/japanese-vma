# Capturing and uploading PR review screenshots

Read this when the PR changes anything visible. Goal: every materially changed state at desktop (1400px), tablet (768px) and mobile (360px), shown in the PR body so the reviewer sees it without checking anything out. The images are uploaded to GitHub as attachments and stay visible after the merge.

## Contents

1. Pick the states
2. Render them from stories
3. Capture with Playwright
4. Look at every image
5. Upload with `gh --attach`
6. Embed in the PR body
7. Re-shoot, and clean up locally

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

Open each PNG and look at it before uploading. Check for stale styles, a half-rendered story, a loading skeleton, cut-off content, or the wrong state. An image of superseded UI is worse than no image.

## 5. Upload with `gh --attach`

`gh issue|pr create|edit|comment --attach <file>` uploads the file to GitHub's attachment store (`github.com/user-attachments/assets/…`, the same store as drag-and-drop in the browser) and rewrites the body reference to the uploaded URL. The images stay visible after the PR merges; there is nothing to clean up.

Prerequisites:

- `gh` ≥ 2.99.0 (`gh --version`).
- Write access to the repository, with an OAuth token (`gh auth login`, a `gho_` token) or a classic PAT. GitHub App `ghu_` tokens are unreliable: older `gh` refuses them outright, newer `gh` (after cli/cli#14495) sends them on and the upload API may still reject them. `gh auth status` shows which kind is active.
- Limits: 10 MB per image, 50 files per command. A full-page 1400px shot of a long page can approach 10 MB; check sizes with `ls -l` before uploading.

How `--attach` matches files to the body:

- A **Markdown** image or link, `![alt](./name.png)`, is rewritten when the path in the body equals the path given to `--attach`. `./name.png` matches `--attach name.png` and does not match `--attach ../dir/name.png`. Run the command from inside the PNG folder and attach bare file names; the body file can live anywhere.
- An HTML `<img src="./name.png">` is **never** rewritten: it stays a broken image, and the file is appended to the end of the body as an extra Markdown image. Use Markdown images for every local file.
- Every attached file the body does not reference is appended to the end of the body. Reference every PNG you attach, and attach only PNGs the body references.

Create the PR with the screenshots, or add them to an existing PR:

```bash
cd <png-dir>
attach=(); for f in *.png; do attach+=(--attach "$f"); done
gh pr create --repo av3000/japanese-vma --base develop --head <branch> --title "…" --body-file <body.md> "${attach[@]}"
gh pr edit <n> --repo av3000/japanese-vma --body-file <body.md> "${attach[@]}"
```

Then read the body back (`gh pr view <n> --json body --jq .body`) and check that every image URL starts with `https://github.com/user-attachments/assets/` and that no `./` path is left. Judge whether an image loads on the PR page, not by fetching the bare asset URL: that URL returns 404 without a GitHub session, while the PR page serves every viewer, logged out included, a signed image URL.

## 6. Embed in the PR body

One table per screen, columns in Desktop, Tablet, Mobile order, each cell a Markdown image that points at the local PNG:

```markdown
## Screenshots

From the built Storybook at 1400 / 768 / 360px.

### Article form

| Desktop | Tablet | Mobile |
|---|---|---|
| ![Article form, filled, 1400px](./article-filled-1400.png) | ![Article form, filled, 768px](./article-filled-768.png) | ![Article form, filled, 360px](./article-filled-360.png) |
| ![Article form, server errors, 1400px](./article-errors-1400.png) | ![Article form, server errors, 768px](./article-errors-768.png) | ![Article form, server errors, 360px](./article-errors-360.png) |
```

- Leave out `width` attributes: GitHub scales the table to the body width, so the three shots sit side by side at their true relative scale (about 360 / 210 / 120px on a desktop viewport). Clicking an image opens it full size.
- Name each row's state in the alt text, and in a line above the table when it isn't obvious.
- Say where the shots came from (stories, not a live backend), so reviewers know what was not exercised.

## 7. Re-shoot, and clean up locally

Every `--attach` run uploads new assets, even for an identical file; the body then points at the new ones and the old ones are left unreferenced, so a re-shoot never shows superseded UI:

1. Re-shoot, and look at every new PNG (section 4).
2. `gh pr view <n> --json body --jq .body > body.md`, then replace the images in the `## Screenshots` section with Markdown references to the local file names (`![…](./article-filled-1400.png)`). Leave the rest of the body as it is.
3. From the PNG folder, run `gh pr edit` with `--attach` for every PNG, as in section 5, and read the body back.

Local cleanup, right away: stop the static server, close the Playwright browser, and confirm `.playwright-mcp/` is gone from the main checkout.
