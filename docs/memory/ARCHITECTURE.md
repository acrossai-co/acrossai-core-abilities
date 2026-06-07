# Architecture Constraints & Boundaries

---

### 2026-06-07 - apply_filters() void-return calls are preserved API contracts

**Status**
Active

**Why this is durable**
A bare `apply_filters('hook_name', true)` with its return value discarded looks like dead code during
cleanups. It is not — it is the WordPress mechanism for third-party code to gate or observe plugin
behaviour. Removing it silently breaks integrations that hook into `hook_name`.

**Decision**
When stripping hook-wiring from `load_hooks()` or any bootstrap method, always grep for
`apply_filters` calls separately from `add_action`/`add_filter` calls and verify each one's purpose
before removing. A void-return `apply_filters` must be treated as an API surface, not dead code.

**Tradeoffs**
- Gained: Third-party integrations that gate on `acrossai_core_abilities_load` continue to work.
- Made harder: Nothing — costs one grep per cleanup.
- Reconsider: If the filter has been documented as deprecated and a migration period has elapsed.
