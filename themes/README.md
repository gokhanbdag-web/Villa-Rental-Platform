# Theme architecture

The `default` theme is the mandatory presentation layer. It cannot be removed or deactivated. Each installed theme has a `theme.json` manifest whose `slug` matches its directory name.

## Responsibility boundary

- The theme owns the complete rendered interface: HTML structure, page templates, reusable components, CSS, JavaScript, responsive behavior, cards, galleries, search/filter controls, calendars, booking presentation, blog layouts, customer and vendor screens, the CMS/admin interface, and every other visual component.
- CMS/admin content data owns editable records and copy: page/article text, images, navigation entries, property data, labels/translations, settings and other content. CMS data does not store or generate CSS/JavaScript or define the visual layout.
- Application code owns business rules and operations: authentication, authorization, reservations, availability calculations, payments, database access, validation and security. Themes may present these capabilities but must not replace their business logic.

## Active-theme cascade and file fallback

- The active theme is selected through the `theme.active` setting. Missing or invalid selections fall back to `default`.
- CSS loads `themes/default/style.css` first and the active theme's `style.css` second. The browser's native CSS cascade merges declarations property-by-property. A rule not declared by the active theme remains inherited from default.
- Theme assets resolve active-first and then default, including nested paths under `assets/`.
- The active theme may override an individual page template under `templates/pages/`, a top-level template under `templates/`, or a reusable PHP partial under `partials/` and `layouts/`. A missing active file falls back to the matching default file.
- Core site views are retained only as a migration/compatibility fallback; new presentation work belongs in `themes/default/`.
- Theme PHP files are presentation templates. They receive prepared data from application routes and must not implement authentication, reservation, payment or other core business rules.

## Suggested structure

```text
themes/
  default/
    theme.json
    style.css
    assets/
      css/
      js/
      images/
      fonts/
    layouts/
    partials/
    templates/
      pages/
        landing.php
        listing.php
        property.php
        booking.php
        destinations.php
        page.php
        account/
```

An active theme can replace one card, calendar, partial, page or asset without copying the rest of the default theme. For example, an active theme's `assets/css/components/villa-card.css` takes precedence over the default file at the same path; files it does not supply continue to come from default.
