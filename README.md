# Full‑Stack PHP + Vue Interview Task

Welcome! This package contains a self‑contained exercise to evaluate real‑world skills.
**Replace every occurrence of `{TOKEN}` with your unique token provided by the recruiter.**

## Deliverables (within 8h)
- Git repo with natural commit history (≥10 incremental commits), branch name: `candidate-{TOKEN}`.
- Short Loom/ScreenRec video (≤15min): run locally, walk through code, explain decisions.
- `README.md` (1–2 pages): architecture, *before/after* benchmarks, key decisions.
- Tests (PHPUnit for backend logic if you add it, and Vitest/Jest for Vue store/component).
- `bench/bench.csv` + a screenshot of your profiler/EXPLAIN.
- `WHY.md`: write 5 key decisions + trade‑offs.

## Structure
```
backend/          PHP (SQLite) API with deliberate performance issues
frontend/         Vue 3 + Vite skeleton (issues in UX/state)
alg/              Algorithm & data-gen stubs
code_review/      A bad PHP controller for review (no coding needed here)
ADR/              ADR one‑pager template
bench/            Simple benchmarking script
```

## What to improve
### Backend (PHP, SQLite)
Endpoint: `GET /api/orders?user_id=1&page=1&per_page=20`
Deliberate issues include: N+1 style lookups, bad pagination, no proper indexes, and inefficient date filtering.

**Your tasks:**
1. Diagnose with `EXPLAIN QUERY PLAN` and add indices via migration (`backend/db/migrations/*.sql`).  
2. Fix N+1 pattern and pagination logic.  
3. Add sane caching strategy (explain your invalidation in `ADR/cache-invalidation.md`).  
4. Provide *before/after* benchmarks (`bench/bench.php` → `bench/bench.csv`).

### Frontend (Vue 3 + Vite)
Page: Orders list using the API above.
Fix UX/state:
- Show skeletons and clear empty state.
- Run calls in parallel where applicable (Promise.all), and **cancel in-flight requests** on filter changes (AbortController).
- Add **optimistic update** + **rollback** for order note editing.
- Add **offline cache** for last successful response using Pinia + localStorage/IndexedDB.

### Algorithm (3-in-5)
Implement two versions:
- A correct `O(n log n)` approach (sort + sliding window).
- A streaming/big‑data friendly approach (bucket by user, one‑pass sliding window).
Provide a small benchmark over generated data (see `alg/`).

### Code Review (no coding)
Review `code_review/BadController.php` by adding up to **20 prioritized comments** (High/Medium/Low).

### ADR
Fill `ADR/cache-invalidation.md` (one page): options, criteria, choice, anti-case, rollback plan.

## Run backend
```
cd backend
php -S 127.0.0.1:8080 -t public
```
Seed data (optional) to increase rows:
```
php db/seed.php 50000   # generates ~50k orders for multiple users
```

## Run frontend
```
cd frontend
npm install
npm run dev
```

## Bench
Update `TARGET_URL` in `bench/bench.php` then:
```
php bench/bench.php 50 > bench/bench.csv
```
### Benchmark (Before vs After)

- Endpoint: GET /api/orders?user_id=1&page=1&per_page=20
- Runs: 50 requests per run

| Run | Context             | Mean (ms) | Median (ms) | p95 (ms) | Max (ms) |
|----:|---------------------|----------:|------------:|---------:|---------:|
| A   | after + cached      |   2.05    |     0.68    |   2.12   |   55.19  |
| B   | after (uncached?)   |  19.73    |    17.70    |  18.86   |  110.06  |

- Speedup (median): ~26× (A vs B)
- Speedup (mean): ~9.6× (A vs B)

Notes:
- First request in each run is cold (warming, I/O, JIT), hence the spike.
- A uses server-side SQLite cache with 60s TTL; B measured with cold cache or invalidation.

## Token
- Use `{TOKEN}` in: branch name, first commit message header, and at top of your benchmark CSV (script will add it if you set BENCH_TOKEN env).

Good luck — focus on reasoning, profiling artifacts, and trade‑offs.
# candidate-CAND-Q2WN
# Final Submission