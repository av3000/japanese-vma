# Review Checklist

Use this checklist before recommending or finalizing a backend architecture change.

## Evidence

- [ ] Read root `AGENTS.md` and `processor-api/AGENTS.md`.
- [ ] Read touched controller/request/resource and application service/action/policy.
- [ ] Read repository interface, repository implementation, mapper/builder, and provider binding when persistence is involved.
- [ ] Checked nearby tests or named the verification gap.
- [ ] Produced a file/class flow map before abstract commentary.

## Boundary Checks

- [ ] Domain has no HTTP, framework, ORM, database, resource, or persistence-model dependency.
- [ ] Controller delegates orchestration instead of enriching or coordinating multiple persistence calls.
- [ ] Application owns use-case orchestration, policy checks, transactions, and side effects.
- [ ] Repository hides persistence details or clearly documents accepted transitional leakage.
- [ ] Mapper translates shape and does not contain business policy.
- [ ] Resource/presenter owns outbound API shape and does not contain business rules.
- [ ] Service responsibilities share one reason to change.
- [ ] Simple CRUD stays simple unless current complexity justifies more structure.

## Reporting

- [ ] Separate observed facts from inferred concerns.
- [ ] Label acceptable transitional code differently from real architecture debt.
- [ ] Preserve legacy behavior unless the task explicitly migrates it.
- [ ] Name payload/response/auth changes explicitly.
- [ ] Prefer current repo conventions over generic folder templates.
