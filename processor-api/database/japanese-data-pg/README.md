# Japanese Data Import Assets

This folder contains the PostgreSQL-side import manifest used by `php artisan app:import-japanese-data`.

- Normal deploys create the Japanese tables with Laravel migrations only.
- The manual import command replays the archived SQL dump data into PostgreSQL-safe inserts and records completion in `environment_bootstrap_runs`.
- The legacy MySQL-style dump files remain under `database/japanese-data/` as the import source of truth and are no longer executed as migrations.
