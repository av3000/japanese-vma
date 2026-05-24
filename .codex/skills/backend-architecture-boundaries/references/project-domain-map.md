# Project Domain Map

This map separates observed facts from inferred or uncertain mappings. Re-check current files before using it as implementation evidence.

## Observed Architecture Names

| Layer | Observed namespaces/files |
| --- | --- |
| HTTP edge | `processor-api/app/Http/v1/**/Controllers`, `Requests`, `Resources`, `Concerns` |
| Application | `processor-api/app/Application/**/Services`, `Actions`, `Policies`, `Jobs`, `Interfaces/Repositories` |
| Domain | `processor-api/app/Domain/**/Models`, `DTOs`, `ValueObjects`, `Enums`, `Errors`, `Factories`, `Queries`, `Support` |
| Infrastructure | `processor-api/app/Infrastructure/Persistence/Models`, `Repositories`, `Builders`; `Infrastructure/Pdf`; `Infrastructure/Auth` |
| Shared | `processor-api/app/Shared/Http/TypedResults`, `Shared/Results`, shared enums/errors |
| Composition root | `processor-api/app/Providers/RepositoryServiceProvider.php`, `PdfServiceProvider.php`, feature providers |

## Observed Domain Terms

| Term | Observed likely layer/namespace | Notes |
| --- | --- | --- |
| Article | `Domain/Articles`, `Application/Articles`, `Http/v1/Articles`, `Infrastructure/Persistence` | Current v1 module has domain models/DTOs, application services/actions, resources, mappers, and repositories. |
| Catalogue | `Domain/Catalogues`, `Application/Catalogues`, `Http/v1/Catalogues`, `Infrastructure/Persistence` | Uses `CatalogueDetailDTO`, list/picker result DTOs, policy, item service, resources, repositories. |
| Item / catalogue item | `Application/Catalogues/Services/CatalogueItemService`, `CatalogueItemRepositoryInterface`, catalogue DTOs/resources | "Item" is a payload/domain word in catalogue flows; avoid inventing broader names unless files prove it. |
| Kanji | `Domain/JapaneseMaterial/Kanjis`, `Application/JapaneseMaterial/Kanjis`, `Http/v1/JapaneseMaterial/Kanjis`, persistence mapper/repository | Query criteria and value objects are domain-side; repository and mapper are infrastructure-side. |
| Word | `Domain/JapaneseMaterial/Words/Models/Word`, `Infrastructure/Persistence/Models/Word`, `WordMapper` | Domain word preserves parsed and raw compatibility fields; mapper translates persistence rows. |
| Comment | `Domain/Comments`, `Application/Comments`, `Http/v1/Comments`, `CommentRepositoryInterface` | Some controller seams still resolve entity IDs through Article/Catalogue services. |
| Engagement | `Domain/Engagement`, `Application/Engagement`, `Http/v1/Engagement`, engagement repositories | Includes likes, views, downloads, hashtags, stats, actions like load stats and record download. |
| Download | `Application/Engagement/Actions/RecordDownloadAction`, `DownloadRepositoryInterface`, `Domain/Engagement/DTOs/DownloadCreateDTO` | Download recording is a side-effect action, not PDF rendering itself. |
| PDF export | `Application/Articles/Services/*PdfExportService`, `Application/Catalogues/Services/*PdfExportService`, `Application/Pdf/PdfRendererInterface`, `Infrastructure/Pdf/DompdfPdfRenderer` | Feature export services prepare export data and call renderer port; HTTP response is handled by response factory. |
| User/auth | `Domain/Users`, `Application/Users/Actions`, `Application/Auth/Interfaces`, `Infrastructure/Auth`, `Http/v1/Auth` | Auth controller delegates to actions; registration also creates default catalogues. |

## Observed Boundary Risks

- Controllers can drift into orchestration when they resolve entity IDs, build response arrays, enrich counts/hashtags, or call multiple services.
- Application services can grow broad when one feature service owns create/read/update/delete/export/cleanup/batch enrichment.
- PDF/export services risk absorbing authorization, data preparation, rendering, download tracking, and response concerns.
- Repository interfaces still contain some transitional leakage, such as raw arrays or framework paginator types.
- Mappers can become tempting places for visibility or include policy because they already inspect relations/options.
- Resources are the correct edge for API shape, but not for business decisions.

## Uncertainty Notes

- This map is a snapshot from representative files, not an exhaustive audit.
- Some current code intentionally preserves legacy behavior and response shapes during v1 migration.
- The repo uses Laravel, but the architecture rule is not "do Laravel folders everywhere"; it is "preserve current repo boundaries and dependency direction."
- Validate exact current route names, DTO names, and response envelopes before changing contracts or generated API schema.
