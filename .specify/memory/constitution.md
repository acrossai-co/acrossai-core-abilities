<!--
  SYNC IMPACT REPORT
  ===================
  Version change: UNVERSIONED (template) → 1.0.0
  Date: 2026-06-07

  Added sections (initial ratification — all sections are new):
  - I. Plugin Identity & Purpose (inferred from project context: plugin header, README)
  - II. WordPress Standards Compliance
  - III. User-Centric Design (NON-NEGOTIABLE)
  - IV. Security First (NON-NEGOTIABLE)
  - V. Extensibility Without Core Modification
  - VI. Reusability & DRY Principle
  - VII. Definition of Done
  - Architecture & UI Standards
  - Governance

  Removed sections: none

  Templates updated:
  - .specify/templates/plan-template.md ✅ (Constitution Check gates filled in)
  - .specify/templates/tasks-template.md ✅ (Polish phase updated with DoD checklist items)
  - .specify/templates/spec-template.md ✅ (reviewed — no changes required)

  Deferred TODOs: none
-->

# Acrossai Core Abilities Constitution

## I. Plugin Identity & Purpose

**Acrossai Core Abilities** is a WordPress plugin built on the WPBoilerplate framework that exposes
the WordPress Abilities API. It MUST:

- Register abilities and categories via `wp_register_ability()` / `wp_register_ability_category()`.
- Serve all ability data through versioned REST endpoints under `/wp-json/wp-abilities/v1/`.
- Operate as a self-contained plugin; no external service dependency is required for core functionality.
- Comply with all WordPress Plugin Directory guidelines for publication and maintenance.

## II. WordPress Standards Compliance

All PHP code MUST conform to WordPress Coding Standards (WPCS strict profile).
Static analysis MUST pass PHPStan at level 8 with zero errors.
JavaScript MUST pass ESLint with zero errors or warnings.
All output MUST be escaped using the most specific available WordPress escaping function.
All input MUST be sanitized at system entry points.
No deprecated WordPress functions are permitted.
The plugin MUST pass the WordPress Plugin Check tool with zero errors and zero warnings.

Plugin Check compliance is evaluated against the installable production plugin surface.
Development-only artifacts such as `.github`, `docs`, `specs`, `tests`, hidden dotfiles, and local
tooling configs MUST be excluded from Plugin Check CI.

Dynamic SQL identifiers MUST be escaped with `$wpdb->prepare()` and `%i`. Direct interpolation of
table names into SQL is prohibited.

WordPress forbidden functions (`eval()`, `extract()`, shell/process functions) MUST be replaced or
removed — not suppressed. Plugin Check suppressions MUST be local and exact.

The plugin MUST be compatible with WordPress 6.9+ and PHP 7.4+. The plugin MUST be
multisite-compatible unless explicitly scoped to single-site with documented justification.

## III. User-Centric Design (NON-NEGOTIABLE)

All admin interfaces MUST prioritize site administrator experience above implementation convenience.
All form handling and data input MUST use `DataForm` (exported from `@wordpress/dataviews`).
All data display and listing MUST use `@wordpress/dataviews`.
DataForm MUST handle: field-level validation, inline error display, and submission state feedback.
DataViews MUST provide: searchable lists, column sorting, pagination, and contextual filtering.
No custom form or table rendering that duplicates DataForm/DataViews functionality is permitted.

## IV. Security First (NON-NEGOTIABLE)

- All input MUST be sanitized at system boundaries using the most specific WordPress sanitization
  function (`sanitize_text_field()`, `absint()`, `wp_kses_post()`).
- All output MUST be escaped at the point of rendering (`esc_html()`, `esc_attr()`, `esc_url()`).
- All forms and AJAX endpoints MUST verify a nonce before processing any data.
- All admin actions MUST enforce a capability check (`manage_options` minimum).
- All database queries MUST use `$wpdb->prepare()` — raw interpolated queries are forbidden.
- File upload operations MUST validate MIME type, extension, and file size before processing.
- No deprecated WordPress security functions are permitted.

## V. Extensibility Without Core Modification

New features and third-party integrations MUST be implemented via WordPress action/filter hooks,
extension points, or new self-contained modules — never by modifying existing core plugin files.
All integrations MUST be optional: the plugin MUST function correctly and degrade gracefully when
an integrated plugin or service is absent.
Auto-discovery of external services MUST be implemented as a background process and MUST NOT block
admin page rendering.

## VI. Reusability & DRY Principle

All common logic MUST be extracted to shared utilities before it is used in a second location.
Reusable components MUST be built for: form builders, view generators, input validation, output
sanitization, API response formatters, and permission checks.
If equivalent functionality already exists anywhere in the codebase, it MUST be reused — never
duplicated.
Use `@wordpress/*` packages first (Tier 1), then npm packages (Tier 2). Never introduce a
dependency that duplicates React, ReactDOM, or other packages already bundled by WordPress.
Run `npm run validate-packages` before every commit.

## VII. Definition of Done

A feature is ONLY considered complete when ALL of the following pass:

- [ ] PHPCS: zero errors and zero warnings
- [ ] PHPStan level 8: zero errors
- [ ] ESLint: zero errors
- [ ] Security review: sanitization, escaping, nonces, and capabilities verified at every boundary
- [ ] Unit tests written and passing for all new logic
- [ ] All data input uses `DataForm` from `@wordpress/dataviews`
- [ ] All data display uses `@wordpress/dataviews`
- [ ] No code duplication or DRY violations
- [ ] All functions, hooks, and classes are prefixed consistently
- [ ] `npm run validate-packages` passes

## Architecture & UI Standards

**Boot Flow Rule**: One central file is the single source of all hook registration. All hooks trace
to `define_admin_hooks()` / `define_public_hooks()` with no intermediate delegation. All feature
classes use the singleton `instance()` pattern. Resolve each singleton to a named variable before
passing to the loader — never inline.

**REST Controller Pattern**: Split into per-domain sub-controllers when a controller exceeds ~400
lines or spans more than one user story. Sub-controllers live in a `Rest/` subdirectory inside the
module. The top-level controller is a thin orchestrator owning only: namespace constant,
`register_routes()` delegation, and shared `check_permission()`.

**Admin Rule**: Any class that calls `add_menu_page()`, enqueues assets, or renders HTML MUST live
in the admin partials layer. Module classes are context-neutral.

**Database**: Direct SQL is permitted only with `$wpdb->prepare()`. Prefer WordPress options/meta
APIs for simple key-value storage. Custom tables only when the data model genuinely cannot fit
existing APIs, with documented justification.

**Integration Resilience**: All calls to optional integrations MUST be wrapped in availability
checks and MUST NOT throw fatal errors or produce broken UIs when absent.

## Governance

This constitution supersedes all other development practices.

**Amendment Procedure**:
1. Propose the amendment with clear rationale
2. Increment version (MAJOR: breaking change, MINOR: new principle, PATCH: clarification)
3. Update this file and propagate to all affected templates
4. Record a sync impact report
5. Commit with message: `docs: amend constitution to vX.Y.Z (<summary>)`

**Version**: 1.0.0 | **Ratified**: 2026-06-07 | **Last Amended**: 2026-06-07
