# Word Extraction Performance Prototype Design

Date: 2026-05-25
Status: Approved design for prototype phase

## Goal

Explore realistic ways to improve Japanese word extraction performance without committing to a final production design too early.

This phase is for learning first.

The prototype should tell us whether the best next step is:
- a DB-improved version of the current approach
- a worker-local in-memory lookup
- or a stronger reason to introduce Redis later

## Current Problem

Current extraction flow in `WordExtractionService`:
- scans text character by character
- grows a candidate string
- asks the repository whether any word starts with that prefix
- asks again whether that candidate is a full word
- may ask again for the winning word id

Current repository seam in `WordRepositoryInterface`:
- `hasWordStartingWith(string $prefix): bool`
- `findIdByWord(string $word): ?int`

This creates many DB round-trips in the hot path.

Current DB shape makes this worse:
- `japanese_word_bank_long` has no index on `word`
- only `id` and `uuid` are indexed

So the current problem is both:
- too many lookups
- weak table support for those lookups

## Locked Semantics

Prototype comparisons must preserve current extraction behavior:
- longest match wins
- duplicate matched words are deduplicated
- unmatched text is skipped
- no morphology or conjugation awareness is added in this phase

This is a performance exploration, not a language-rule redesign.

## Prototype Scope

Prototype boundary:
- use the real `WordExtractionService` seam
- do not run the full `ProcessArticleWordsJob` end-to-end as the main benchmark harness

The prototype should compare alternative lookup backends behind the same extraction behavior.

## Options To Compare

### 1. Current Baseline

Use the current DB-driven extractor behavior as the control.

Purpose:
- establish current query count
- establish current runtime
- confirm current matching output

### 2. DB Baseline A: Minimal DB Repair

Compare a DB-backed version that assumes:
- index on `japanese_word_bank_long.word`

Purpose:
- measure the benefit of the obvious missing schema fix

### 3. DB Baseline B: Stronger DB-Centric Redesign

Compare a DB-backed version that goes further than indexing:
- remove duplicate exact-word lookups where possible
- reduce per-candidate chatty queries
- allow batched or wider candidate fetching if that fits the seam cleanly

Purpose:
- measure whether a DB-first redesign is enough before introducing memory-heavy structures

### 4. Worker-Local Lookup A: Hash Maps

Prototype a worker-local snapshot using simple in-memory maps such as:
- `exactWord -> id`
- `prefix -> true`

Purpose:
- get the simplest in-memory benchmark quickly

### 5. Worker-Local Lookup B: Trie

Prototype a worker-local trie-like structure with:
- character-by-character traversal
- optional terminal `wordId`

Purpose:
- measure the natural structure for longest-match extraction

## Dictionary Loading Modes

Support both:

### Partial Snapshot

Load only words needed for the benchmark corpus.

Purpose:
- very fast local loop
- useful while shaping the harness

### Full Snapshot

Load the full dictionary from `japanese_word_bank_long`.

Purpose:
- real signal for worker-local feasibility
- required for final prototype conclusions

The design should treat full-dictionary load as the real production-shaped signal.

## Benchmark Corpus

Use a mixed corpus:

### Synthetic Cases

Include short controlled texts that probe:
- longest-match behavior
- unmatched text
- repeated words
- mixed short and longer candidate paths

### Real Article Samples

Use real `title_jp + content_jp` article text snapshots.

Purpose:
- measure actual workload shape
- avoid tuning only for artificial inputs

## Batch Simulation

Do both:

### Sequential Batch

Run a smaller multi-article batch in one worker process.

Purpose:
- show benefit after warmup
- show whether repeated extraction reuses local memory effectively

### Multi-Worker Simulation

Approximate several workers, each with its own local snapshot.

Purpose:
- show duplicated warmup cost
- show duplicated memory cost
- stress the “many articles created around the same time” concern

This phase does not need a 100-article batch immediately.
Smaller controlled batches are acceptable if they still surface warmup reuse and duplication costs clearly.

## Required Measurements

Each benchmark variant should capture:
- query count
- extraction runtime
- warmup runtime
- peak memory usage
- output correctness parity with current semantics

For failure-path exploration, also capture:
- fallback behavior outcome
- fail-fast behavior outcome

## Safety Requirements

The prototype must explicitly prove:
- cursor always advances
- extraction always terminates
- unmatched text does not create accidental loops

This is required for both DB-backed and worker-local variants.

## Failure Behavior To Explore

Compare both behaviors for snapshot build failures:

### Fallback

If worker-local snapshot build fails:
- fall back to current DB extractor

### Fail-Fast

If worker-local snapshot build fails:
- stop cleanly
- report failure clearly

The prototype should measure both and recommend one.

## Reporting

Prototype output should include:

### Human Summary

Console summary with clear comparison by extractor type.

### Machine-Readable Artifact

Structured output that records:
- extractor type
- corpus type
- loading mode
- batch mode
- warmup time
- extraction time
- query count
- peak memory
- outcome
- failure mode used

Purpose:
- keep results comparable between runs
- use the results later in the production design decision

## Decision Rule After Prototype

The follow-up design should recommend:

The simplest option that meets a clear performance target.

Not:
- the cleverest option
- the most infrastructure-heavy option
- the fastest option at any operational cost

This rule exists to avoid overbuilding before the measurements justify it.

## Redis Position In This Phase

Redis is not the first prototype target.

Redis should remain a later option if the prototype shows that:
- worker-local warmup cost is too high
- worker-local memory duplication is too expensive
- or shared cache reuse across workers becomes clearly worth the added complexity

The prototype must end with an explicit statement:
- Redis still unnecessary
- Redis optional next step
- Redis justified

## Recommended Outcome Shape

The preferred current path is:
- prototype first
- learn from benchmark data
- then write the production design using evidence

The expected comparison matrix is:
- current DB path
- DB baseline with minimal schema fix
- DB baseline with stronger query redesign
- worker-local hash-map lookup
- worker-local trie lookup

## Out Of Scope

Not part of this prototype phase:
- changing extraction semantics
- adding morphology or conjugation awareness
- final Redis implementation
- wiring a production rollout directly into article jobs
- broad unrelated refactors outside the extraction seam

## Open Questions Resolved In This Design

- Matching semantics should stay the same: yes
- Strategy shape should stay hybrid overall: yes
- First learning step should be a prototype: yes
- First in-memory target should be worker-local: yes
- Prototype should compare DB improvements too: yes
- Prototype should compare both hash-map and trie structures: yes
- Prototype should use both synthetic and real text corpora: yes
- Prototype should simulate both sequential reuse and multi-worker duplication: yes
- Prototype should report both console and machine-readable results: yes
- Final recommendation rule should favor the simplest option that meets the target: yes
