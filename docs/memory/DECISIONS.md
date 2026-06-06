# Durable Decisions

---

### 2026-06-07 - Constitution HARD conflicts resolved within same feature before governance review

**Status**
Active

**Why this is durable**
When implementation requires removing a pattern that the constitution currently mandates (e.g., Boot
Flow Rule requiring `define_admin_hooks()`/`define_public_hooks()`), the correct resolution is to
amend the constitution as part of the same feature — not to suppress the violation or defer the
amendment to a follow-up PR. The amendment must be sequenced (and committed) before the
post-implementation architecture governance review runs.

**Decision**
Any feature that triggers a HARD constitution conflict must include the constitution amendment as an
explicit task (e.g., T017) in the same feature branch. The amendment task must be completed before
T019+ verification steps. This ensures the architecture governance review evaluates the
implementation against the updated constitution, not the stale one.

**Tradeoffs**
- Gained: No false-positive violations in governance review; constitution stays in sync with reality.
- Made harder: Features that clash with the constitution require a rationale and a version bump, not
  just a code change.
- Reconsider: If the amendment requires stakeholder sign-off beyond the feature author.

---

### 2026-06-07 - ai-policy.yml sync mandatory after path additions or removals

**Status**
Active

**Why this is durable**
`ai-policy.yml` drives five generated files (`.aiignore`, `.claudeignore`, `.claude/settings.json`,
`.vscode/settings.json`, `.github/copilot-instructions.md`) via `npm run sync:ai-policy`. Deleting
or adding directories without syncing leaves the AI tooling with stale path exclusions — LLMs may
read or write into paths that should be off-limits (or vice versa).

**Decision**
Whenever a directory is deleted from the plugin root (e.g., `build/`, `src/`), remove the
corresponding line from `ai-policy.yml` and immediately run `npm run sync:ai-policy`. Never edit
the five generated files directly.

**Tradeoffs**
- Gained: AI tooling access rules stay consistent across all editors and agents.
- Made harder: Path changes require one extra sync step.
- Reconsider: If the ai-policy format changes — revisit the sync script accordingly.
