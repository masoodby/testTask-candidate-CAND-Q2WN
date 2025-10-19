# Algorithm Task: 3-in-5

Find users who, within **any 5-minute window, have at least 3 purchases**.

Deliver:
- `sol_sort_sliding.php` (O(n log n))
- `sol_streaming.php` (bucket+stream friendly)
- `gen.php` (data generator) and `bench_alg.php` (simple runtime compare)

Document why checking **adjacent pairs** is insufficient for the ≥3 requirement.
