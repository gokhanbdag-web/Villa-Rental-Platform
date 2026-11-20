# Code quality and architecture guardrails

## Scope and compatibility
- Keep the existing native PHP 8.2+ / MySQL architecture. Do not rewrite the application or redesign its themes.
- Preserve public routes, admin URLs, rendered HTML/CSS class contracts, database schema compatibility, and booking/payment behavior unless a separate tested change explicitly requires otherwise.
- Prefer small, behavior-preserving refactors with regression coverage. Never mix large formatting changes with functional changes.
- Keep runtime credentials, uploaded media, and installed data out of source-control changes.

## PHP conventions
- Use PSR-12-style indentation, spacing, braces and readable multiline conditionals for new or modified PHP.
- Use strict types in standalone PHP classes where compatible; avoid adding it blindly to shared view fragments.
- Give methods a single clear responsibility. Avoid dead branches, duplicated conditions, and unnecessary globals.
- Escape untrusted output and use parameterized SQL. Keep authorization and CSRF checks intact.
- Treat view partials as presentation code; move nontrivial business rules into dedicated services gradually, with tests.
- Keep translations and existing theme customization compatible.

## Refactoring sequence
1. Inventory public entrypoints, routes, services, templates, migrations and external integrations.
2. Remove proven unreachable code and establish formatting conventions without changing behavior.
3. Add regression tests for the affected behavior before extracting large classes.
4. Extract focused services behind existing public APIs; migrate callers incrementally.
5. Verify installation, upgrades, locale switching, authentication, bookings, payments and theme rendering before each merge.

## Required checks before merge
- PHP syntax validation and existing installer/MySQL smoke tests must pass.
- Review diffs for changed routes, markup, query semantics and data migrations.
- Test high-risk workflows on a representative installation when automation does not cover them.
- Merge only after green checks; document any untested behavior explicitly.

## Initial audit findings (not a complete repository audit)
- `app/Core/ThemeManager.php` mixes theme discovery, settings, assets, rendering and presentation concerns. Split only after call-site inventory and regression coverage.
- `themes/default/partials/navigation-items.php` contained an unreachable authentication branch after a `continue`; removed in the first cleanup PR.
- `app/Core/Translator.php` loads translation data from PDO during locale setup. Measure usage and preserve fallback semantics before optimizing.
- `index.php` invokes the migrator on normal requests. Inspect idempotency and concurrency before altering startup behavior.
- Existing files use inconsistent whitespace and statement density. Standardize incrementally in touched areas rather than reformatting the whole repository at once.
