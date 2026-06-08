---
name: acrossai-abilities-api
description: "Use when registering add-on abilities via the AcrossAI plugin — covers the Ability_Definition class-based authoring API, the acrossai_abilities_api_init filter contract, the 4 AcrossAI-specific fields (main_key, main_key_label, sub_key, sub_key_label), the Library admin UI config system, and the REST config endpoint."
compatibility: "Targets WordPress 6.9+ (PHP 7.4+). Requires AcrossAI Abilities Manager plugin."
extends: "wp-abilities-api"
---

# AcrossAI Abilities API — Add-on Registration

## Dependency: read `wp-abilities-api` first

This skill **extends** the `wp-abilities-api` skill. It does not duplicate WordPress Abilities API fundamentals — those live in `wp-abilities-api/SKILL.md` and its references.

> If WordPress updates the Abilities API (new args, new hooks, REST changes), update `wp-abilities-api` only. This skill stays current automatically because it delegates all WP-core Abilities API knowledge to that skill.

Read `wp-abilities-api` before using this skill if you need:
- `wp_register_ability()` arg reference
- `wp_abilities_api_init` hook mechanics
- REST endpoint details (`wp-abilities/v1`)
- JS consumption via `@wordpress/abilities`

---

## When to use this skill

Use **this** skill (not `wp-abilities-api` alone) when the task involves:

- Registering abilities from an **add-on plugin** that targets the AcrossAI Abilities Manager,
- Extending `Ability_Definition` (the recommended authoring API) or using the raw `acrossai_abilities_api_init` filter directly,
- Adding the 4 AcrossAI-specific fields (`main_key`, `main_key_label`, `sub_key`, `sub_key_label`) to a definition,
- Understanding how the Library admin UI gates/enables abilities,
- Reading or writing the `acrossai_library_config` site option.

---

## Inputs required

- Repo root of the add-on plugin.
- Target AcrossAI Abilities Manager version (must be active on the site).
- The desired `main_key` grouping and `sub_key` slot names for your abilities.

---

## Procedure

### 1) Read `wp-abilities-api` for WP core fundamentals

Run through that skill's §1–§4 before writing any PHP, especially:
- hook order: `wp_abilities_api_categories_init` before `wp_abilities_api_init`,
- standard `wp_register_ability()` arg schema (`label`, `description`, `category`, `execute_callback`, `meta`, etc.).

### 2) Understand the AcrossAI registration layer

AcrossAI Abilities Manager introduces a **second filter layer** that sits in front of `wp_abilities_api_init`:

```
add-on plugin
  └─ extends Ability_Definition (or hooks acrossai_abilities_api_init directly)
       └─ AcrossAI Library Registry collects at init P99
            └─ AcrossAI Library Processor calls wp_register_ability() at wp_abilities_api_init P5
```

**Do NOT hook `wp_abilities_api_init` directly** for add-on abilities that should appear in the Library admin UI. Use `Ability_Definition` (recommended) or `acrossai_abilities_api_init` instead.

See `references/plugin-hooks.md` for the full filter contract and definition schema.

### 3) Define your main_key and sub_key taxonomy

Before writing code, decide:

- **`main_key`** — machine key for the ability group (e.g. `my-plugin`, `content-tools`). One card in the Library admin grid per distinct `main_key`. Use `sanitize_key()` format.
- **`main_key_label`** — human-readable group label shown in the admin card header.
- **`sub_key`** — machine key for the specific ability slot within the group (e.g. `summarize`, `generate`). One checkbox per `sub_key` when mode is Specific. Use `sanitize_key()` format.
- **`sub_key_label`** — human-readable label for that checkbox.

Multiple ability definitions can share the same `main_key`; they will be grouped under one card.
Multiple ability definitions can also share the same `main_key` **and** `sub_key` — they are gated together (enabling the sub_key registers all abilities under it).

### 4) Register abilities — class-based approach (recommended)

The `Ability_Definition` abstract base class (provided by `acrossai-abilities-manager`) is the standard authoring API. It hides the filter plumbing and enforces the full definition contract via five abstract methods.

#### 4a) Create one class per ability

```php
<?php
namespace My_Plugin\Includes\Abilities;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;

defined( 'ABSPATH' ) || exit;

class Get_Site_Info extends Ability_Definition {

    protected function main_key(): string {
        return 'my-plugin';
    }

    protected function main_key_label(): string {
        return __( 'My Plugin', 'my-plugin' );
    }

    protected function sub_key(): string {
        return 'get-info';
    }

    protected function sub_key_label(): string {
        return __( 'Get Site Info', 'my-plugin' );
    }

    protected function ability(): array {
        return [
            'name' => 'my-plugin/get-info',
            'args' => [
                'label'            => __( 'Get Site Info', 'my-plugin' ),
                'description'      => __( 'Returns basic site information.', 'my-plugin' ),
                'category'         => 'my-plugin',
                'execute_callback' => [ $this, 'execute' ],
                'meta'             => [ 'show_in_rest' => true ],
            ],
        ];
    }

    public function execute( array $input = [] ): array {
        return [
            'name'       => get_bloginfo( 'name' ),
            'url'        => get_site_url(),
            'wp_version' => get_bloginfo( 'version' ),
        ];
    }
}
```

Key rules:
- `ability()` must return `[ 'name' => '...', 'args' => [...] ]`.
- `execute_callback` can be `[ $this, 'execute' ]` — the executor lives in the same class.
- The `Ability_Definition` constructor calls `add_filter( 'acrossai_abilities_api_init', ... )` automatically. Do **not** add the filter yourself.

#### 4b) Instantiate abilities at plugins_loaded priority 20

In your plugin's main file or `Includes\Main::load_hooks()`:

```php
add_action( 'plugins_loaded', function () {
    // Guard: do nothing if the manager is not active.
    if ( ! class_exists( '\AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition' ) ) {
        return;
    }

    new \My_Plugin\Includes\Abilities\Get_Site_Info();
    // new \My_Plugin\Includes\Abilities\Another_Ability();
}, 20 );
```

Priority 20 ensures the manager plugin (bootstrapped at `plugins_loaded P0`) is available before instantiation.

#### 4c) Add PSR-4 autoloading for the Abilities namespace

In your plugin's `composer.json`:

```json
"autoload": {
    "psr-4": {
        "My_Plugin\\Includes\\": "includes/"
    }
}
```

Place ability classes under `includes/Abilities/` (e.g. `includes/Abilities/Get_Site_Info.php`). Run `composer dump-autoload` after adding classes.

---

### 5) Register abilities — raw filter approach (alternative)

If your add-on does not use Composer autoloading or you prefer the procedural style, push definitions directly onto the filter. Use this only when the class approach is not suitable.

```php
add_filter( 'acrossai_abilities_api_init', function( array $definitions ): array {
    $definitions[] = [
        'main_key'       => 'my-plugin',
        'main_key_label' => __( 'My Plugin', 'my-plugin' ),
        'sub_key'        => 'get-info',
        'sub_key_label'  => __( 'Get Site Info', 'my-plugin' ),
        'name'           => 'my-plugin/get-info',
        'args'           => [
            'label'            => __( 'Get Site Info', 'my-plugin' ),
            'description'      => __( 'Returns basic site information.', 'my-plugin' ),
            'category'         => 'my-plugin',
            'execute_callback' => 'my_plugin_get_info_callback',
            'meta'             => [ 'show_in_rest' => true ],
        ],
    ];
    return $definitions;
} );
```

Key rules:
- Always append to `$definitions` and return it — never replace the array.
- `name` must follow WordPress ability naming convention (namespaced slug).
- `args` keys pass through AcrossAI's `ALLOWED_ARGS_FIELDS` allowlist: `label`, `description`, `category`, `execute_callback`, `permission_callback`, `meta`. Unknown keys are stripped.
- No class dependency on AcrossAI is required — plain PHP arrays only.

### 6) Register your ability category (via wp_abilities_api_categories_init)

This is standard WordPress; follow `wp-abilities-api` §3. Categories must be registered before the Processor calls `wp_register_ability()` at `wp_abilities_api_init P5`.

### 7) Understand Library admin UI defaults

- **Absent from config** (default): ability is **enabled**, mode is **all**. No admin action needed — all your abilities are active by default.
- Admin can disable a group, switch to Specific mode, or disable individual sub_keys from the Library admin page (`wp-admin → Abilities Manager → Library`).
- Config is stored sparsely: only non-default entries appear in `acrossai_library_config`. See `references/library-config.md`.

### 8) Verify

- Activate your add-on.
- Go to **Abilities Manager → Library** in wp-admin.
- Confirm your `main_key_label` card appears in the grid.
- Confirm all `sub_key_label` entries appear when switching to Specific mode.
- Check that your ability appears at `wp-json/wp-abilities/v1/abilities` (requires `meta.show_in_rest: true`).

---

## Failure modes / debugging

| Symptom | Likely cause |
|---------|-------------|
| Card missing from Library grid | Class not instantiated, or instantiated after `init P99`; verify `plugins_loaded P20` bootstrap runs |
| `class_exists` guard exits early | `acrossai-abilities-manager` plugin is not active |
| Definition silently skipped | Missing required field (`main_key`, `main_key_label`, `sub_key`, `sub_key_label`, `name`, or non-empty `args`) — check `WP_DEBUG_LOG` |
| Ability arg stripped unexpectedly | Key not in `ALLOWED_ARGS_FIELDS`; only `label`, `description`, `category`, `execute_callback`, `permission_callback`, `meta` pass through |
| Ability not in REST output | `meta.show_in_rest` not set to `true` in `args` |
| Ability registered but not executing | Category not registered before `wp_abilities_api_init P5`; or `execute_callback` not callable |
| Card shows disabled after add-on reactivation | Expected — admin previously disabled this group. Saved config survives deactivation/reactivation. Admin must re-enable manually. |
| PHP fatal: class not found | `composer dump-autoload` not run after adding new ability class, or `Abilities/` folder missing from PSR-4 map |

---

## Escalation

- For WP core Abilities API changes (new args, deprecated hooks, REST namespace changes): update `wp-abilities-api` references only — this skill inherits those changes automatically.
- For AcrossAI-specific config/REST behavior: see `references/library-config.md`.
- For the full filter contract and field validation rules: see `references/plugin-hooks.md`.
