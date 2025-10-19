# ADR: Cache Invalidation for /api/orders

- Candidate: {{CAND-Q2WN}}
- Date: YYYY-MM-DD

## Context
Briefly describe the API, data freshness needs, and traffic patterns.

## Options Considered
1) TTL-based cache (per key)
2) Tag-based cache invalidation
3) Event-driven invalidation on write (pub/sub)
4) No cache (DB + indexes only)

## Decision
We choose: ...

## Rationale (Criteria)
- Simplicity
- Staleness risk
- DB offload
- Deployment/ops complexity
- Failure modes

## When NOT to use this choice (Anti-case)
...

## Rollback Plan
...

## Implementation Notes
Keys, TTLs, tags/topics, invalidation triggers.
