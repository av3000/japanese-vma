---
title: Reuse the Nearest v1 Pattern Before Adding Layers
impact: MEDIUM
impactDescription: structure follows real pressure, not a diagram
tags: arch, simplicity, cqrs, interfaces
---

## Reuse the Nearest v1 Pattern Before Adding Layers

**Impact: MEDIUM (structure follows real pressure, not a diagram)**

Start from the nearest v1 module and copy its shape. Add CQRS, events, a command bus, a new interface or a new folder only for a pressure the change has now: divergent read and write models, an async boundary, a second adapter, non-trivial side effects, test isolation. Simple CRUD stays simple. Add a mapper or builder when the persistence shape should not leak into the domain (`DATA-mappers-translate-shape`).

**Incorrect (hypothetical, the repo has never had it: a one-field update gains a command, a handler, an event and a port nobody else implements):**

```php
$this->commandBus->dispatch(new UpdateCatalogueTitleCommand($uuid, $title));
// UpdateCatalogueTitleHandler -> CatalogueTitleUpdated event -> CatalogueTitleWriterInterface (one implementation)
```

**Correct (the existing service and repository method, as the Catalogues module already does for updates):**

```php
$result = $this->catalogueService->updateCatalogue($catalogueUuid, $dto, $authenticatedUser);
```

Source: `.ai/guidelines/backend-v1-architecture.md` (guardrails), `processor-api/AGENTS.md` §7, the retired `backend-architecture-boundaries` skill.
