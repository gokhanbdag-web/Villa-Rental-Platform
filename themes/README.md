# Theme architecture

The `default` theme is the required base layer and cannot be removed. Each valid theme directory contains a `theme.json` manifest whose `slug` matches its directory name.

## Cascade and fallback

- The active theme is selected through the `theme.active` setting. Missing or invalid selections fall back to `default`.
- CSS loads `themes/default/style.css` first and the active theme's `style.css` second. The browser's native cascade merges declarations property-by-property. An active theme only overrides declarations it actually supplies.
- Theme assets resolve active-first and then default, including nested paths under `assets/`.
- PHP template mappings prefer the active theme; when the mapped file is absent, the default theme's mapped template is used. Core site views remain the final fallback.
- Shared topbar partials use the same active-first/default fallback.
- Theme PHP files are presentation templates only. Authentication, reservations, payments, database access, authorization and security remain in application code.

## Manifest example

```json
{
  "slug": "coastal",
  "name": "Coastal",
  "version": "1.0.0",
  "templates": {
    "landing.php": "templates/landing.php"
  }
}
```

An active theme may therefore override selected CSS declarations, templates and assets while inheriting everything it does not provide from `default`.
