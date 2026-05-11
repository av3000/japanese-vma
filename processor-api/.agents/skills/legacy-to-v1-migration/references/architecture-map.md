# Architecture Map

Use this repository backend map when migrating endpoints:

- Routes
  - Legacy routes live in `processor-api/routes/api.php`.
  - V1 routes live in `processor-api/routes/api_v1.php`.
- HTTP layer
  - V1 controllers, requests, and resources live under `processor-api/app/Http/v1/`.
- Domain layer
  - Domain models, DTOs, enums, value objects, and errors live under `processor-api/app/Domain/`.
- Application layer
  - Services, policies, actions, jobs, and repository interfaces live under `processor-api/app/Application/`.
- Infrastructure layer
  - Persistence models, repositories, builders, and mappers live under `processor-api/app/Infrastructure/Persistence/`.

Use these modules as anchors first:

- Articles
  - Good reference for request validation, services, resources, `TypedResults`, and include flags.
- Catalogues
  - Good reference for migrating a legacy, aggregate-heavy list endpoint into split services, repositories, resources, and typed errors.

Check provider bindings explicitly:

- Repository bindings typically live in `processor-api/app/Providers/RepositoryServiceProvider.php`.
- Service bindings currently live in `processor-api/app/Providers/ArticlesServiceProvider.php` unless the module introduces a better-scoped provider.
