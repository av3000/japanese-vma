# State Management

Use the smallest state tool that matches ownership and change frequency.

## Server State

React Query owns server state:

- fetched data
- loading/pending/error status
- pagination
- cache invalidation
- optimistic or server-confirmed mutation outcomes

Do not duplicate server pagination or loading state in routes when React Query fits.

## Local UI State

React local state owns local UI state:

- modal open state
- selected item
- temporary form values
- draft display flags
- current tab or expanded row when it is not shared globally

Keep this near the feature orchestration seam.

## Context

Context is acceptable for stable app/session dependencies:

- auth/session
- permissions
- environment/config
- theme/locale when infrequently changed

Auth context is acceptable while auth changes infrequently. It is not selector-based: consumers rerender when provider value changes.

## Zustand Or Selector Store

Use Zustand or another selector-based store only when:

- shared client state changes often
- many independent consumers need slices
- broad rerenders are measured or clearly likely

Examples: modals registry, dashboard filters, editor state, command palette, selected item sets.

## Redux

Do not revive Redux patterns for new touched or migrated code.
