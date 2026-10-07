# Runbook: Content Import (NHK News)

> **Status:** Current; written for the first production setup after PR #425
> **Last reviewed:** 2026-10-07
> **Audience:** Whoever sets up or looks after a production environment

The Content Import creates short articles from NHK News once a day (`php artisan content:import`). This runbook is the checklist for making it work in production and keeping it working. How it works inside, and why, is in [Content Import](../architecture/content-import.md).

**Nothing here happens by itself.** Merging into `develop` deploys nothing, and the release pipeline neither seeds the import's data nor runs a scheduler. Work through the first-time setup below the first time an environment gets this code.

## First-time setup checklist

Do these in order, once per environment.

### 1. Get the code into a deployed release

The GitLab pipeline only migrates and deploys from the default branch (`master`). The import reaches production with the next `develop` → `master` release.

### 2. Run the migrations

This feature adds five migrations (content sources, article provenance, nullable English fields, import runs, tag mappings). On `master`, the pipeline offers the manual `migrate_backend` job when migrations change. Run it.

### 3. Seed the import's reference data (the pipeline does not do this)

`migrate_backend` only runs `ObjectTemplatesTableSeeder` and `RoleSeeder`. Run these three yourself, in the same way (a one-off container from the release image, with the production environment):

```bash
php artisan db:seed --class="Database\Seeders\ContentSourceSeeder" --force
php artisan db:seed --class="Database\Seeders\NhkTagMappingSeeder" --force
php artisan db:seed --class="Database\Seeders\ContentImporterUserSeeder" --force
```

| Seeder | What it creates | What fails without it |
|---|---|---|
| `ContentSourceSeeder` | The `nhk-news` row in `content_sources` (name, homepage, enabled) | Every run stops with `ContentImport.UnknownSource` |
| `NhkTagMappingSeeder` | NHK genres and about thirty topics mapped to platform hashtags | Imports succeed but get no tags |
| `ContentImporterUserSeeder` | The system user that authors Imported Articles. It cannot sign in. | Every run stops with `ContentImport.SystemAuthorMissing` |

All three are safe to run again: they create what is missing and keep any edits made in production. Run `RoleSeeder` before `ContentImporterUserSeeder`; `migrate_backend` already does.

To stop doing this by hand, add the three lines to the `migrate_backend` script in `.gitlab-ci.yml`.

### 4. Set the environment variables

| Variable | Needed | Why |
|---|---|---|
| `CONTENT_IMPORT_LOCK_STORE` | Yes, set it to `redis` | Only one run per source may run at a time, enforced by a cache lock. A lock is only shared between machines that use the same cache store, and the file cache is per machine. |
| `APP_URL` | Already set | Part of the import's User-Agent, so NHK can see who is crawling and where to find the site. |
| `CONTENT_IMPORT_REQUEST_INTERVAL_MS` | No (default 1000) | Pause between requests to NHK. Do not lower it. |
| `CONTENT_IMPORT_SYSTEM_USER_EMAIL` | No | Only if the system user must have a different address. |

### 5. Try it by hand

On a production shell or one-off container:

```bash
php artisan content:import --source=nhk-news --dry-run -v
```

A dry run lists, filters and tags but writes nothing. Expect up to ten "would create" lines with hashtags such as `#政治`. Then run it for real:

```bash
php artisan content:import --source=nhk-news
```

Check the result in the database:

```sql
SELECT status, listed, created, skipped, failed, error, started_at, finished_at
FROM content_import_runs ORDER BY id DESC LIMIT 5;
```

A healthy run is `succeeded`, with `created` between 1 and 10 most days.

### 6. Make sure imported articles get processed

Each Imported Article goes through the same word and kanji processing as any other article, as a queued job (`ProcessArticleContentJob`). **If nothing consumes the queue, imported articles stay "pending" forever.** The worker host is currently gone, so this has to be decided together with the queue runtime in [#331](https://github.com/av3000/japanese-vma/issues/331). For the import alone, there are two ways out:

- Run the scheduled import with `QUEUE_CONNECTION=sync`. Each article is then processed in the same process, right after it is saved. #331 records a known risk to check first: a failed extraction rethrows. Under `sync`, the import would then report that article as failed even though it was saved, and it would not be retried.
- Run `php artisan queue:work --stop-when-empty` straight after the import, in the same job. This also processes anything else waiting in the queue.

### 7. Make it run every day

See the next section. Until one of those options is set up, the import only runs when someone runs it by hand.

## Making it run every day

### What has to happen

Something has to start `php artisan content:import` once a day. The intended time is 07:30 in Tokyo, which is 22:30 UTC the day before. There are two ways:

- **Laravel's scheduler.** `app/Console/Kernel.php` already lists the import at 07:30 Asia/Tokyo, next to `article-processing:sweep-stale` every five minutes. The scheduler runs whatever is due when it is started, either by a cron entry calling `php artisan schedule:run` every minute, or by a long-running `php artisan schedule:work`.
- **An outside scheduler** that runs `php artisan content:import` at 22:30 UTC. It needs nothing from `Kernel.php`.

### Does a scheduler use many resources?

No. The cost is not the scheduler; it is having something running all the time.

- `schedule:run` starts, boots Laravel, checks what is due and exits, usually in well under a second. Run once a minute, that is a few seconds of CPU an hour. This is the standard way Laravel applications run scheduled work in production.
- `schedule:work` is the same check in a loop that sleeps between minutes.
- Projects K and B (below) do the same thing with a heavier tool: an always-on job server that polls its database every few seconds.

So the question is not "can we afford a scheduler" but "where does a process run every minute, or once a day". Today nothing in this project's production runs all the time except the Render web service.

### Options without a VM

| Option | What it runs | Cost | Watch out for |
|---|---|---|---|
| **Render Cron Job** | A separate Render service from the same image, command `php artisan content:import`, schedule `30 22 * * *` | Billed per second of run time, minimum $1 a month per cron job | Needs the same environment variables as the API service. Easiest to set up and to trust. |
| **GitLab scheduled pipeline** | A job that runs only on a schedule and runs the command in a one-off container, like `migrate_backend` does | Free within GitLab.com's 400 CI minutes a month (a run is a few minutes, image pull included) | In a scheduled pipeline, `changes:` rules count as matched, so the existing build and deploy jobs would also run. Every one of them needs a `$CI_PIPELINE_SOURCE != "schedule"` rule first. |
| **GitHub Actions scheduled workflow** | A workflow on `schedule:` that runs the image with the production environment from repository secrets | Free for a public repository | Runs can start 15–30 minutes late or be dropped under load. Scheduled workflows switch off after 60 days without a push to the repository. Production database and Redis credentials would live in GitHub too. |
| **An always-on host** (a VM, or a paid Render background worker) | `schedule:work` and Horizon side by side | A small VM or worker plan | The most production-like, and it also solves the queue in step 6. This is what [#210](https://github.com/av3000/japanese-vma/issues/210) and #331 are about. |
| **Not this: the scheduler inside the free Render web service** | — | — | A free web service sleeps after 15 minutes without traffic, and its scheduler sleeps with it. |

Recommendation:

- **While there is no always-on host:** a Render Cron Job if $1 a month is fine, because it is the least to set up and maintain. Otherwise a GitLab scheduled pipeline, guarding the other jobs first.
- **Once a host exists for the queue worker (#331):** run `schedule:work` there and remove the outside scheduler.

Whichever runs it, check `content_import_runs` the next morning, as in step 5.

### Exactly once

The import cannot double-run by accident. Each run takes a per-source lock (step 4), and a second run is refused with `ContentImport.RunInProgress` and a non-zero exit code. If two schedulers ever fire at the same time, one of them simply exits.

## How Projects K and B run their daily jobs

Both reference solutions use Hangfire, a .NET background-job library. Their setups map onto Laravel's pieces like this:

| What | Project K | Project B | This project (Laravel) |
|---|---|---|---|
| Where jobs run | A separate job-server program, installed as an always-on Windows service. Job state lives in the database. | A separate job-server application (its own container), always on, with one server per named queue and a worker count each. | `schedule:work` (or cron plus `schedule:run`) for timing, Horizon for queued jobs. |
| Adding a recurring job | Write a job class with a static schedule property, for example "daily at 22:30". At startup a registration step finds every job class, removes its old recurring entry and adds it again with the current schedule, so schedule changes ship with the code. | The job class declares a default schedule and queue. The first registration copies the default into a schedules table; from then on the table wins and can be changed from an admin page without a deploy. | One line in `app/Console/Kernel.php`, for example `$schedule->command('content:import')->dailyAt('07:30')->timezone('Asia/Tokyo')`. Changes ship with the code, as in Project K. |
| Daily at a set time | Cron expressions through helpers such as `Daily(hour, minute)`, in UTC | Same helpers, plus the editable table | `dailyAt()` plus `timezone()`, or a raw `cron()` expression |
| One copy at a time | A filter fingerprints the job type and parameters under a distributed lock and cancels a second copy | Same filter | `withoutOverlapping()` on the schedule, or a lock inside the job. The import does the latter, so it also holds for manual runs. |
| On a developer machine | Every schedule becomes "never"; jobs run only when triggered by hand | Not checked | Nothing runs unless `schedule:work` is started; run the command by hand |
| Seeing runs, triggering by hand | The job dashboard lists every run with its log, retries and failures, and has a "trigger now" button | Same dashboard, plus job metrics | Horizon for queued jobs. Scheduled commands have no dashboard: the import writes `content_import_runs`, and you trigger it by running the command. |

The main lesson: in both reference solutions, someone pays for one always-on program whose only job is running background work. The schedules are cheap; the host is the cost. That program is the piece this project does not have yet (#210, #331).

## Everyday operations

| Task | How |
|---|---|
| Run now | `php artisan content:import` (all enabled sources) or `--source=nhk-news` |
| See what would happen | Add `--dry-run`; add `-v` for one line per article |
| Stop a source | `UPDATE content_sources SET enabled = false WHERE key = 'nhk-news';` |
| Check recent runs | The SQL in step 5 |
| See unmapped topics | Logs: "Content import topic has no tag mapping". Add rows to `source_tag_mappings` for any worth tagging. |

## When something goes wrong

| Symptom | Likely cause | What to do |
|---|---|---|
| Exit code 1, `ContentImport.UnknownSource` or `SystemAuthorMissing` | Step 3 was skipped | Run the seeders |
| Exit code 1, `ContentImport.RunInProgress` | Another run of the source is in progress, or one died holding the lock | Wait. The lock expires two hours after the run started. |
| A run `failed`, with the error "robots.txt … disallows" | NHK changed its robots.txt | Stop the source and check what NHK now allows. Do not work around it. |
| A run `failed` with "keep failing", "not valid XML" or "expected a urlset" | NHK is down, or changed its page or sitemap format | If it repeats, the adapter needs updating. Fix it against saved fixtures in `tests/Fixtures/nhk/`. |
| Warning "several runs in a row created nothing" | The source changed quietly, or every article is being filtered | Run `--dry-run -v` and read the skip reasons |
| A run stays `running` | The process was killed | The next run of the source closes it as failed; nothing to do |
| Imported articles stay "pending" | Nothing processes the queue | Step 6 |

## Related

- [Content Import architecture](../architecture/content-import.md): how it works and why
- [#210](https://github.com/av3000/japanese-vma/issues/210): scheduler runtime
- [#331](https://github.com/av3000/japanese-vma/issues/331): queue worker and Reverb release task
- [#500](https://github.com/av3000/japanese-vma/issues/500): per-article import record (follow-up)
