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
- The active theme may replace an individual page template under `templates/pages/`, a top-level template under `templates/`, or a reusable PHP partial under `partials/`, `layouts/`, and `components/`. A missing active file falls back to the matching default file.
- Core site views are retained only as a migration/compatibility fallback; new presentation work belongs in `themes/default/`.
- Theme PHP files are presentation templates. They receive prepared data from application routes and must not implement authentication, reservation, payment or other core business rules.

## Package contract

Every theme package is a directory under `themes/{slug}/` and must contain a UTF-8 `theme.json` manifest with:

- `slug`: directory name; lowercase letters, digits, underscores and hyphens only.
- `name`: non-empty display name.
- `version`: semantic version recommended.
- `author` and `description`: theme metadata.
- `templates`: optional map of logical template names to paths inside the theme.

A theme can be intentionally partial. It does not need to duplicate Default templates: the resolver falls back to Default one file at a time. Keep application logic, database migrations, secrets and user uploads outside theme packages.

## Included themes

- `default`: mandatory base theme and fallback for every installation.
- `coastal`: optional modern coastal presentation. It overrides the visual stylesheet only and inherits all templates and components from Default.

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
  coastal/
    theme.json
    style.css
```

An active theme can replace one card, calendar, partial, page or asset without copying the rest of the default theme. For example, an active theme's `components/cards/villa-card.php` takes precedence over the default file at the same path; files it does not supply continue to come from Default.
