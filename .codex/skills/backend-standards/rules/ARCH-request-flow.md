---
title: v1 Endpoints Follow the Layered Request Flow
impact: MEDIUM
impactDescription: every endpoint reads the same way, so changes stay local
tags: arch, layers, request-flow, v1
---

## v1 Endpoints Follow the Layered Request Flow

**Impact: MEDIUM (every endpoint reads the same way, so changes stay local)**

`routes/api_v1.php` -> v1 Controller -> Request -> DTO or Value Object -> Application Service, Action or Policy -> Repository or Reader Interface -> Infrastructure Repository, Model, Mapper or Builder -> Resource -> `TypedResults`. DTOs and value objects (`EntityId`, `Viewer`, `*IncludeOptionsDTO`) carry operation inputs across the seam where they make invariants explicit. Each layer has its own rules: `ARCH-thin-controllers`, `ARCH-business-rules-in-application`, `DATA-repositories-hide-persistence`, `CONTRACT-request-per-endpoint`, `RESULT-result-typed-errors`.

**Incorrect (legacy `ArticleController::show`, retired in `635d079c`: every layer in one method):**

```php
public function show($id)
{
    $article = Article::find($id);

    if (! isset($article)) {
        return response()->json(['success' => false, 'message' => 'Requested article does not exist']);
    }

    $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
    incrementView($article, $objectTemplateId);
    $article->likes = getImpression('like', $objectTemplateId, $article, 'all');
    // ...
    return response()->json(['success' => true, 'article' => $article]);
}
```

**Correct (article show today):**

```text
routes/api_v1.php                 Route::get('articles/{id}', [ArticleController::class, 'show'])
Http/v1 ArticleController::show   ArticleDetailRequest -> ArticleIncludeOptionsDTO, EntityId, Viewer
Application ArticleService        ArticlePolicy::canView, ArticleRepositoryInterface::findByPublicUid
Infrastructure ArticleRepository  ArticleMapper::mapToDomain (persistence Article -> domain Article)
Http/v1 ArticleDetailResource     fed an ArticleDetailResultDTO; failures via TypedResults::fromError
```

Source: `processor-api/AGENTS.md` §1, `.ai/guidelines/backend-v1-architecture.md`.
