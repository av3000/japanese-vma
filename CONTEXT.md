# Japanese VMA

Japanese VMA is a Japanese learning platform with content, community, and study-resource workflows. This context captures project language that affects product and architecture decisions.

## Language

**Scalable Backend Architecture**:
A v1 backend architecture that can grow in traffic, feature count, and maintenance load without spreading business rules across HTTP, application, domain, persistence, and presentation seams.
_Avoid_: Microservices by default, performance-only scalability, folder-count scalability

**Robust Modular Monolith**:
A backend shape strong enough for moderate product scale without requiring CQRS, event sourcing, or service decomposition as the default next step.
_Avoid_: CQRS by default, event sourcing by default, microservice split by default

**Module-Seams-First Audit**:
An architecture review order that judges v1 module seams before runtime mechanisms such as caching, queues, indexes, or new infrastructure.
_Avoid_: Runtime-first audit, infrastructure-first audit

**Domain-Feature Audit**:
An architecture review structure that audits each v1 domain feature flow before comparing repeated module-type smells across the backend.
_Avoid_: Layer-only audit, controller-only audit

**Content Source**:
An external publisher the platform imports articles from, such as NHK News. Each one has a key, one adapter class that reads it, and an enabled switch.
_Avoid_: Scraper, feed, provider

**Import Run**:
One execution of the Content Import against one Content Source: list what is new, filter it, tag it, create what survives. Every non-dry run is recorded with its counts.
_Avoid_: Scrape, sync, crawl

**Imported Article**:
An Article whose origin is `imported`: an excerpt (headline, the publisher's own lead, link) authored by the seeded system user and linked to its Content Source and the id the source knows it by.
_Avoid_: Auto-generated article, scraped article, bot post

## Relationships

- **Scalable Backend Architecture** is the audit lens for v1 backend work before proposing runtime or refactoring improvements.
- **Scalable Backend Architecture** treats development scalability as the first gate for runtime scalability.
- A **Robust Modular Monolith** is the target shape for **Scalable Backend Architecture** in the current v1 backend.
- A **Module-Seams-First Audit** is the review order for deciding whether the v1 backend is a **Robust Modular Monolith**.
- A **Domain-Feature Audit** is the structure for applying a **Module-Seams-First Audit** to the v1 backend.
- An **Import Run** reads exactly one **Content Source** and creates zero or more **Imported Articles**.
- Only one **Import Run** per **Content Source** runs at a time; a second one is refused while the first is in progress.
- An **Imported Article** is still an Article: it goes through the same creation path, processing and tagging as one a person writes; only its origin, author and attribution differ.

## Example dialogue

> **Dev:** "Should we split v1 into more infrastructure because we want scalability?"
> **Domain expert:** "Not first. For this project, **Scalable Backend Architecture** means the modular monolith has clear seams before we add more runtime machinery."

> **Dev:** "Should we introduce CQRS or event sourcing so the backend is scalable?"
> **Domain expert:** "No. The target is a **Robust Modular Monolith** unless a concrete read/write or audit-history pressure proves the extra machinery is worth it."

> **Dev:** "Should we start by adding cache and indexes?"
> **Domain expert:** "No. Use a **Module-Seams-First Audit**: first check whether the v1 seams are stable, then add runtime mechanisms where pressure exists."

> **Dev:** "Should we audit controllers first across the whole backend?"
> **Domain expert:** "Start with a **Domain-Feature Audit**. Article scalability is the whole feature flow, not the controller alone."

## Flagged ambiguities

- "scalable architecture" was resolved to mean both runtime scalability and development scalability, with development scalability as the first gate.
- "scalable" does not mean tens or hundreds of thousands of active users for the current audit; it means robust v1 code that teaches and preserves strong architecture patterns at moderate product scale.
- The audit order was resolved as module seams first, runtime mechanisms second.
- The audit structure was resolved as domain feature first, module type inside each feature.
- "auto-generated article" was resolved to **Imported Article**: nothing is generated, the text is the publisher's own excerpt.
- "source" on an article means its **Content Source** for an **Imported Article**; the free-text `source_link` a person enters is not a Content Source.
