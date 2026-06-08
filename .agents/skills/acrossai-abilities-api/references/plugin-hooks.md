# Plugin hooks reference — acrossai_abilities_api_init

## Ability_Definition — class-based authoring API

`AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition` is the recommended way to author abilities. It wires the filter automatically and enforces the definition contract at the class level.

### Abstract methods

| Method | Return type | Maps to definition field |
|--------|-------------|--------------------------|
| `main_key()` | `string` | `main_key` |
| `main_key_label()` | `string` | `main_key_label` |
| `sub_key()` | `string` | `sub_key` |
| `sub_key_label()` | `string` | `sub_key_label` |
| `ability()` | `array` | `name` + `args` (see below) |

`ability()` must return:

```php
[
    'name' => 'plugin-slug/ability-slug',  // WordPress ability ID
    'args' => [
        'label'            => '...',
        'description'      => '...',
        'category'         => '...',
        'execute_callback' => [ $this, 'execute' ],
        'meta'             => [ 'show_in_rest' => true ],
    ],
]
```

### How the constructor wires the filter

```php
public function __construct() {
    add_filter( 'acrossai_abilities_api_init', [ $this, 'push_definition' ] );
}
```

`push_definition()` is a public method on the base class. It calls all five abstract methods and appends the assembled definition to `$definitions`. You never call it directly.

### Instantiation requirement

Each ability class must be instantiated **before `init P99`** (when the Registry fires). The canonical place is `plugins_loaded P20`, guarded by `class_exists`:

```php
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( '\AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition' ) ) {
        return; // Manager not active.
    }
    new \My_Plugin\Includes\Abilities\Get_Site_Info();
}, 20 );
```

Do **not** instantiate at `init` — the Registry fires at `init P99`, which may be the same request cycle in which your class is loaded.

---

## The filter contract

```
Filter name : acrossai_abilities_api_init
Fires at    : init priority 99 (AcrossAI Library Registry)
Add-on hook : standard init priority 10 (before collection at P99)
Returns     : array<int, array<string, mixed>>  — full accumulated definitions array
```

**Always append to the incoming array and return it:**

```php
add_filter( 'acrossai_abilities_api_init', function( array $definitions ): array {
    $definitions[] = [ /* your definition */ ];
    return $definitions;
} );
```

Do **not** replace the array — other add-ons may have already pushed definitions.

---

## Full definition schema

Each item in the `$definitions` array must contain **all six top-level keys**:

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `main_key` | `string` | ✅ | Machine key for the ability group. Shown as card in Library grid. Use `sanitize_key()` format (lowercase, hyphens, no spaces). |
| `main_key_label` | `string` | ✅ | Human-readable group label displayed in the Library admin card header. |
| `sub_key` | `string` | ✅ | Machine key for this specific ability slot within the group. Use `sanitize_key()` format. |
| `sub_key_label` | `string` | ✅ | Human-readable label for this ability slot. Shown as a checkbox label in Specific mode. |
| `name` | `string` | ✅ | WordPress ability ID (`namespace/ability-name`). Passed as first arg to `wp_register_ability()`. |
| `args` | `array` | ✅ | WordPress ability registration args (see below). Must be a non-empty array. |

### args allowlist

Only these keys inside `args` pass AcrossAI's validation filter. **All other keys are silently stripped:**

| `args` key | Purpose |
|-----------|---------|
| `label` | Human-readable name |
| `description` | What the ability does |
| `category` | Category ID (must be registered first via `wp_abilities_api_categories_init`) |
| `execute_callback` | Callable that executes the ability |
| `permission_callback` | Optional callable to gate execution by current user |
| `meta` | Array — set `show_in_rest: true` to expose via REST; `readonly: true` for informational abilities |

For the full set of WordPress-native `args` keys and their semantics, see `../wp-abilities-api/references/php-registration.md`.

---

## Validation rules

Definitions that fail validation are **silently skipped** (no PHP fatal). Errors are logged only when `WP_DEBUG_LOG` is true.

A definition is skipped when:
- it is not an array,
- any of the six required keys is missing or empty string (`''`),
- `args` is not an array.

After validation:
- `main_key` and `sub_key` are sanitized with `sanitize_key()` + truncated to 100 characters.
- `main_key_label` and `sub_key_label` are sanitized with `wp_kses_post()`.
- `name` is sanitized with `sanitize_key()`.

---

## Multi-ability example (two sub_keys under one main_key)

### Class-based (recommended)

```php
// includes/Abilities/Summarize.php
class Summarize extends Ability_Definition {
    protected function main_key(): string       { return 'content-tools'; }
    protected function main_key_label(): string { return __( 'Content Tools', 'my-plugin' ); }
    protected function sub_key(): string        { return 'summarize'; }
    protected function sub_key_label(): string  { return __( 'Summarize', 'my-plugin' ); }

    protected function ability(): array {
        return [
            'name' => 'my-plugin/summarize',
            'args' => [
                'label'            => __( 'Summarize Content', 'my-plugin' ),
                'description'      => __( 'Generates a summary of provided text.', 'my-plugin' ),
                'category'         => 'content-tools',
                'execute_callback' => [ $this, 'execute' ],
                'meta'             => [ 'show_in_rest' => true ],
            ],
        ];
    }

    public function execute( array $input = [] ): array { /* ... */ }
}

// includes/Abilities/Generate.php
class Generate extends Ability_Definition {
    protected function main_key(): string       { return 'content-tools'; }
    protected function main_key_label(): string { return __( 'Content Tools', 'my-plugin' ); }
    protected function sub_key(): string        { return 'generate'; }
    protected function sub_key_label(): string  { return __( 'Generate', 'my-plugin' ); }

    protected function ability(): array {
        return [
            'name' => 'my-plugin/generate',
            'args' => [
                'label'               => __( 'Generate Content', 'my-plugin' ),
                'description'         => __( 'Generates new content from a prompt.', 'my-plugin' ),
                'category'            => 'content-tools',
                'execute_callback'    => [ $this, 'execute' ],
                'permission_callback' => fn() => current_user_can( 'edit_posts' ),
                'meta'                => [ 'show_in_rest' => true ],
            ],
        ];
    }

    public function execute( array $input = [] ): array { /* ... */ }
}

// Bootstrap (plugins_loaded P20)
new \My_Plugin\Includes\Abilities\Summarize();
new \My_Plugin\Includes\Abilities\Generate();
```

### Raw filter (alternative)

```php
add_filter( 'acrossai_abilities_api_init', function( array $definitions ): array {

    $definitions[] = [
        'main_key'       => 'content-tools',
        'main_key_label' => __( 'Content Tools', 'my-plugin' ),
        'sub_key'        => 'summarize',
        'sub_key_label'  => __( 'Summarize', 'my-plugin' ),
        'name'           => 'my-plugin/summarize',
        'args'           => [
            'label'            => __( 'Summarize Content', 'my-plugin' ),
            'description'      => __( 'Generates a summary of provided text.', 'my-plugin' ),
            'category'         => 'content-tools',
            'execute_callback' => 'my_plugin_summarize',
            'meta'             => [ 'show_in_rest' => true ],
        ],
    ];

    $definitions[] = [
        'main_key'       => 'content-tools',
        'main_key_label' => __( 'Content Tools', 'my-plugin' ),
        'sub_key'        => 'generate',
        'sub_key_label'  => __( 'Generate', 'my-plugin' ),
        'name'           => 'my-plugin/generate',
        'args'           => [
            'label'               => __( 'Generate Content', 'my-plugin' ),
            'description'         => __( 'Generates new content from a prompt.', 'my-plugin' ),
            'category'            => 'content-tools',
            'execute_callback'    => 'my_plugin_generate',
            'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
            'meta'                => [ 'show_in_rest' => true ],
        ],
    ];

    return $definitions;
} );
```

Both approaches produce the same result: `main_key = 'content-tools'` → one card in the Library grid with two checkboxes (Summarize, Generate) when Specific mode is active.

---

## Default enabled behavior

By default **all abilities are enabled**. Only admin overrides are stored in `acrossai_library_config`:

- **Absent from config** = enabled (mode: all). No entry needed.
- **Stored in config** = only when admin disabled the group, switched to Specific mode, or disabled a specific sub_key.

The AcrossAI Library Processor reads config at `wp_abilities_api_init P5` and calls `wp_register_ability()` only for permitted definitions. Your add-on does not need to implement any gating logic — the plugin handles it.
