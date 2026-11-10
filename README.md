# Villa Rental Platform

Native PHP 8.2+ and MySQL villa rental platform. No PHP framework is used. The first-run installer selects either single-vendor or multi-vendor operation and stores the selected mode in the database.

## Current implementation

- First-run `/install/` wizard: MySQL credentials, initial super-admin credentials, and single-vendor / multi-vendor mode.
- PDO connection service, database migrations, Turkish translation catalogue, CSRF protection, output escaping, strict sessions and secure cookie flags.
- Database-driven public homepage, category and destination search, villa detail pages, editable header/footer navigation, and editable CMS pages.
- Admin content editor for homepage copy, site name and footer content.
- Admin listing CRUD, category management, local image uploads (JPEG/PNG/WebP with MIME validation and size limits), and availability / seasonal price rules.
- Guest registration, authenticated booking requests, date-overlap checks, nightly pricing from calendar rules, guest booking history, admin booking approval/cancellation, favorites, booking-scoped guest/host messaging, guest reviews with admin moderation, and in-app notifications.
- Multi-vendor onboarding, vendor approval, vendor listing management, vendor-specific calendars, and per-vendor commission rates.
- Booking financial ledger, platform commission calculation, payment transaction, payout and notification database structures; editable translation catalogue.
- 36 editable Turkish demo villa listings with 36 local SVG placeholder illustrations under `public/demo/villas/`.
- GitHub Actions PHP syntax-check workflow.

## Important operational notes

- Payment gateway processing is not connected yet. `payments.enabled` defaults to `0`; booking requests are stored as unpaid and no card payment is taken.
- Email/SMS delivery, payment gateway integration, payout execution, advanced permissions, language switching, email/SMS delivery, and full automated integration/security tests remain to be implemented.
- The current codebase is an active development build, not a production-ready booking service. Do not accept real payments or rely on it for live inventory until the remaining modules are implemented and a real PHP/MySQL deployment test and security review have passed.
- The sample villa illustrations are local SVG artwork, not photographs of real properties. Demo listings can be edited, unpublished, or deleted from the admin panel.

## Installation

Requirements: PHP 8.2+, MySQL 8+ (or compatible MariaDB), PDO MySQL, Fileinfo, and Apache with `mod_rewrite`.

1. Upload the repository files to the hosting account.
2. Prefer setting the web root to the repository's `public/` directory. A `public/index.php` entrypoint routes requests to the application outside the public directory. If your host cannot use that layout, the repository root can be used temporarily with the supplied root `.htaccess`.
3. Ensure PHP can write to `config/`, `storage/`, and `public/uploads/`.
4. Visit the domain. The application redirects to `/install/` while not installed.
5. Enter the database credentials, initial administrator account, and platform mode.
6. Sign in at `/login`.

The installer runs `database/schema.sql` and every `database/migrations/*.sql` file in lexical order. Keep the `config/` and `storage/` directories outside the public document root. Review host-specific Apache/PHP permissions before deployment.
