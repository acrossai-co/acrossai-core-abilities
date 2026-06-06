# Feature Specification: Strip Boilerplate Scaffolding — `includes/`-only Plugin

**Feature Branch**: `001-cleanup-boilerplate-scaffolding`
**Created**: 2026-06-07
**Status**: Draft
**Input**: User description: "Clean up all the js and scss, admin, public files, and from the webpack config all the unwanted js and css files that are not used here — this plugin will only have the includes folder."

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Plugin Activates Without Dead Code Errors (Priority: P1)

A plugin developer activates `acrossai-core-abilities` on a WordPress site and it loads cleanly: no fatal errors from missing admin/public classes, no missing asset files referenced from deleted build output, no orphaned hook registrations.

**Why this priority**: Without this, the plugin is broken after cleanup. Everything else depends on clean activation.

**Independent Test**: Activate the plugin via WP-CLI or the admin plugins screen. WordPress loads without PHP notices, warnings, or fatals. Deactivation and uninstall also complete cleanly.

**Acceptance Scenarios**:

1. **Given** the plugin is installed on a WordPress site, **When** a site administrator activates it, **Then** no errors appear in the PHP error log and the admin dashboard loads normally.
2. **Given** the plugin is active, **When** a page is loaded on the front end or back end, **Then** no warnings about missing files, undefined classes, or broken hook callbacks appear.
3. **Given** the plugin is active, **When** it is deactivated and uninstalled, **Then** cleanup runs without errors.

---

### User Story 2 — Static Analysis Covers Only Live Code (Priority: P2)

A developer runs code quality tools (linting, static analysis) and the tools scan only the code paths that actually run — the `includes/` layer. Dead scaffolding folders that no longer exist do not appear as false-positive errors or missing-path warnings.

**Why this priority**: Broken tool configurations erode developer trust and slow down CI. Aligning tool config with the actual file tree is a hygiene requirement.

**Independent Test**: Run the project's code quality commands. They complete without path-not-found warnings or errors caused by references to deleted directories.

**Acceptance Scenarios**:

1. **Given** the project's static analysis configuration lists analysis paths, **When** those tools are run, **Then** only `includes/` and the main plugin file are scanned — no warnings about missing `admin/` or `public/` directories.
2. **Given** the project's coding standards configuration references directories, **When** standards checks are run, **Then** no rules reference folders that no longer exist.
3. **Given** a developer runs dependency management commands, **Then** no warnings appear about autoload namespace mappings pointing to missing directories.

---

### User Story 3 — Build Tooling Matches Plugin Shape (Priority: P3)

A developer looking at the project's build configuration sees only entries that are relevant to the current plugin. There are no references to JS/SCSS source files that were deleted, and no build scripts that would fail because the source no longer exists.

**Why this priority**: Stale build config is a maintenance hazard — it confuses future contributors and breaks `npm install` / build pipelines unnecessarily.

**Independent Test**: Inspect `package.json` — no build-related scripts remain. Confirm the webpack config file is gone. Run `npm install` — it completes without errors.

**Acceptance Scenarios**:

1. **Given** the project has a `package.json`, **When** a developer inspects it, **Then** no build, compile, or CSS-lint scripts reference source files that no longer exist.
2. **Given** the build configuration file previously referenced JS/SCSS entry points, **When** the cleanup is complete, **Then** that configuration file no longer exists.
3. **Given** the distribution ignore list excluded a `src/` directory, **When** that directory is deleted, **Then** the ignore list no longer references it.

---

### User Story 4 — Future Admin UI Has a Clear Starting Point (Priority: P4)

A developer who later needs to add real admin pages to the plugin finds a clean `includes/Main.php` with no stub hook methods, and a constitution that documents the correct pattern for adding admin/public layers when needed.

**Why this priority**: Removing dead code should not destroy institutional knowledge. The constitutional rules must be updated to describe admin/public layers as optional-but-patterned, not removed entirely.

**Independent Test**: Read `includes/Main.php` — no `define_admin_hooks()` or `define_public_hooks()` methods. Read the project constitution — the Boot Flow Rule and Admin Rule describe the pattern a developer must follow *when* they add admin UI, framed as future guidance rather than current requirement.

**Acceptance Scenarios**:

1. **Given** `includes/Main.php` previously contained methods that wired admin and public hooks, **When** cleanup is complete, **Then** those methods and their callers are gone — the file contains only the bootstrap logic needed for the `includes/`-only shape.
2. **Given** the project constitution previously mandated that hooks trace to `define_admin_hooks()` / `define_public_hooks()`, **When** cleanup is complete, **Then** those rules are reworded as forward guidance describing the pattern to follow *when* admin/public layers are introduced.

---

### Edge Cases

- What happens if `vendor/` is absent when the plugin activates? The Composer autoload check in `includes/Main.php` (`file_exists( $plugin_path . 'vendor/autoload_packages.php' )`) already guards against this with a graceful skip — this must remain unchanged.
- What if a future developer re-adds a `build/` directory? They should re-add the corresponding enqueue logic in a proper admin/public class — the cleanup must not leave any ghost enqueue calls.
- What if the deletion of namespace entries in `composer.json` breaks the autoloader while `vendor/` still contains the old generated files? Running `composer dump-autoload` after editing regenerates the autoloader — this is a required post-edit step.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The plugin MUST activate, run, deactivate, and uninstall without PHP errors when the `admin/`, `public/`, `src/`, and `build/` directories are absent.
- **FR-002**: The bootstrap class (`includes/Main.php`) MUST NOT contain hook registration methods that reference classes in deleted directories.
- **FR-003**: The dependency manager configuration MUST map only the namespace(s) that correspond to directories that exist (`includes/`); dead namespace mappings MUST be removed.
- **FR-004**: The dependency manager configuration MUST NOT declare test framework dependencies when no tests exist in the project.
- **FR-005**: Static analysis configuration MUST list only existing directories as analysis paths.
- **FR-006**: Coding standards configuration MUST NOT contain directory-specific rule overrides for directories that no longer exist.
- **FR-007**: The build system entry-point configuration file MUST be removed when no source files remain to compile.
- **FR-008**: The package configuration MUST NOT declare scripts that invoke a removed build system or compile source files that no longer exist.
- **FR-009**: The package configuration MUST NOT declare build-system dependencies that are no longer used.
- **FR-010**: The distribution ignore list MUST NOT reference directories that no longer exist.
- **FR-011**: The project constitution MUST be updated so that its architectural rules accurately describe the current plugin shape and provide forward guidance for when admin/public layers are re-introduced.
- **FR-012**: All AI/agent tooling files (`.agents/`, `.claude/`, `.specify/`, `scripts/`, `AGENTS.md`, `CLAUDE.md`, `ai-policy.yml`, `.mcp.json`) MUST remain untouched — they are development infrastructure, not runtime artifacts.

### Key Entities

- **`includes/` layer**: The sole runtime PHP namespace after cleanup. Contains `Main.php`, `Activator.php`, `Deactivator.php`, `I18n.php`, `Loader.php`, `index.php`.
- **Bootstrap class (`includes/Main.php`)**: Wires plugin constants, the Composer autoloader, and the i18n hook. After cleanup it MUST NOT wire admin or public hooks.
- **Project constitution (`.specify/memory/constitution.md`)**: Governs all architectural decisions. Must be updated to reflect the new plugin shape and bump version.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Plugin activates on a WordPress site with zero PHP errors, warnings, or notices in the error log.
- **SC-002**: Running the project's static analysis tool produces zero errors and zero path-not-found warnings.
- **SC-003**: Running the project's coding standards checker produces zero errors against the surviving codebase.
- **SC-004**: The package manager install command completes successfully after the package configuration is trimmed.
- **SC-005**: The project's dependency autoloader regenerates successfully after the namespace configuration is updated, with zero warnings about missing directories.
- **SC-006**: A developer inspecting `includes/Main.php` finds zero methods whose sole purpose was to instantiate and wire classes from the deleted `admin/` or `public/` directories.
- **SC-007**: The project constitution version is incremented and its Boot Flow Rule and Admin Rule accurately describe the `includes/`-only state while providing forward guidance for future admin/public layers.

---

## Assumptions

- `vendor/` and `node_modules/` are present in the working tree during cleanup and are regenerated by the developer after `composer.json` and `package.json` are edited — they are not deleted as part of this feature.
- The `build/` directory was committed to the repository; its deletion is safe because all consumers of its output (`admin/Main.php`, `public/Main.php`) are also being deleted.
- No other plugin, theme, or external code depends on the `Admin\` or `Public\` namespaces of this plugin.
- The `acrossai-core-abilities.php` main file requires no changes — it already routes only through `includes/Main.php` and does not reference `admin/` or `public/` directly.
- AI tooling files are development infrastructure and their correctness is maintained by spec-kit workflows, not by this feature.
- The constitution version bump is MINOR (new section, no breaking change): `1.0.0` → `1.1.0`.
