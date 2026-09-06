# Japanese Data Import Assets

This folder contains the PostgreSQL-side import manifest used by `php artisan app:import-japanese-data`.

- Normal deploys create the Japanese tables with Laravel migrations only.
- `php artisan app:setup` runs the import as its last step; the import can also run on its own.
- The import replays the archived SQL dump data into PostgreSQL-safe inserts and records completion in `environment_bootstrap_runs`. A second run is a no-op unless `--allow-rerun` is passed.
- The legacy MySQL-style dump files remain under `database/japanese-data/` as the import source of truth and are no longer executed as migrations.
