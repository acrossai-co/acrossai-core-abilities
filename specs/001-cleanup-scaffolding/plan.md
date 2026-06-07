# Implementation Plan: Strip Boilerplate Scaffolding — `includes/`-only Plugin

**Branch**: `001-cleanup-boilerplate-scaffolding` | **Date**: 2026-06-07 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-cleanup-scaffolding/spec.md`
**Reference**: `docs/planning/001-cleanup-boilerplate-scaffolding.md`

---

## Summary

Remove all WPBoilerplate scaffolding artifacts (`admin/`, `public/`, `src/`, `build/` directories) that ship no real functionality, strip the corresponding hook wiring from `includes/Main.php`, align all developer tooling configs (Composer autoload, PHPStan, PHPCS, webpack, npm) to the `includes/`-only shape, and amend the project constitution so that admin/public layers are documented as optional-but-patterned rather than currently required.

No new runtime logic is introduced. Every change is either a deletion or a configuration trim. The plugin must activate cleanly after all changes.

---

## Technical Context

**Language/Version**: PHP 8.0+ (plugin requires PHP 8.0 per header)
**Primary Dependencies**: `automattic/jetpack-autoloader`, `wpboilerplate/wpb-updater-checker-github` (Composer runtime); `@agents-dev/cli`, `@wordpress/env` (npm dev-only)
**Storage**: N/A
**Testing**: PHPCS (`vendor/bin/phpcs`) + PHPStan (`vendor/bin/phpstan`) — no PHPUnit (no tests exist, accepted deviation)
**Target Platform**: WordPress 6.9+, PHP 8.0+
**Project Type**: WordPress plugin (library / headless ability provider)
**Performance Goals**: N/A (cleanup only)
**Constraints**: PHPStan level 8, PHPCS WPCS-strict, zero errors after all changes; plugin must activate with zero PHP errors
**Scale/Scope**: 10 discrete changes across 4 deletions + 6 file edits

---

## Constitution Check

*GATE: Assessed before Phase 0. Re-checked after Phase 3.*

- [x] **II. WP Standards**: PHPCS + PHPStan level 8 verified as post-step. ESLint — **accepted deviation** (all JS deleted; nothing remains to lint).
- [x] **III. User-Centric**: N/A — no admin UI introduced or modified.
- [x] **IV. Security**: No new input surfaces. `vendor/` untouched. Security posture unchanged.
- [x] **V. Extensibility**: `load_hooks()` retains the `acrossai_core_abilities_load` filter — third-party hook contract preserved. No core WP files modified.
- [x] **VI. DRY**: No new code introduced. `npm run validate-packages` can still be run post-trim.
- [x] **VII. DoD**: PHPStan + PHPCS pass required. Unit tests — **accepted deviation** (no new logic). DataForm/DataViews — N/A. ESLint — accepted deviation.
- [x] **Architecture**: Boot Flow Rule + Admin Rule are being amended by CHANGE-10 as part of this plan — treated as HARD-RESOLVED conflict, not a blocking violation.

**Complexity Tracking**: No unjustified violations. The constitution amendment (CHANGE-10) is a governance action explicitly within scope.

---

## Project Structure

### Documentation (this feature)

```text
specs/001-cleanup-scaffolding/
├── plan.md              ← this file
├── spec.md
├── memory-synthesis.md
└── checklists/
    └── requirements.md
```

### Source Layout — After Cleanup

```text
acrossai-core-abilities/
├── acrossai-core-abilities.php   ← untouched
├── uninstall.php                 ← untouched
├── index.php                     ← untouched
├── includes/
│   ├── index.php
│   ├── Main.php                  ← edited (CHANGE-5)
│   ├── Activator.php
│   ├── Deactivator.php
│   ├── I18n.php
│   └── Loader.php
├── vendor/                       ← untouched (regenerated post-step)
├── languages/
├── composer.json                 ← edited (CHANGE-6)
├── phpstan.neon.dist             ← edited (CHANGE-7)
├── phpcs.xml.dist                ← edited (CHANGE-8)
├── package.json                  ← edited (CHANGE-9)
├── .distignore                   ← edited (CHANGE-10a)
└── .specify/memory/constitution.md ← edited (CHANGE-10b)

Deleted:
  admin/        (CHANGE-1)
  public/       (CHANGE-2)
  src/          (CHANGE-3)
  build/        (CHANGE-4)
  webpack.config.js (CHANGE-9)
```

---

## Implementation Phases

### Phase 0 — Pre-Cleanup Baseline Verification

**Goal**: Confirm the current tree matches what the plan expects before making any destructive changes.

**Actions**:
1. Verify `admin/`, `public/`, `src/`, `build/` all exist.
2. Verify `includes/Main.php` contains `define_admin_hooks()` at line ~247 and `define_public_hooks()` at line ~263.
3. Verify `composer.json` has `Admin\` and `Public\` PSR-4 entries.
4. Verify `webpack.config.js` exists.
5. Verify `phpstan.neon.dist` lists `admin/` and `public/` as paths.
6. Record any line-number drift from the planning doc reference lines.

**Verification commands**:
```bash
ls admin public src build
grep -n "define_admin_hooks\|define_public_hooks" includes/Main.php
grep "Admin\|Public" composer.json
test -f webpack.config.js && echo EXISTS
```

---

### Phase 1 — Delete Dead Directories (CHANGE-1 through CHANGE-4)

**Dependency**: None. These are independent.

**CHANGE-1**: Delete `admin/` recursively.
- Files removed: `admin/index.php`, `admin/Main.php`, `admin/Partials/index.php`, `admin/Partials/Menu.php`

**CHANGE-2**: Delete `public/` recursively.
- Files removed: `public/index.php`, `public/Main.php`, `public/Partials/index.php`, `public/Partials/display.php`

**CHANGE-3**: Delete `src/` recursively.
- Files removed: `src/js/backend.js`, `src/js/frontend.js`, `src/scss/backend.scss`, `src/scss/frontend.scss`, `src/media/bookshelf.webp`, `src/media/purple-sunset.webp`

**CHANGE-4**: Delete `build/` recursively.
- All compiled CSS/JS output; consumers (`admin/Main.php`, `public/Main.php`) are deleted in CHANGE-1/2.

**Risk**: Low. Directories contain only dead scaffold code; no class is referenced from outside `includes/Main.php` hook registration methods.

---

### Phase 2 — Update Bootstrap Class (CHANGE-5)

**File**: `includes/Main.php`
**Dependency**: CHANGE-1 and CHANGE-2 must complete first (classes being deleted must be gone before lint/analysis runs on this file).

**Edit 1** — `load_hooks()` method body:

Replace:
```php
if ( apply_filters( 'acrossai_core_abilities_load', true ) ) {
    $this->define_admin_hooks();
    $this->define_public_hooks();
}
```

With:
```php
/**
 * Reserved for future hook registration.
 *
 * Use the `acrossai_core_abilities_load` filter to gate plugin loading.
 */
apply_filters( 'acrossai_core_abilities_load', true );
```

**Key invariant**: `apply_filters( 'acrossai_core_abilities_load', true )` MUST be retained — it is a documented third-party extension point. The return value is intentionally discarded (it was already discarded in the original — the `if` gate consumed it but any registered filter callbacks still fire).

**Edit 2** — Delete `define_admin_hooks()` method (lines ~240–261 including docblock).
**Edit 3** — Delete `define_public_hooks()` method (lines ~263–277 including docblock).

**Post-edit verification**:
```bash
grep -n "define_admin_hooks\|define_public_hooks\|Admin\\\\Main\|Public\\\\Main" includes/Main.php
# Must return zero results
```

---

### Phase 3 — Update Configuration Files (CHANGE-6 through CHANGE-10)

These changes are independent of each other and can be applied in any order within this phase.

#### CHANGE-6 — `composer.json`

**Edit 1** — Remove `Admin\` and `Public\` PSR-4 entries from `autoload.psr-4`:

```json
"autoload": {
    "psr-4": {
        "Acrossai_Core_Abilities\\Includes\\": "includes/"
    }
}
```

**Edit 2** — Remove `phpunit/phpunit` from `require-dev`:

```json
"require-dev": {
    "wp-coding-standards/wpcs": "*",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.2"
}
```

**Post-edit action** (required): `composer dump-autoload`
This regenerates `vendor/composer/autoload_psr4.php` to remove the deleted namespace maps. Without this step the plugin will throw a fatal error if any old autoload cache references `Admin\` or `Public\` classes.

---

#### CHANGE-7 — `phpstan.neon.dist`

Remove `admin/` and `public/` from the `paths:` block:

```neon
parameters:
    paths:
        - includes/
        - acrossai-core-abilities.php
```

No other lines change.

---

#### CHANGE-8 — `phpcs.xml.dist`

Remove the `<exclude-pattern>*/admin/*</exclude-pattern>` child from the `WordPress.WP.GlobalVariablesOverride` rule. The rule becomes a self-closing element:

```xml
<rule ref="WordPress.WP.GlobalVariablesOverride"/>
```

---

#### CHANGE-9 — `webpack.config.js` + `package.json`

**Edit 1**: Delete `webpack.config.js`.

**Edit 2** — `package.json` scripts: remove `build`, `start`, `lint:css`, `lint:js`, `packages-update`, `plugin-zip`.

Scripts to **keep**: `format`, `env`, `env:start`, `env:stop`, `env:restart`, `env:clean`, `env:reset`, `skillpack`, `skillpack:push`, `validate-packages`, `sync:ai-policy`, `mcp:sync`, `mcp:status`.

**Edit 3** — `package.json` devDependencies: remove `@wordpress/scripts`, `@wordpress/stylelint-config`, `copy-webpack-plugin`, `mini-css-extract-plugin`, `glob`, `path`, `webpack-remove-empty-scripts`.

**Edit 4** — `package.json` dependencies: remove `@wordpress/icons` (only consumed by deleted JS).

**Edit 5** — `package.json`: delete the `stylelint` config object entirely (no SCSS remains).

**Post-edit action** (required): `npm install` — refreshes `package-lock.json`.

---

#### CHANGE-10a — `.distignore`

Remove the `/src` line. No other line changes.

---

#### CHANGE-10b — `.specify/memory/constitution.md`

**Amendment procedure** (per §Governance):

1. **Rationale**: `admin/` and `public/` layers are deleted. Boot Flow Rule and Admin Rule must be reworded so they describe the *expected pattern when these layers are introduced* rather than mandating they currently exist.

2. **Version bump**: `1.0.0` → `1.1.0` (MINOR — new principle/guidance, not a clarification of an unchanged rule).

3. **Boot Flow Rule** replacement (§Architecture & UI Standards):

   > **Boot Flow Rule**: `includes/Main.php` is the single source of all hook registration.
   > When the plugin grows to need admin or public layers, hooks for those layers MUST be
   > registered via dedicated `define_admin_hooks()` / `define_public_hooks()` methods on the
   > bootstrap class — with no intermediate delegation. All feature classes use the singleton
   > `instance()` pattern. Resolve each singleton to a named variable before passing to the
   > loader — never inline.

4. **Admin Rule** replacement:

   > **Admin Rule**: When admin UI is introduced, any class that calls `add_menu_page()`,
   > enqueues assets, or renders HTML MUST live in a dedicated `admin/Partials/` namespace.
   > Module classes under `includes/` are context-neutral and MUST NOT call admin-only APIs.

5. **Version footer** update: `1.0.0` → `1.1.0`, update `Last Amended` date to `2026-06-07`.

6. **SYNC IMPACT REPORT** HTML comment at the top: record `1.0.0 → 1.1.0`, date, what changed (Boot Flow Rule and Admin Rule reworded to forward guidance), templates reviewed.

7. **Template review** — scan `.specify/templates/plan-template.md` and `.specify/templates/tasks-template.md` for any direct references to `define_admin_hooks`, `define_public_hooks`, or the Admin Rule text. Update if found.

---

### Phase 4 — Regenerate Derived Artifacts

These steps are sequential and depend on Phase 3 completing.

1. `composer dump-autoload` — regenerate Composer PSR-4 map (also required after CHANGE-6).
2. `npm install` — regenerate `package-lock.json` after CHANGE-9.

---

### Phase 5 — Verification

Run in this order:

```bash
# 1. Confirm deletions
ls admin public src build 2>&1
test -f webpack.config.js && echo PRESENT || echo GONE

# 2. Confirm bootstrap clean
grep -n "define_admin_hooks\|define_public_hooks\|Admin\\\\Main\|Public\\\\Main" includes/Main.php

# 3. Static analysis
vendor/bin/phpstan analyse --memory-limit=512M
vendor/bin/phpcs

# 4. Autoloader smoke
composer dump-autoload

# 5. Confirm third-party filter hook preserved (SEC-002)
grep -n "apply_filters.*acrossai_core_abilities_load" includes/Main.php
# Must return exactly 1 result

# 6. Plugin smoke (requires wp-env or local WP)
# wp plugin activate acrossai-core-abilities
# Check PHP error log for notices/warnings/fatals
# wp plugin deactivate acrossai-core-abilities
```

---

## Risk Register

| Risk | Likelihood | Mitigation |
|------|-----------|------------|
| Autoloader cache still maps deleted namespaces | Medium | `composer dump-autoload` is a required Phase 4 step |
| `load_hooks()` `apply_filters` call accidentally removed | Low | Explicit invariant documented; verified by grep in Phase 5 |
| Template files reference old Boot Flow Rule text | Low | CHANGE-10b procedure includes explicit template scan |
| `phpstan` finds new errors in `includes/Main.php` after method deletion | Low | No type changes introduced; `load_hooks()` return type unchanged |
| `npm install` fails due to outdated lock file | Low | `package-lock.json` regenerated by `npm install` post-trim |

---

## Ordering Summary

```
Phase 0  →  Baseline verification (read-only)
Phase 1  →  CHANGE-1, CHANGE-2, CHANGE-3, CHANGE-4 (delete dirs — parallel safe)
Phase 2  →  CHANGE-5 (bootstrap edit — after Phase 1)
Phase 3  →  CHANGE-6, CHANGE-7, CHANGE-8, CHANGE-9, CHANGE-10a, CHANGE-10b (config edits — after Phase 1; parallel safe within phase)
Phase 4  →  composer dump-autoload, npm install (after Phase 3)
Phase 5  →  Verification (after Phase 4)
```

Total: 10 changes. 4 deletions + 6 edits. 0 new files created.
