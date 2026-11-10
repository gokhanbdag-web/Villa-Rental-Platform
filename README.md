# Villa-Rental-Platform

Standalone villa rental platform using native PHP 8.2+ and MySQL. Single-vendor and multi-vendor modes share one codebase and are selected during first-run installation.

## Product invariants

- No PHP framework.
- No hardcoded business data, listings, prices, destinations, categories, page copy, or marketing sections.
- Business content is database-driven and intended to be administered through CMS and management modules.
- Turkish is the initial locale; UI strings are stored in the database translation catalogue and additional locales are supported.
- First visit redirects to /install while installation is incomplete.
- Installer collects DB credentials, administrator username/email/password, and operating mode.
- Installer creates the initial schema/admin account, stores protected configuration, and locks itself after completion.
- Single-vendor mode suppresses marketplace vendor workflows; multi-vendor mode enables vendor onboarding, isolated dashboards, commissions, balances, and payouts.
- Demo listings are optional editable seed data, never required for production installation.
- PDO prepared statements, password hashing, CSRF protection, output escaping, authorization, validation, and audit logs are mandatory.

## Implemented foundation

- Front controller, autoloader, route dispatcher
- First-visit installation page and installation lock
- Database connection service and database-backed translation loader
- Initial users/settings/languages/translations schema
- Marketplace schema migration for vendors, categories, properties, media, amenities, availability, bookings, reviews, messages, favorites, CMS pages, and audit logs
- CSRF and output escaping helpers

## Not yet production-ready

Authentication/session lifecycle, role and permission enforcement, admin interface, public listing/search UI, booking/pricing engine, payment integrations, vendor workflows, CMS editor, translation management screens, optional demo dataset (30–40 listings), and automated integration/security tests remain to be implemented and verified. The migration must be integrated into the installer before a clean install can create the full marketplace schema.

## Install

Upload to a PHP 8.2+ host with MySQL 8+ and PDO MySQL. Ensure config/ and storage/ can be created by PHP during installation. Visit the domain and complete /install. The current development layout uses the repository root as document root; production deployment hardening will move the public entry point into public/.
