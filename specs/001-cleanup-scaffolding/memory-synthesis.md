# Memory Synthesis

## Current Scope

Strip all WPBoilerplate scaffolding from `acrossai-core-abilities`: delete `admin/`, `public/`, `src/`, `build/`; update `includes/Main.php`, `composer.json`, `phpstan.neon.dist`, `phpcs.xml.dist`, `package.json`, `.distignore`, `.specify/memory/constitution.md`. Affected modules: bootstrap layer (`includes/Main.php`), build tooling, static analysis config, constitution.

---

## Relevant Decisions

- **Remove `Admin\` and `Public\` PSR-4 namespaces from composer.json** (Reason Included: directly affects autoloader correctness after directory deletion, Status: active, Source: planning doc CHANGE-6)
- **Update constitution Boot Flow Rule and Admin Rule to forward-looking guidance** (Reason Included: current rules mandate layers being deleted; must be amended before plan violates them, Status: active, Source: spec FR-011)
- **Keep `vendor/` and `node_modules/` untouched** (Reason Included: scope boundary; regeneration is a developer post-step, Status: active, Source: spec Assumptions)
- **Keep all AI/agent tooling files** (Reason Included: `.agents/`, `.claude/`, `.specify/`, `scripts/` are dev infrastructure not runtime, Status: active, Source: spec FR-012)
- **Constitution bump: `1.0.0` → `1.1.0` (MINOR)** (Reason Included: adding optional-layer guidance is a new principle, not a clarification — MINOR per amendment procedure, Status: active, Source: spec Assumptions + §Governance)

---

## Active Architecture Constraints

- **PHPStan level 8 must pass after all PHP edits** (Reason Included: §II WordPress Standards Compliance mandates zero errors, Source: constitution.md §II)
- **PHPCS zero errors after all PHP edits** (Reason Included: §II, Source: constitution.md §II)
- **Amendment procedure must be followed for constitution changes** (Reason Included: §Governance requires: propose rationale, increment version, update affected templates, record sync impact report, Source: constitution.md §Governance)
- **Bootstrap class is single source of hook registration** (Reason Included: Boot Flow Rule; after cleanup, `load_hooks()` must still apply the `acrossai_core_abilities_load` filter even with no hooks to register, Source: constitution.md §Architecture)
- **`plugin_dir_path()` / `plugin_dir_url()` / `file_exists()` guard on vendor autoload must be preserved** (Reason Included: graceful degradation when vendor is absent; in scope because `includes/Main.php` is being edited, Source: constitution.md §V)

---

## Accepted Deviations

- **Unit tests not written for this feature** (Reason Included: §VII DoD requires unit tests for all new logic; this feature has no new logic — it is pure deletion + config trim; no testable logic is introduced, Status: Accepted-Deviation for cleanup-only tasks)
- **ESLint check not applicable** (Reason Included: §II mandates ESLint zero errors; all JS source is being deleted — no JS remains to lint, Status: Accepted-Deviation for this feature)

---

## Relevant Security Constraints

- **No new input surfaces introduced** (Reason Included: cleanup deletes code — no new attack surfaces are created; no nonce/capability/sanitization review needed for this change, Source: constitution.md §IV)
- **`vendor/` left intact** (Reason Included: Composer deps including `wpboilerplate/wpb-updater-checker-github` remain; their security posture is unchanged, Source: constitution.md §IV)

---

## Related Historical Lessons

- **Autoloader must be regenerated after namespace edits** — Silent failure mode: old `vendor/` still loads the deleted class paths from its cached autoload map until `composer dump-autoload` is run. This is a required post-edit verification step, not optional.
- **Constitution amendments require a sync impact report** — Templates that reference the changed sections must also be updated. Check `.specify/templates/plan-template.md` and `.specify/templates/tasks-template.md` for Boot Flow Rule / Admin Rule references after the constitution is patched.

---

## Conflict Warnings

- **HARD-RESOLVED**: Current constitution Boot Flow Rule ("All hooks trace to `define_admin_hooks()` / `define_public_hooks()`") directly conflicts with removing those methods. Resolution: spec FR-011 and SC-007 mandate updating the constitution as part of this feature — the amendment happens in CHANGE-10. Planning must not treat the current rule as blocking; the rule itself is being changed.
- **SOFT**: §VII DoD requires unit tests for all new logic. No new logic is introduced by this feature — only deletion. Accepted deviation recorded above.
- **SOFT**: `load_hooks()` must not become an empty method shell. After removing the two `define_*_hooks()` calls, it must still retain the `apply_filters( 'acrossai_core_abilities_load', true )` call to preserve the filter hook contract for third-party code.

---

## Retrieval Notes

- Index entries considered: 0 (no `docs/memory/INDEX.md` exists — fresh project)
- Active decisions sourced from: spec.md, planning doc, constitution.md (loaded in-session)
- Constitution loaded: yes (`.specify/memory/constitution.md` v1.0.0, loaded earlier in session)
- Feature memory file: absent (`specs/001-cleanup-scaffolding/memory.md` does not exist)
- Budget: well within limits (no durable memory files to load beyond constitution)
