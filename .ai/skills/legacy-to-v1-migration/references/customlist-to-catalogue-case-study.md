# CustomList To Catalogue Case Study

Use the CustomList to Catalogue migration as the primary pattern for legacy endpoint decomposition.

Legacy starting point:

- Route family lived in `processor-api/routes/api.php`.
- Behavior was concentrated in `App\Http\Controllers\CustomListController`.
- Controller methods mixed query logic, auth decisions, side effects, item loading, stats loading, and response shaping.

V1 decomposition pattern:

- Rename the module to match the new domain language: `Catalogue`.
- Introduce a domain model plus list and detail DTOs instead of passing Eloquent models upward.
- Add a `CatalogueService` for orchestration and a `CataloguePolicy` for access rules.
- Split persistence concerns into repositories:
  - main catalogue repository for list and single-entity retrieval
  - item repository for list-item relations and grouped counts
- Add a mapper so persistence models do not leak into the domain layer.
- Add a dedicated item service when a catalogue can embed heterogeneous item payloads and enrichment logic.
- Keep HTTP logic in requests, controller coordination, and resources.
- Return v1 payloads with `TypedResults` and typed error handling.

Transferable lessons:

- Heavy legacy controllers usually hide multiple use cases. Extract them deliberately instead of translating line-by-line.
- Aggregate endpoints often need more than one repository. Do not force every query through a single repository if it muddies responsibilities.
- Batch enrichment belongs in services or infrastructure queries, not in resources.
- When legacy payloads are messy but widely used, keep compatibility intentionally and document any v1 cleanup.
- Feature tests should lock down visibility, list filters, sorting, pagination, and side effects such as view tracking.
