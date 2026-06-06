# Planning: Strip Boilerplate Scaffolding — `includes/`-only Plugin (Feature 001)

Remove every WPBoilerplate scaffolding artifact that ships JS, SCSS, admin pages, or
public-side hooks. After this feature lands, `acrossai-core-abilities` is a pure-PHP
plugin whose only runtime code lives under `includes/`. The bootstrap class
(`includes/Main.php`) no longer wires `admin/` or `public/` hooks. The Composer
autoload, PHPStan paths, PHPCS rules, and webpack/npm tooling are aligned with the
new shape.

---

## Spec-kit Workflow

```markdown
# 1. Branch
/speckit.git.feature "001-cleanup-boilerplate-scaffolding"

# 2. Specify
/speckit.specify "Strip all WPBoilerplate scaffolding so the plugin contains only the
includes/ runtime layer plus the main plugin file and dev/AI tooling.
Ten changes total:
(1) DELETE admin/ folder,
(2) DELETE public/ folder,
(3) DELETE src/ folder,
(4) DELETE build/ folder,
(5) includes/Main.php — remove define_admin_hooks() and define_public_hooks() and their callers,
(6) composer.json — remove Admin\\ and Public\\ PSR-4 entries; remove phpunit/phpunit dev dep,
(7) phpstan.neon.dist — drop admin/ and public/ paths,
(8) phpcs.xml.dist — drop the admin/ exclude in WordPress.WP.GlobalVariablesOverride rule,
(9) DELETE webpack.config.js; package.json — drop build/start/lint:css/lint:js/plugin-zip/packages-update scripts and webpack-related devDependencies,
(10) .specify/memory/constitution.md — update Boot Flow Rule and Admin Rule so admin/public layers are documented as optional, and bump constitution version."
```

---

## Background — what is already true; do NOT redo it

| # | Fact | How to verify |
|---|------|---------------|
| B-1 | The plugin loads only `includes/Main.php` from the main file; `admin/` and `public/` classes are reached **only** via the loader hooks in `includes/Main.php` lines 249, 251, 253, 272, 274, 276 | read `acrossai-core-abilities.php` lines 53–96 |
| B-2 | `admin/Main.php`, `admin/Partials/Menu.php`, `public/Main.php`, `public/Partials/display.php` are the only PHP files under `admin/`/`public/`; no other code in the plugin references their classes | `grep -rn "Acrossai_Core_Abilities\\\\Admin\\|Acrossai_Core_Abilities\\\\Public" .` |
| B-3 | `admin/Main.php:78–79` and `public/Main.php` `include` `build/css/*.asset.php` / `build/js/*.asset.php` at construct time — those files exist only because `npm run build` was run | `ls build/css build/js` |
| B-4 | `src/` contains exactly `js/backend.js`, `js/frontend.js`, `scss/backend.scss`, `scss/frontend.scss`, `media/*.webp` — no production code depends on them | `ls src/js src/scss src/media` |
| B-5 | `webpack.config.js` declares entries for `js/frontend`, `js/backend`, `css/frontend`, `css/backend` and dynamic globs for `src/scss/blocks/core/*.scss` and `src/blocks/**/block.json`; the dynamic globs already match nothing | read `webpack.config.js` lines 24–56, 65–77 |
| B-6 | `composer.json` PSR-4 maps three namespaces: `Includes\\` → `includes/`, `Admin\\` → `admin/`, `Public\\` → `public/` | read `composer.json` lines 25–31 |
| B-7 | `composer.json` declares `phpunit/phpunit ^13.2@dev` as a dev dep, but the plugin contains **no `tests/` directory, no `phpunit.xml`, and no `*Test.php` files** | `find . -path ./node_modules -prune -o -path ./vendor -prune -o -iname "*Test.php" -print` |
| B-8 | `phpstan.neon.dist` `paths:` block lists `includes/`, `admin/`, `public/`, and the main file | read `phpstan.neon.dist` lines 3–7 |
| B-9 | `phpcs.xml.dist` line 45 carries `<exclude-pattern>*/admin/*</exclude-pattern>` inside the `WordPress.WP.GlobalVariablesOverride` rule | read `phpcs.xml.dist` lines 44–46 |
| B-10 | `.distignore` already excludes `/src`, `node_modules`, `composer.json`, `package.json`, `webpack.config.js` from distribution; after this cleanup, the `/src` line becomes dead and should be removed | read `.distignore` |
| B-11 | `constitution.md` §Architecture & UI Standards declares the **Boot Flow Rule** ("All hooks trace to `define_admin_hooks()` / `define_public_hooks()`") and the **Admin Rule** ("Any class that calls `add_menu_page()`, enqueues assets, or renders HTML MUST live in the admin partials layer"); after this cleanup these rules describe layers that no longer exist | read `.specify/memory/constitution.md` lines 118–131 |
| B-12 | `vendor/` and `node_modules/` are present in the working tree; they are NOT touched by this feature — the user re-runs `composer install` and `npm install` after the cleanup | `ls vendor node_modules` |

---

## CHANGE-1 — DELETE `admin/` folder

Remove the entire `admin/` directory recursively. After deletion `ls admin` must report no such file or directory.

Files removed (4):
- `admin/index.php`
- `admin/Main.php`
- `admin/Partials/index.php`
- `admin/Partials/Menu.php`

---

## CHANGE-2 — DELETE `public/` folder

Remove the entire `public/` directory recursively.

Files removed (4):
- `public/index.php`
- `public/Main.php`
- `public/Partials/index.php`
- `public/Partials/display.php`

---

## CHANGE-3 — DELETE `src/` folder

Remove the entire `src/` directory recursively.

Files removed:
- `src/js/backend.js`, `src/js/frontend.js`
- `src/scss/backend.scss`, `src/scss/frontend.scss`
- `src/media/bookshelf.webp`, `src/media/purple-sunset.webp`

---

## CHANGE-4 — DELETE `build/` folder

Remove the entire `build/` directory recursively. All enqueued CSS/JS targets disappear with `admin/` and `public/`, so the compiled output is orphaned.

---

## CHANGE-5 — `includes/Main.php` — strip admin/public hook wiring

**Edit 1** — `load_hooks()` (lines 171–182):

```php
// Before:
public function load_hooks() {
    if ( apply_filters( 'acrossai_core_abilities_load', true ) ) {
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
}

// After:
public function load_hooks() {
    /**
     * Reserved for future hook registration.
     *
     * Use the `acrossai_core_abilities_load` filter to gate plugin loading.
     */
    apply_filters( 'acrossai_core_abilities_load', true );
}
```

**Edit 2** — DELETE the entire `define_admin_hooks()` method (lines 240–261) and the entire `define_public_hooks()` method (lines 263–277), including their docblocks.

No other content in `includes/Main.php` changes.

---

## CHANGE-6 — `composer.json` — autoload + dev deps

**Edit 1** — `autoload.psr-4` (lines 25–31): replace the three-namespace map with the includes-only map.

```json
// Before:
"autoload": {
    "psr-4": {
        "Acrossai_Core_Abilities\\Includes\\": "includes/",
        "Acrossai_Core_Abilities\\Admin\\": "admin/",
        "Acrossai_Core_Abilities\\Public\\": "public/"
    }
}

// After:
"autoload": {
    "psr-4": {
        "Acrossai_Core_Abilities\\Includes\\": "includes/"
    }
}
```

**Edit 2** — `require-dev` (lines 32–36): remove the `phpunit/phpunit` line.

```json
// Before:
"require-dev": {
    "wp-coding-standards/wpcs": "*",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.2",
    "phpunit/phpunit": "^13.2@dev"
}

// After:
"require-dev": {
    "wp-coding-standards/wpcs": "*",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.2"
}
```

After editing, run `composer dump-autoload` to refresh the autoloader.

---

## CHANGE-7 — `phpstan.neon.dist` — drop deleted paths

```neon
# Before:
paths:
    - includes/
    - admin/
    - public/
    - acrossai-core-abilities.php

# After:
paths:
    - includes/
    - acrossai-core-abilities.php
```

No other line changes.

---

## CHANGE-8 — `phpcs.xml.dist` — drop deleted exclude

```xml
<!-- Before (lines 44–46): -->
<rule ref="WordPress.WP.GlobalVariablesOverride">
    <exclude-pattern>*/admin/*</exclude-pattern>
</rule>

<!-- After: -->
<rule ref="WordPress.WP.GlobalVariablesOverride"/>
```

No other line changes.

---

## CHANGE-9 — DELETE `webpack.config.js`; trim `package.json`

**Edit 1** — DELETE `webpack.config.js` outright.

**Edit 2** — `package.json` `scripts` (lines 16–36): remove the npm-build-related entries. Keep the AI/agent tooling entries.

```jsonc
// Keep:
"format", "env", "env:start", "env:stop", "env:restart", "env:clean", "env:reset",
"skillpack", "skillpack:push", "validate-packages", "sync:ai-policy",
"mcp:sync", "mcp:status"

// Remove:
"build", "start", "lint:css", "lint:js", "packages-update", "plugin-zip"
```

**Edit 3** — `package.json` `devDependencies`: remove webpack-only deps that are now unused.

```jsonc
// Remove:
"@wordpress/scripts", "@wordpress/stylelint-config", "copy-webpack-plugin",
"mini-css-extract-plugin", "glob", "path", "webpack-remove-empty-scripts"

// Keep:
"@agents-dev/cli", "@wordpress/env", "@wordpress/eslint-plugin",
"@wordpress/prettier-config"
```

**Edit 4** — `package.json` `dependencies`: remove `@wordpress/icons` (only used by deleted JS).

**Edit 5** — `package.json` `stylelint` block: delete the `stylelint` config object — no SCSS remains to lint.

After editing, run `npm install` to refresh `package-lock.json`.

---

## CHANGE-10 — `.distignore` + `.specify/memory/constitution.md`

**Edit 1** — `.distignore`: remove the `/src` line (line 8). Everything else stays.

**Edit 2** — `.specify/memory/constitution.md` §Architecture & UI Standards:

Replace the existing **Boot Flow Rule** paragraph (lines 120–123) with:

> **Boot Flow Rule**: `includes/Main.php` is the single source of all hook registration.
> When the plugin grows to need admin or public layers, hooks for those layers MUST be
> registered via dedicated `define_admin_hooks()` / `define_public_hooks()` methods on the
> bootstrap class — with no intermediate delegation. All feature classes use the singleton
> `instance()` pattern. Resolve each singleton to a named variable before passing to the
> loader — never inline.

Replace the existing **Admin Rule** paragraph (lines 129–131) with:

> **Admin Rule**: When admin UI is introduced, any class that calls `add_menu_page()`,
> enqueues assets, or renders HTML MUST live in a dedicated `admin/Partials/` namespace.
> Module classes under `includes/` are context-neutral and MUST NOT call admin-only APIs.

Bump the version footer from `1.0.0` → `1.1.0` (MINOR — admin/public layers are now
optional rather than required). Update the SYNC IMPACT REPORT HTML comment at the top
with the version change and a note describing the rule update.

---

## What must NOT change

- Do not touch `vendor/`, `node_modules/`, or `composer.lock` content beyond the autoload refresh in CHANGE-6.
- Do not modify any file under `.agents/`, `.claude/`, `.specify/extensions/`, `.specify/templates/`, `.specify/integrations/`, or `scripts/`.
- Do not edit `acrossai-core-abilities.php` — it already routes only through `includes/`.
- Do not edit `includes/Activator.php`, `includes/Deactivator.php`, `includes/I18n.php`, `includes/Loader.php`, `includes/index.php`.
- Do not delete `package.json` outright — the AI tooling scripts depend on it.
- Do not delete `.eslintrc`, `.prettierignore`, `.wp-env.json`, `.editorconfig`.
- Do not delete `README.md`, `README.txt`, `LICENSE.md`, `LICENSE.txt`, `AGENTS.md`, `CLAUDE.md`, `ai-policy.yml`.
- Do not add a `tests/` directory or `phpunit.xml` — testing scaffolding is out of scope.

---

## CONSTRAINTS

- Exactly 10 changes; 4 deletions (CHANGE-1–4) + 6 edits (CHANGE-5–10).
- After all changes, `find . -maxdepth 1 -type d -not -path '*/\.*' -not -name vendor -not -name node_modules -not -name scripts -not -name docs -not -name languages` must list exactly: `.`, `includes`.
- After CHANGE-5, `grep -n "define_admin_hooks\|define_public_hooks\|Admin\\\\Main\|Public\\\\Main" includes/Main.php` must return zero results.
- After CHANGE-6, `composer dump-autoload` must succeed with no warnings about missing `admin/` or `public/` directories.
- After CHANGE-7, `vendor/bin/phpstan analyse --memory-limit=512M` must pass with zero errors.
- After CHANGE-8, `vendor/bin/phpcs` must pass with zero errors against the `includes/` tree.
- After CHANGE-9, `npm install` must succeed; `webpack.config.js` must not exist.
- After CHANGE-10, `grep -c "define_admin_hooks" .specify/memory/constitution.md` must return 1 (only the now-optional forward reference).
- The plugin must still activate cleanly: `wp plugin activate acrossai-core-abilities` must succeed with no PHP notices, warnings, or fatal errors.

---

## Spec-kit Commands

```markdown
# 3. Plan + guard + security
/speckit.memory-md.plan-with-memory
/speckit.architecture-guard.governed-plan
/speckit.security-review.plan

# 4. Tasks + guard
/speckit.tasks
/speckit.architecture-guard.governed-tasks

# 5. Implement + quality checks
/speckit.architecture-guard.governed-implement
composer dump-autoload
vendor/bin/phpcs
vendor/bin/phpstan analyse --memory-limit=512M
npm install

# 6. Review + memory + commit
/speckit.analyze
/speckit.architecture-guard.architecture-review
/speckit.security-review.staged
/speckit.memory-md.capture-from-diff
/speckit.git.commit
```

---

## Manual Verification Checklist

### CHANGE-1 to 4 — Folder deletions

- [ ] `ls admin public src build 2>&1` returns four "No such file or directory" lines.
- [ ] `git status` shows the deletions are staged or committed.

### CHANGE-5 — `includes/Main.php`

- [ ] `grep -n "define_admin_hooks\|define_public_hooks" includes/Main.php` returns nothing.
- [ ] `grep -n "Acrossai_Core_Abilities\\\\Admin\|Acrossai_Core_Abilities\\\\Public" includes/Main.php` returns nothing.
- [ ] `load_hooks()` still exists and still applies the `acrossai_core_abilities_load` filter.

### CHANGE-6 — `composer.json`

- [ ] `autoload.psr-4` contains exactly one key: `Acrossai_Core_Abilities\\Includes\\`.
- [ ] `require-dev` no longer contains `phpunit/phpunit`.
- [ ] `composer dump-autoload` succeeds with no warnings.

### CHANGE-7 — `phpstan.neon.dist`

- [ ] `paths:` lists exactly `includes/` and `acrossai-core-abilities.php`.
- [ ] `vendor/bin/phpstan analyse --memory-limit=512M` exits 0.

### CHANGE-8 — `phpcs.xml.dist`

- [ ] `grep "admin" phpcs.xml.dist` returns nothing.
- [ ] `vendor/bin/phpcs` exits 0 against the working tree.

### CHANGE-9 — webpack + npm

- [ ] `test -f webpack.config.js && echo PRESENT || echo GONE` prints `GONE`.
- [ ] `package.json` `scripts` no longer contains `build`, `start`, `lint:css`, `lint:js`, `packages-update`, `plugin-zip`.
- [ ] `package.json` `devDependencies` no longer contains `@wordpress/scripts`, `copy-webpack-plugin`, `mini-css-extract-plugin`, `webpack-remove-empty-scripts`, `glob`, `path`.
- [ ] `npm install` exits 0 and updates `package-lock.json`.

### CHANGE-10 — `.distignore` + constitution

- [ ] `.distignore` no longer contains a `/src` line.
- [ ] `grep -n "Boot Flow Rule" .specify/memory/constitution.md` returns the updated paragraph.
- [ ] Constitution version footer reads `1.1.0`.
- [ ] HTML SYNC IMPACT REPORT at the top notes `1.0.0 → 1.1.0`.

### Plugin smoke test

- [ ] `wp plugin activate acrossai-core-abilities` succeeds.
- [ ] WP admin loads with no notices, warnings, or fatal errors.
- [ ] `wp plugin deactivate acrossai-core-abilities` succeeds.
- [ ] `wp plugin uninstall acrossai-core-abilities` does not error.
