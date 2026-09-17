# Web fonts

Aeonik (CoType Foundry) is the UI typeface. Place the licensed web files here:

- `Aeonik-Regular.woff2` (400)
- `Aeonik-Medium.woff2` (500)
- `Aeonik-Bold.woff2` (700)

`src/styles/00-settings/typography.css` declares the matching `@font-face` rules. Until the
files exist the browser falls back to Helvetica/Arial (one 404 per weight in the console).
Font binaries are gitignored; keep the licence with the files, not in the repository.
