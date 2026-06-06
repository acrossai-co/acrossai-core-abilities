# Library admin UI and config reference

## Admin page

- **Location**: wp-admin → Abilities Manager → Library
- **Slug**: `acrossai-abilities-library`
- **Hook suffix**: `acrossai-abilities-manager_page_acrossai-abilities-library`

The Library page renders a DataViews grid of ability group cards — one card per distinct `main_key` registered by active add-ons.

---

## Card controls

| Control | What it does |
|---------|-------------|
| Master toggle (ON/OFF) | Enables or disables the entire `main_key` group |
| Mode selector (All / Specific) | "All" registers every sub_key; "Specific" shows per-sub_key checkboxes |
| Sub-key checkboxes | Visible only in Specific mode; individually enables/disables each `sub_key` |

Changes auto-save within ~1 second of last interaction (1000ms debounce). No Save button.

---

## Config storage — sparse site option

**Site option key**: `acrossai_library_config`

Config is stored sparsely: **only entries that deviate from the default are persisted.** The default state (enabled = true, mode = all) is never written to the option.

```json
{
  "content-tools": {
    "enabled": false,
    "mode": "all",
    "sub_keys": {}
  },
  "media": {
    "enabled": true,
    "mode": "specific",
    "sub_keys": {
      "summarize": true,
      "generate": false
    }
  }
}
```

**Absent key = enabled by default** (mode: all). The Processor applies this default; the stored option never contains phantom entries.

### Default resolution rules

| Condition | Resolved as |
|-----------|-------------|
| `main_key` not in config | enabled = true, mode = all |
| `main_key` in config, `enabled` missing | enabled = true |
| `main_key` disabled | all sub_keys skipped |
| mode = all | all sub_keys registered |
| mode = specific, `sub_key` not in `sub_keys` | disabled by default |
| mode = specific, `sub_key` present | value from `sub_keys[sub_key]` (bool) |

---

## REST config endpoint

| | |
|---|---|
| **Namespace** | `acrossai-abilities-library/v1` |
| **Path** | `/abilities/config` |
| **GET** | Returns full saved config object (`{}` if nothing saved) |
| **POST** | Accepts full config, sanitizes, strips defaults, saves, returns saved state |
| **Auth** | `manage_options` capability + `X-WP-Nonce: wp_rest` header |

The React UI in the Library page reads this endpoint on mount and POSTs on every debounced change.

---

## Deactivation / reactivation behavior

- When an add-on is **deactivated**, its `main_key` groups disappear from the Library grid.
- The saved config for those groups is **preserved** in `acrossai_library_config`.
- When the add-on is **reactivated**, the groups reappear with the previously saved toggle state restored.

This means: if an admin disabled a group before the add-on was deactivated, it will still be disabled after reactivation.

---

## Uninstall

If the AcrossAI Abilities Manager plugin is uninstalled with "delete data on uninstall" enabled, `acrossai_library_config` is deleted via `delete_site_option('acrossai_library_config')`.
