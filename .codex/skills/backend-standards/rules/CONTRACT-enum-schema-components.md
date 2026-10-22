---
title: Public API Enums Are Backed Enums Shared by Request and Resource
impact: MEDIUM
impactDescription: one generated client model instead of request-local aliases
tags: contract, enums, openapi, orval
---

## Public API Enums Are Backed Enums Shared by Request and Resource

**Impact: MEDIUM (one generated client model instead of request-local aliases)**

When a value set is part of the public contract, model it as a backed PHP enum, validate it with `Rule::enum(...)` in the Request, and return the enum from the Resource. Scramble then emits one reusable component (`#/components/schemas/ObjectTemplateType`) that both sides `$ref`, and Orval generates one shared model. Pin it with an OpenAPI test (`CommentContractOpenApiTest::test_the_entity_type_vocabulary_is_shared_between_request_and_response`).

**Incorrect (`47610a4d^:CommentResource`: the response answers with the type title while the request takes the enum, so the client gets two vocabularies):**

```php
'entity_uuid' => $comment->getEntityUuidValue(),
'entity_type' => $comment->getEntityType()->getTitle(),
```

**Correct:**

```php
// StoreCommentRequest
'entity_type' => ['required', Rule::enum(ObjectTemplateType::class)],

// CommentResource returns the enum instance, so api.json has
// "entity_type": { "$ref": "#/components/schemas/ObjectTemplateType" } on both sides.
'entity_type' => $comment->getEntityType(),
```

Source: `processor-api/AGENTS.md` §5.
