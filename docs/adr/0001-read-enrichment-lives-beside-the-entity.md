# Read enrichment lives beside the entity, not on it

A domain model is constructed complete from its own row. Data that belongs to a query result rather than to the row - subtree sizes, reply previews, page totals, facet counts - lives on a `*ListItemDTO` handed back alongside the model, and paginated reads go through a `*ReaderInterface` while the repository keeps identity reads and writes.

## Context

The Comments module briefly did the opposite. `Comment` took `repliesCount` and `replies` as constructor arguments defaulting to `0` and `[]`; the mapper always passed those defaults, and a clone-based `withReplies()` corrected them afterwards for the one code path that had loaded the data.

The mutation itself was fine - a clone-and-return wither is the same idiom `DateTimeImmutable` uses, and this codebase already relies on it for timestamps. The problem was the state it left behind. `repliesCount: 0` meant either "this comment genuinely has no replies" or "replies were never loaded for this instance", and nothing in the type distinguished them. Two repository methods returned the uncorrected version, so any future caller reading `replies_count` off a comment fetched by id would have shipped a zero for a forty-reply thread and had no way to notice. The same defect had a second face: every reply nested inside a root carried two fields it would never use, because a reply's own replies are not part of the contract.

## Decision

Strip read-only enrichment off domain entities. It travels on a dedicated DTO that wraps the entity, built by the use case that performed the read.

Pair that with the reader split already recorded in `processor-api/AGENTS.md`: list reads with filters or pagination go through a `*ReaderInterface`, identity reads and writes stay on the repository.

## Considered options

**Keep the wither, pass the data into the mapper instead.** Removes the placeholder defaults but leaves one class modelling two concepts - an aggregate with thread metadata, and a bare leaf that can never have any. Reply instances would still carry dead fields.

**Fold list methods into the repository, returning an envelope.** The two external reference solutions this architecture borrows from - referred to as Project K and Project B - do exactly this: one repository per aggregate, with search methods returning a result envelope beside the get-by-id that returns the aggregate. It is a legitimate shape, and both of those codebases hold the invariant above without any reader abstraction.

It was not chosen here because this repository had already written the reader rule down and implemented it for Articles, because the comment reply walk is a recursive CTE with window functions rather than a five-line query composition, and because a narrower port makes the test doubles smaller. That is a placement preference, not a correctness argument.

## Consequences

The invariant is the load-bearing half and holds under either shape; the reader is the reversible half. A reader that still hands back a half-populated entity has not fixed anything.

Cost: one more interface and a handful of DTOs per module that needs a list read. For a module whose list is a plain ordered query, that is ceremony, and folding the read into the repository while still keeping enrichment off the entity is the better trade.
