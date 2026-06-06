# Bug Patterns & Prevention

---

### 2026-06-07 - Stale docblocks reference deleted classes after directory removal

**Status**
Active

**Failure Mode**
After deleting `admin/` and `public/` and stripping executable references from `includes/Main.php`,
a `@var` / `@param` / `@return` docblock in `load_dependencies()` still named `Admin\Main`,
`Admin\I18n`, and `Acrossai_Core_Abilities_Public`. PHPStan would have flagged these as unknown
class references at level 8.

**Prevention**
After any directory deletion, grep for class names in ALL contexts — not just executable code:
```bash
grep -rn "Admin\\\\\\|Public\\\\\\" includes/
```
This catches docblock references that survive a pure executable-code grep.

**Evidence**
Feature 001 T021 verification — `grep -n "Admin\\\\Main"` returned a match at line 208 of
`includes/Main.php` in the `load_dependencies()` docblock, even after all executable references
were removed. Fixed by cleaning the docblock before marking T021 passed.

---

### 2026-06-07 - composer dump-autoload mandatory after PSR-4 namespace removal

**Status**
Active

**Failure Mode**
Removing `"Acrossai_Core_Abilities\\Admin\\"` and `"Acrossai_Core_Abilities\\Public\\"` from
`composer.json` `autoload.psr-4` without running `composer dump-autoload` leaves the cached
classmap referencing deleted directories. The plugin will throw a fatal class-not-found error on
activation even though the source directories no longer exist.

**Prevention**
Always run `composer dump-autoload` immediately after editing `autoload.psr-4` in `composer.json`.
This applies to both additions and removals. The regenerated autoloader in `vendor/` is what PHP
actually loads — `composer.json` is just the source of truth for the next generation.

**Evidence**
Feature 001 T007 — sequenced explicitly as a mandatory post-step after T006 (composer.json edit).
