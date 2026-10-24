---
title: Resources Shape the Response, They Do Not Decide
impact: MEDIUM
impactDescription: business decisions stay testable in the application layer
tags: contract, resources, presentation
---

## Resources Shape the Response, They Do Not Decide

**Impact: MEDIUM (business decisions stay testable in the application layer)**

A Resource maps an already-decided result (a domain model or a `*ResultDTO`) to the public field names. It does not query, load relations, check permissions or compute business state; the use case puts those values on the DTO first.

**Incorrect (`47610a4d^:CommentResource`: the Resource carried an include flag and a replies array of its own, documented `replies`, and always emitted `[]` because no caller ever passed the data):**

```php
private bool $include_replies;

private array $replies;

public function __construct(
    $comment,
    bool $include_replies = false,
    array $replies = []
) {
    parent::__construct($comment);
    $this->include_replies = $include_replies;
    $this->replies = $replies;
}

// toArray()
'replies' => [],
```

**Correct (the use case builds `CommentListItemDTO` with the reply count and previews; the Resource only maps it):**

```php
final readonly class CommentListItemDTO
{
    public function __construct(
        public Comment $comment,
        public int $repliesCount,
        public array $replyPreviews,
    ) {
    }
}
```

Source: the retired `backend-architecture-boundaries` skill ("Resources are the correct edge for API shape, but not for business decisions"), `processor-api/AGENTS.md` §3.
