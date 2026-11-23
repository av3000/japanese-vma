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

**Staff**:
Anyone acting in the admin panel on content or accounts that are not their own, by holding a panel permission or the `admin` role. In code, the moderation policies still mean `AuthenticatedUser::$isAdmin` by "staff" until #551 makes them permission-aware.
_Avoid_: moderator, operator, or admin for a non-admin role holder

**Panel**:
The Filament admin app at `/admin` (`app/Filament`), the only surface where staff act. Entry needs any permission from the panel permission catalogue.
_Avoid_: admin (for the app itself), backoffice, dashboard (that is one panel page)

**Moderation**:
A staff write on someone else's content: changing an article's status or publicity, locking a post, removing a comment, catalogue or user-authored sentence. Each module's moderation goes through its `{Module}ModerationService` and writes one **Activity log** row.
_Avoid_: admin action, editing (staff remove, they do not rewrite)

**Activity log**:
The append-only record of staff actions, one row per action with actor, source, subject, before/after snapshot and context (table `admin_activities`).
_Avoid_: admin activity log, audit trail, history

**Known list**:
A catalogue that tracks a learner's own progress (known kanji, words and so on) rather than content others see. Moderation never removes one.
_Avoid_: progress list, system catalogue

**User-authored sentence**:
A sentence a user wrote (`sentences.user_id` set), as opposed to an imported dictionary sentence. Staff may remove it but never edit it; imported sentences change only through the importer.
_Avoid_: custom sentence, user sentence

## Relationships

- **Scalable Backend Architecture** is the audit lens for v1 backend work before proposing runtime or refactoring improvements.
- **Scalable Backend Architecture** treats development scalability as the first gate for runtime scalability.
- A **Robust Modular Monolith** is the target shape for **Scalable Backend Architecture** in the current v1 backend.
- A **Module-Seams-First Audit** is the review order for deciding whether the v1 backend is a **Robust Modular Monolith**.
- A **Domain-Feature Audit** is the structure for applying a **Module-Seams-First Audit** to the v1 backend.
- **Staff** act only in the **Panel**; every **Moderation** writes exactly one **Activity log** row.
- **Moderation** never removes a **Known list** and never edits a **User-authored sentence**.

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
- "admin" has three meanings in code: the `admin` role (a panel superuser, `User::isPanelSuperuser()`), the panel id `'admin'`, and the "Administration" permission group. Say **admin** only for the role; the app is the **Panel**.
- "staff" in the `*ModerationPolicy` classes currently means the `admin` role only, not any permission holder; #551 tracks making them permission-aware.
