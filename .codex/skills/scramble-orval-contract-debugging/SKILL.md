---
name: scramble-orval-contract-debugging
description: Use when Orval-generated types in this repo do not match the intended Laravel v1 response contract, especially after Scramble regeneration or when nested arrays and lean resources collapse into wrong shapes
---

# Scramble Orval Contract Debugging

## Overview

Use this when the runtime Laravel endpoint looks right but the generated frontend types are wrong. In this repo, treat that as a contract-debugging problem first, not a frontend adapter problem.

## Workflow

1. Inspect the generated TypeScript model under `client/src/api/generated/model/**`.
2. Inspect `processor-api/api.json` to see whether the broken shape already exists in OpenAPI.
3. Inspect the backend Request, Resource PHPDoc, and controller `#[Response(...)]` annotation for the touched endpoint.
4. Decide where the fix belongs:
   - Request schema if params are wrong
   - Resource `toArray()` or PHPDoc if scalar or object fields are wrong
   - Controller `#[Response(type: 'array{...}')]` if Scramble is collapsing a lean nested response shape
5. Regenerate in order:
   - `composer openapi`
   - `npm run orval:file`
6. Re-check the generated client before touching frontend route code.

## Repo-Specific Checks

- Run backend compose commands from `processor-api/`, not repo root.
- Inspect `processor-api/api.json` before blaming Orval.
- Prefer clean array param names like `types` for new v1 endpoints; Orval preserves literal names such as `types[]`.
- For lean nested responses, Scramble may ignore Resource PHPDoc detail and need an explicit controller-level response annotation.

## Do Not Do

- Do not hand-edit generated files.
- Do not patch route code to compensate for a backend schema bug if a shared adapter or schema fix can solve it cleanly.
- Do not run Orval against a stale `api.json`.

## Quick Checklist

- Is the generated TS model wrong?
- Is `processor-api/api.json` already wrong in the same way?
- Did you inspect Request, Resource, and controller response annotation?
- Did you regenerate backend schema before Orval?
- Did you verify the regenerated model before changing frontend consumers?
