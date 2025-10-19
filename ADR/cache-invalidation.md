# ADR: Cache Invalidation for /api/orders

- Candidate: {{CAND-Q2WN}}
- Date: 2025-10-19

---

## Context
The `/api/orders` endpoint retrieves paginated order data by `user_id` and optional date filters.  
Typical usage pattern involves frequent reads (list views, filters, pagination) and relatively rare writes (new order, update, payment).  
Performance profiling showed the endpoint was CPU-bound on repeated SELECT queries even with indexes.  
A lightweight caching strategy was required to reduce read load while maintaining acceptable data freshness.

---

## Options Considered
1. **TTL-based cache (per key, SQLite backend)** – simple time-based expiry; invalidation on writes.  
2. **Tag-based cache invalidation** – group cache entries by tag (e.g. `orders_user_1`) for selective clearing.  
3. **Event-driven invalidation (pub/sub)** – use message bus or Redis channels to invalidate distributed caches.  
4. **No cache (DB + indexes only)** – rely purely on query optimization.

---

## Decision
✅ **Chosen:** Option 1 – **TTL-based per-key cache with user-specific prefixes and optional explicit invalidation.**

---

## Rationale (Criteria)
| Criterion | Evaluation |
|------------|-------------|
| **Simplicity** | Single SQLite table, minimal dependencies. |
| **DB offload** | Reduces duplicate reads for same parameters by >10×. |
| **Staleness risk** | Acceptable (< 60 s); explicit invalidation handles writes. |
| **Deployment complexity** | None — cache stored alongside app DB. |
| **Failure modes** | Safe degradation — if cache table missing or corrupted, system falls back to DB reads. |

---

## When NOT to use this choice (Anti-case)
- Systems with **high-frequency writes** (e.g. stock trading, live counters) where data must always be fresh.  
- **Distributed multi-node deployments** without shared SQLite; cache coherence would break.  
- APIs returning **sensitive real-time data** (e.g. payments in progress).

---

## Rollback Plan
1. Disable caching layer by removing calls to `cache_get()` and `cache_set()`.  
2. Drop the `cache` table safely (`DROP TABLE cache;`).  
3. Retain indexes for performance.  
4. Revert to direct DB reads — no downtime or schema dependency.

---

## Implementation Notes
- **Cache Key Format:** `orders:u{user_id}:p{page}:per{per}:s{start}:e{end}`  
- **TTL:** 60 seconds (configurable in `cache.php`)  
- **Storage:** SQLite table `cache(cache_key TEXT PRIMARY KEY, value TEXT, expires_at INTEGER)`  
- **Invalidation Trigger:** On any `INSERT`, `UPDATE`, or `DELETE` to `orders`, `order_items`, or `payments`,  
  call `invalidate_user_orders_cache($userId)` → deletes all keys with prefix `orders:u{userId}:`.  
- **Fallback Behavior:** If `cache_get` or table unavailable → automatic DB query execution.

---