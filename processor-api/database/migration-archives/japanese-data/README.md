# Archived Japanese Data Migrations

These legacy migration files used `DB::unprepared()` against MySQL-style dump files.

- They are archived here so Laravel no longer auto-discovers them during normal `php artisan migrate --force`.
- The Japanese tables are now created by a schema-only migration.
- The actual dataset import is handled manually by `php artisan app:import-japanese-data`.
