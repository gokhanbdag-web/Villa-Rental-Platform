# Villa-Rental-Platform

Native PHP 8.2+ and MySQL villa rental marketplace. Single-vendor and multi-vendor modes are selected during first-run installation and share one codebase.

## Non-negotiable rules

- No PHP framework.
- No hardcoded business records, listings, prices, destinations, categories, page content, or marketing sections.
- Business data is database-driven. UI text is resolved through the database translation catalogue (Turkish is the initial locale).
- First visit redirects to /install while installation is incomplete.
- Installer collects database credentials, administrator username/email/password, and operating mode; creates schema, migrations, administrator, protected config, and installation lock.
- Single-vendor and multi-vendor features are controlled by the saved operating mode.
- Demo data is optional, editable seed data and never required for production installation.
- PDO prepared statements, password hashing, CSRF, output escaping, role checks, validation, and audit logs are required.

## Implemented so far

- Front controller, autoloader, route dispatcher, and Apache rewrite rules
- First-run installer with DB/admin/mode fields and installation lock
- PDO database service and database-backed translator
- Initial relational schema and marketplace migration (vendors, properties, categories, media, amenities, availability, bookings, reviews, messages, favorites, CMS pages, audit logs)
- Turkish translation seed migration
- Session login service, login screen, logout action, and initial admin dashboard metrics
- CSRF and output escaping helpers

## Development status

This is an active development repository, not a production-ready release. The current dashboard is a foundation only. Remaining work includes granular roles/permissions, complete listing and vendor management screens, public Airbnb-style search/detail experience, calendar and pricing engine, booking lifecycle, payment provider integrations, payouts/commission accounting, full CMS/theme/SEO management, complete translation editor, notifications, privacy workflows, 30–40 local demo-image listings, and automated PHP/database/security tests. Do not deploy for real bookings until those modules are implemented and tested.

## Local installation

Use PHP 8.2+ with PDO MySQL and MySQL 8+. Apache mod_rewrite is expected for pretty routes. Ensure PHP can create config/ and storage/ during installation. Point the document root to the repository root for this development build, then visit the domain and complete /install. Before production, move the document root to a dedicated public/ directory and review host-specific permissions and security settings.
