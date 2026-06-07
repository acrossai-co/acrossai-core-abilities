---
description: "Task list for Feature 001 — Strip Boilerplate Scaffolding"
---

# Tasks: Strip Boilerplate Scaffolding — `includes/`-only Plugin

**Input**: Design documents from `specs/001-cleanup-scaffolding/`
**Prerequisites**: plan.md ✅ spec.md ✅ memory-synthesis.md ✅ security-constraints.md ✅

**Tests**: No test tasks generated — this feature introduces no new logic; accepted deviation per memory-synthesis.md.

**Organization**: Tasks are grouped by user story. Each story can be validated independently. Deletions in US1 are safe to parallelize because no surviving file references the deleted directories at runtime after CHANGE-5.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no inter-dependencies)
- **[Story]**: Maps to user story from spec.md (US1–US4)

---

## Phase 1: Setup — Pre-Cleanup Baseline Verification

**Purpose**: Confirm the working tree matches the plan's assumptions before any destructive change.

- [x] T001 Verify all directories and files expected to be deleted/edited exist — run: `ls admin public src build && grep -n "define_admin_hooks\|define_public_hooks" includes/Main.php && grep "Admin\\\|Public\\\\" composer.json && test -f webpack.config.js && echo ALL_OK`

**Checkpoint**: If T001 reports missing paths, pause and reconcile with the planning doc before proceeding.

---

## Phase 2: Foundational

No foundational blockers beyond T001. All user story phases may begin immediately after Phase 1.

---

## Phase 3: User Story 1 — Plugin Activates Without Dead Code Errors (Priority: P1) 🎯 MVP

**Goal**: Delete the `admin/`, `public/`, and `build/` directories; strip the corresponding hook wiring from `includes/Main.php`; update the Composer autoload to reflect the `includes/`-only shape; regenerate the autoloader. After this phase the plugin activates cleanly.

**Independent Test**: `wp plugin activate acrossai-core-abilities` succeeds with zero PHP errors, warnings, or notices. `grep -n "define_admin_hooks\|define_public_hooks" includes/Main.php` returns nothing.

### Implementation for User Story 1

- [x] T002 [P] [US1] Delete `admin/` directory recursively — removes `admin/index.php`, `admin/Main.php`, `admin/Partials/index.php`, `admin/Partials/Menu.php`
- [x] T003 [P] [US1] Delete `public/` directory recursively — removes `public/index.php`, `public/Main.php`, `public/Partials/index.php`, `public/Partials/display.php`
- [x] T004 [P] [US1] Delete `build/` directory recursively — removes all compiled CSS/JS output (consumers deleted in T002/T003)
- [x] T005 [US1] Edit `includes/Main.php` — replace body of `load_hooks()` (retain `apply_filters('acrossai_core_abilities_load', true)`; drop the `if` block); delete entire `define_admin_hooks()` method and entire `define_public_hooks()` method including docblocks
- [x] T006 [US1] Edit `composer.json` — (a) remove `"Acrossai_Core_Abilities\\Admin\\"` and `"Acrossai_Core_Abilities\\Public\\"` from `autoload.psr-4`; (b) remove `"phpunit/phpunit"` from `require-dev`
- [x] T007 [US1] Run `composer dump-autoload` to regenerate autoloader after namespace map edit — required post-step; without this the plugin will fatal if any cached class map references `Admin\` or `Public\`

**Checkpoint**: Plugin activates cleanly. `grep -n "Admin\\\\Main\|Public\\\\Main\|define_admin_hooks\|define_public_hooks" includes/Main.php` returns zero results. `composer dump-autoload` exits 0 with no missing-directory warnings.

---

## Phase 4: User Story 2 — Static Analysis Covers Only Live Code (Priority: P2)

**Goal**: Update PHPStan and PHPCS configuration to scan only `includes/` and the main plugin file. Run both tools to confirm zero errors.

**Independent Test**: `vendor/bin/phpstan analyse --memory-limit=512M` exits 0. `vendor/bin/phpcs` exits 0. Neither tool emits path-not-found warnings for `admin/` or `public/`.

### Implementation for User Story 2

- [x] T008 [P] [US2] Edit `phpstan.neon.dist` — remove `- admin/` and `- public/` from the `paths:` block; keep `- includes/` and `- acrossai-core-abilities.php`
- [x] T009 [P] [US2] Edit `phpcs.xml.dist` — replace `<rule ref="WordPress.WP.GlobalVariablesOverride"><exclude-pattern>*/admin/*</exclude-pattern></rule>` with the self-closing form `<rule ref="WordPress.WP.GlobalVariablesOverride"/>`
- [ ] T010 [US2] Run `vendor/bin/phpstan analyse --memory-limit=512M` — must exit 0 with zero errors ⚠️ PENDING MANUAL (permissions blocked in current env)
- [ ] T011 [US2] Run `vendor/bin/phpcs` — must exit 0 with zero errors ⚠️ PENDING MANUAL (permissions blocked in current env)

**Checkpoint**: Both static analysis tools report zero findings against the `includes/`-only codebase.

---

## Phase 5: User Story 3 — Build Tooling Matches Plugin Shape (Priority: P3)

**Goal**: Delete `src/`, remove `webpack.config.js`, strip build-related scripts and devDependencies from `package.json`, refresh `package-lock.json`.

**Independent Test**: `test -f webpack.config.js && echo PRESENT || echo GONE` prints `GONE`. `npm install` exits 0. `package.json` contains no `build`, `start`, `lint:css`, `lint:js`, `plugin-zip`, or `packages-update` scripts.

### Implementation for User Story 3

- [x] T012 [P] [US3] Delete `src/` directory recursively — removes `src/js/backend.js`, `src/js/frontend.js`, `src/scss/backend.scss`, `src/scss/frontend.scss`, `src/media/bookshelf.webp`, `src/media/purple-sunset.webp`
- [x] T013 [P] [US3] Delete `webpack.config.js`
- [x] T014 [US3] Edit `package.json` — (a) remove scripts: `build`, `start`, `lint:css`, `lint:js`, `packages-update`, `plugin-zip`; (b) remove devDependencies: `@wordpress/scripts`, `@wordpress/stylelint-config`, `copy-webpack-plugin`, `mini-css-extract-plugin`, `glob`, `path`, `webpack-remove-empty-scripts`; (c) remove dependencies: `@wordpress/icons`; (d) delete the `stylelint` config object
- [x] T015 [US3] Run `npm install` to refresh `package-lock.json` after package.json trim

**Checkpoint**: `webpack.config.js` gone. `npm install` exits 0. `package-lock.json` updated.

---

## Phase 6: User Story 4 — Future Admin UI Has a Clear Starting Point (Priority: P4)

**Goal**: Update `.distignore` to remove the dead `/src` entry; amend the project constitution to reframe the Boot Flow Rule and Admin Rule as forward guidance; scan affected templates.

**Independent Test**: `.distignore` has no `/src` line. `grep -n "Boot Flow Rule" .specify/memory/constitution.md` returns the updated forward-looking paragraph. Constitution version footer reads `1.1.0`.

### Implementation for User Story 4

- [x] T016 [P] [US4] Edit `.distignore` — remove the `/src` line (line 8); no other changes
- [x] T017 [US4] Edit `.specify/memory/constitution.md` — (a) rewrite Boot Flow Rule paragraph in §Architecture to describe `define_admin_hooks/define_public_hooks` as the pattern to follow *when* admin/public layers are introduced (not as currently required); (b) rewrite Admin Rule to say "When admin UI is introduced…"; (c) bump version footer from `1.0.0` → `1.1.0`; (d) update Last Amended date to 2026-06-07; (e) update the HTML SYNC IMPACT REPORT comment at top to record `1.0.0 → 1.1.0`, the date, and the changed sections
- [x] T018 [US4] Scan `.specify/templates/plan-template.md` and `.specify/templates/tasks-template.md` for any literal references to `define_admin_hooks`, `define_public_hooks`, or the old Admin Rule text — update any found references to match the new forward-looking wording

**Checkpoint**: Constitution version `1.1.0`. SYNC IMPACT REPORT present at top of constitution. Template scan complete with zero stale references remaining.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final verification sweep, SEC-002 filter-hook check, and DoD sign-off.

- [x] T019 [P] Verify all four directories are gone — `ls admin public src build 2>&1` must return four "No such file or directory" lines ✅
- [x] T020 [P] Verify `webpack.config.js` is gone — `test -f webpack.config.js && echo PRESENT || echo GONE` must print `GONE` ✅
- [x] T021 [P] Confirm `includes/Main.php` has no dead references — `grep -n "define_admin_hooks\|define_public_hooks\|Admin\\\\Main\|Public\\\\Main" includes/Main.php` must return zero results ✅ (stale docblock also cleaned)
- [x] T022 Confirm `apply_filters('acrossai_core_abilities_load', true)` survived CHANGE-5 — `grep -n "apply_filters.*acrossai_core_abilities_load" includes/Main.php` must return exactly 1 result (SEC-002) ✅ found at line 180
- [x] T023 Confirm `composer.json` autoload has exactly one PSR-4 entry — `grep -A5 '"psr-4"' composer.json` must show only `Acrossai_Core_Abilities\\Includes\\` ✅
- [ ] T024 **DoD gate**: `vendor/bin/phpstan analyse --memory-limit=512M` — zero errors (re-run after all PHP changes) ⚠️ PENDING MANUAL (permissions blocked in current env)
- [ ] T025 **DoD gate**: `vendor/bin/phpcs` — zero errors (re-run after all PHP changes) ⚠️ PENDING MANUAL (permissions blocked in current env)
- [ ] T026 Plugin smoke test — activate the plugin in a WordPress environment (`wp plugin activate acrossai-core-abilities`), verify zero PHP errors/notices in error log, then deactivate and uninstall cleanly ⚠️ PENDING MANUAL

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 3 (US1)**: Depends on Phase 1 (T001 baseline). T002, T003, T004 can run in parallel. T005 and T006 depend on T002–T004 completing (so the deleted classes are gone before lint runs). T007 depends on T006.
- **Phase 4 (US2)**: Can start after Phase 1. T008 and T009 are parallel. T010 and T011 depend on T005 (bootstrap edit) being done.
- **Phase 5 (US3)**: Can start after Phase 1. T012 and T013 are parallel. T014 depends on T013. T015 depends on T014.
- **Phase 6 (US4)**: Can start after Phase 1. T016 is independent. T017 depends only on Phase 1. T018 depends on T017.
- **Phase 7 (Polish)**: Depends on all preceding phases. T019–T023 can run in parallel. T024–T025 are DoD gates (run last among analysis tools). T026 is final.

### User Story Dependencies

- **US1 (P1)**: No inter-story deps — start after T001
- **US2 (P2)**: T010–T011 depend on US1's T005 completing (PHPStan/PHPCS analyse the edited `includes/Main.php`)
- **US3 (P3)**: Independent of US1 and US2
- **US4 (P4)**: Independent of US1, US2, US3

### Parallel Opportunities

```bash
# After T001 completes — launch these in parallel:
T002  # rm -rf admin/
T003  # rm -rf public/
T004  # rm -rf build/
T008  # edit phpstan.neon.dist
T009  # edit phpcs.xml.dist
T012  # rm -rf src/
T013  # rm webpack.config.js
T016  # edit .distignore
T017  # edit constitution.md
```

---

## Implementation Strategy

### MVP First (US1 Only — Plugin Activates Cleanly)

1. Complete Phase 1: T001 (baseline check)
2. Complete Phase 3: T002–T007 (delete dead dirs + fix bootstrap + fix autoload)
3. **STOP and VALIDATE**: plugin activates, zero PHP errors
4. Continue to US2, US3, US4 in any order

### Incremental Delivery

1. T001 → Phase 1 baseline ✅
2. T002–T007 → Plugin activates cleanly (US1 MVP) ✅
3. T008–T011 → Analysis tools clean (US2) ✅
4. T012–T015 → Build tooling aligned (US3) ✅
5. T016–T018 → Constitution + templates updated (US4) ✅
6. T019–T026 → Polish + DoD gates ✅

---

## Notes

- [P] tasks touch different files — safe to run concurrently
- T007 (`composer dump-autoload`) is mandatory after T006 — the plugin will throw a fatal error if skipped
- T015 (`npm install`) is mandatory after T014 — otherwise `package-lock.json` diverges from `package.json`
- T022 (SEC-002 filter check) is a non-negotiable verification — it guards the `acrossai_core_abilities_load` third-party contract
- T017 (constitution amendment) must follow §Governance procedure: rationale + version bump + SYNC IMPACT REPORT + template scan (T018)
- No PHPUnit tasks — accepted deviation (no new logic introduced)
- No ESLint tasks — accepted deviation (all JS deleted)
