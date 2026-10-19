# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. In this repository, never use `php artisan test`: run tests in the isolated Docker test lane from `processor-api/`, `docker compose -f docker-compose.test.yml -p jvma<issue> exec -T test-runner composer test -- ...`, with a specific filename or filter. Lane setup is in `processor-api/AGENTS.md` §6.
