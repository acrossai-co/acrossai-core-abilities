---
name: acrossai-abilities-api
description: "Use when registering add-on abilities via the AcrossAI plugin's acrossai_abilities_api_init filter — covers the 4 AcrossAI-specific fields (main_key, main_key_label, sub_key, sub_key_label), the Library admin UI config system, and the REST config endpoint."
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
- Using the `acrossai_abilities_api_init` filter hook (not `wp_abilities_api_init` directly),
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
  └─ hooks acrossai_abilities_api_init (standard init priority 10)
       └─ AcrossAI Library Registry collects at init P99
            └─ AcrossAI Library Processor calls wp_register_ability() at wp_abilities_api_init P5
```

**Do NOT hook `wp_abilities_api_init` directly** for add-on abilities that should appear in the Library admin UI. Use `acrossai_abilities_api_init` instead.

See `references/plugin-hooks.md` for the full filter contract and definition schema.

### 3) Define your main_key and sub_key taxonomy

Before writing code, decide:

- **`main_key`** — machine key for the ability group (e.g. `my-plugin`, `content-tools`). One card in the Library admin grid per distinct `main_key`. Use `sanitize_key()` format.
- **`main_key_label`** — human-readable group label shown in the admin card header.
- **`sub_key`** — machine key for the specific ability slot within the group (e.g. `summarize`, `generate`). One checkbox per `sub_key` when mode is Specific. Use `sanitize_key()` format.
- **`sub_key_label`** — human-readable label for that checkbox.

Multiple ability definitions can share the same `main_key`; they will be grouped under one card.

### 4) Register abilities via acrossai_abilities_api_init

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
- The filter receives and returns the full `$definitions` array — **always append, never replace**.
- `name` must follow WordPress ability naming convention (namespaced slug).
- `args` keys are filtered through AcrossAI's `ALLOWED_ARGS_FIELDS` allowlist: `label`, `description`, `category`, `execute_callback`, `permission_callback`, `meta`. Unknown keys are stripped.
- No plugin-class dependency on AcrossAI is required — plain PHP arrays only.

### 5) Register your ability category (via wp_abilities_api_categories_init)

This is standard WordPress; follow `wp-abilities-api` §3. Categories must be registered before the Processor calls `wp_register_ability()` at `wp_abilities_api_init P5`.

### 6) Understand Library admin UI defaults

- **Absent from config** (default): ability is **enabled**, mode is **all**. No admin action needed — all your abilities are active by default.
- Admin can disable a group, switch to Specific mode, or disable individual sub_keys from the Library admin page (`wp-admin → Abilities Manager → Library`).
- Config is stored sparsely: only non-default entries appear in `acrossai_library_config`. See `references/library-config.md`.

### 7) Verify

- Activate your add-on.
- Go to **Abilities Manager → Library** in wp-admin.
- Confirm your `main_key_label` card appears in the grid.
- Confirm all `sub_key_label` entries appear when switching to Specific mode.
- Check that your ability appears at `wp-json/wp-abilities/v1/abilities` (requires `meta.show_in_rest: true`).

---

## Failure modes / debugging

| Symptom | Likely cause |
|---------|-------------|
| Card missing from Library grid | `acrossai_abilities_api_init` filter not firing (wrong hook name, file not loaded, priority after P99) |
| Definition silently skipped | Missing required field (`main_key`, `main_key_label`, `sub_key`, `sub_key_label`, `name`, or non-empty `args`) — check `WP_DEBUG_LOG` |
| Ability arg stripped unexpectedly | Key not in `ALLOWED_ARGS_FIELDS`; only `label`, `description`, `category`, `execute_callback`, `permission_callback`, `meta` pass through |
| Ability not in REST output | `meta.show_in_rest` not set to `true` in `args` |
| Ability registered but not executing | Category not registered before `wp_abilities_api_init P5`; or `execute_callback` not callable |
| Card shows disabled after add-on reactivation | Expected — admin previously disabled this group. Saved config survives deactivation/reactivation. Admin must re-enable manually. |

---

## Escalation

- For WP core Abilities API changes (new args, deprecated hooks, REST namespace changes): update `wp-abilities-api` references only — this skill inherits those changes automatically.
- For AcrossAI-specific config/REST behavior: see `references/library-config.md`.
- For the full filter contract and field validation rules: see `references/plugin-hooks.md`.
