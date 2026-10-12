# Generated Client Boundaries

Generated Orval clients are the default transport adapter for documented v1 endpoints.

## Decision Rule

1. Check whether a documented v1 endpoint exists.
2. Check whether Orval generated a usable client and model.
3. If usable, call the generated client directly or through a feature hook that adds behavior.
4. If generated output is wrong, inspect backend schema/OpenAPI source before adding frontend coercion.
5. If v1 is missing, prefer backend/schema work before frontend workaround.
6. If frontend must proceed temporarily, use one typed temporary adapter with status TODO.

## Wrapper Must Add Behavior

A wrapper earns its keep when it owns:

- query keys
- generated query-key helper reuse
- pagination flattening
- cache invalidation
- payload mapping
- response mapping
- legacy shielding
- unstable generated output isolation

A wrapper that only renames a generated client is shallow.

## Temporary Adapter Comment

Required shape:

```ts
// TODO(JP-123): temporary legacy adapter because <reason>. Target: <v1 endpoint or generated client>. Remove when <condition>.
```

If no issue exists:

```ts
// TODO(no issue): temporary legacy adapter because <reason>. Target: <v1 endpoint or generated client>. Remove when <condition>.
```

Do not leave a temporary adapter with a vague TODO.
