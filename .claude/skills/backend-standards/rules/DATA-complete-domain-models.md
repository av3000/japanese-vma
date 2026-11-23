---
title: Domain Models Are Constructed Complete; Read Enrichment Lives Beside Them
impact: HIGH
impactDescription: no value that cannot be told apart from the truth
tags: data, domain-models, dto, adr-0001
---

## Domain Models Are Constructed Complete; Read Enrichment Lives Beside Them

**Impact: HIGH (no value that cannot be told apart from the truth)**

A mapper builds a domain model from its own row, complete. Per-read enrichment (counts, reply previews, page totals, facet counts) goes on a `*ListItemDTO` beside the model, never into defaulted constructor arguments filled in later by a `with*()` wither. A model that can be handed out half-populated gives every reader a `repliesCount: 0` that means either "no replies" or "never loaded". See `docs/adr/0001-read-enrichment-lives-beside-the-entity.md`.

**Incorrect (the `Comment` shape ADR 0001 describes and removed; reconstructed from the ADR, the wither never reached a merged commit):**

```php
public function __construct(
    // ... columns of the comment row
    private int $repliesCount = 0,
    private array $replies = [],
) {
}

public function withReplies(array $replies, int $count): self
{
    $clone = clone $this;
    // ...
}
```

**Correct (`CommentListItemDTO`):**

```php
final readonly class CommentListItemDTO
{
    /** @param array<int, Comment> $replyPreviews oldest first, empty when previews were not requested */
    public function __construct(
        public Comment $comment,
        public int $repliesCount,
        public array $replyPreviews,
    ) {
    }
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`, ADR 0001.
