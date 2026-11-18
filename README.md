# Villa Rental Platform

Native PHP 8.2+ and MySQL villa rental platform. No PHP framework or Composer dependency is required. The first-run browser installer selects either single-vendor or multi-vendor operation and stores the selected mode in the database.

## Current implementation

- First-run `/install/` wizard: MySQL credentials, initial super-admin credentials, and single-vendor / multi-vendor mode.
- PDO connection service with tracked, startup-applied migrations, Turkish translation catalogue and editor, CSRF protection, output escaping, strict sessions, secure cookie flags, and persistent login throttling.
- Database-driven public homepage, category and destination search, villa detail pages, editable header/footer navigation, and editable CMS pages.
- Admin content editor for homepage copy, site name and footer content.
- Admin listing CRUD, category and amenity catalogues, local image upload/removal (JPEG/PNG/WebP with MIME validation and size limits), and availability / seasonal price rules.
- Guest registration, authenticated booking requests, date-overlap checks, nightly pricing from calendar rules, guest booking history, admin booking approval/cancellation, favorites, booking-scoped guest/host messaging, guest reviews with admin moderation, and in-app notifications.
- Native SMTP email delivery with encrypted credentials, an idempotent retrying outbox, token-protected shared-hosting cron processing, administrator test email, and booking / new-message email notifications. SMTP delivery stays disabled until configured.
- Multi-vendor onboarding, vendor approval, vendor listing management, vendor reservation inbox, vendor-specific calendars, and per-vendor commission rates.
- Admin user activation controls, editable navigation, CMS pages with SEO fields, platform settings, and translation catalogue editing.
- Booking financial ledger, platform commission calculation, iyzico Refund V2 with signed response verification and partial/full refund reconciliation, vendor payout requests with per-booking allocations and audit history, encrypted vendor IBAN profiles, seasonal property pricing with admin/vendor management, season-specific minimum-night and permitted check-in/check-out weekday rules, optional early check-in and late checkout with per-property fees and booking snapshots, iCal calendar import/export with encrypted feeds and cPanel Cron synchronization, and admin-controlled manual payout confirmation; editable translation catalogue.
- Admin-configurable manual bank transfer details plus customer transfer reports, booking-scoped pending-report protection, admin approval/rejection, payment ledger entries, audit trail, and customer notifications.
- 36 editable Turkish demo villa listings with illustrative property imagery; listings and media remain editable from the admin panel.
- A single shared villa card component is used on the homepage and search results, including image galleries, favorites, ratings, booking indicators, and installation-aware image URLs.
- One active `default` theme. Shared top information markup is centralized in `themes/default/partials/shared-topbar.php`; common styling is in `themes/default/style.css`. The topbar has a taller layout and theme-specific height control.
- Theme typography is managed separately for each theme from Admin → Website → Theme Typography. Heading, span, paragraph and paragraph-link families, sizes, colors, weights and styles are configurable; selected Google Fonts are loaded on the storefront. Paragraph button/CTA links are excluded from paragraph-link styling.
- GitHub Actions PHP syntax checks and a MySQL-backed installer / migration smoke test. Retired theme-demo and Page Builder database structures are removed by migration `029_retired_builder_cleanup.sql`.

## Important operational notes

- Manual bank transfers can be reported by customers and are only marked paid after an authenticated admin verifies and approves the report. Approval also checks booking date conflicts before confirming a pending reservation. No automated bank statement matching is provided.
- iyzico Checkout Form supports the explicitly selected Sandbox or Production environment. Each payment transaction stores its originating environment so callback retrieval cannot silently switch between Sandbox and Production. The admin connection test performs a BIN lookup in the selected environment and does not create a payment or charge a card. Production requires valid live iyzico credentials and explicit enablement. PayTR is deferred because no merchant account is available. `payments.enabled` defaults to `0` until a payment method is explicitly enabled in admin settings.
- Automated bank-transfer execution, SMS delivery, full language switching, advanced permissions, provider certification, and real browser-based end-to-end payment tests remain to be implemented. iCal import expands bounded `DAILY` and `WEEKLY` RRULE events with `INTERVAL`, `COUNT`, `UNTIL`, `BYDAY`, and `EXDATE`; unsupported recurrence frequencies or rule fields fail closed instead of silently importing incomplete availability. Expansion is capped at 500 occurrences and a 730-day horizon. iCal import requires PHP cURL and accepts only public HTTPS hosts; set the admin-provided tokenized calendar Cron URL in cPanel (recommended every 30 minutes). Pricing precedence is base weekday rate, weekend rate, matching season, then explicit availability-calendar nightly price; seasonal date ranges for one property cannot overlap. Seasonal stay restrictions apply the highest minimum-night requirement across any overlapping season, enforce arrival weekdays on check-in and departure weekdays on checkout, and are checked again when an administrator or vendor confirms a booking. Vendor payout requests are currently paid manually by an administrator after an external bank transfer; the system records the bank reference and audit trail. Automated refunds currently support iyzico only; manual bank-transfer refunds must be handled outside the platform and recorded by the operator. If an iyzico refund request has an ambiguous network outcome, its amount remains reserved until the operator checks the provider dashboard; it must not be submitted again blindly. SMTP email delivery requires administrator configuration and a cPanel cron job; no real SMTP provider credentials are included.
- The current codebase is an active development build, not a production-ready booking service. Do not accept real payments or rely on it for live inventory until the remaining modules are implemented and a real PHP/MySQL deployment test and security review have passed.
- Demo villa imagery is illustrative and should not be treated as verified photographs of real properties. Demo listings can be edited, unpublished, or deleted from the admin panel.

## Automated checks

GitHub Actions runs PHP syntax checks and an installer smoke test against MySQL 8. The smoke test creates a clean installation, verifies the initial administrator and selected platform mode, checks the demo villa seed and required tables, confirms that retired Page Builder/theme-demo tables and navigation entries are absent, checks the shared topbar partial, and applies migrations twice.

These automated checks do not replace testing on the target shared-hosting provider, real SMTP delivery with the operator's mail provider, real iyzico Sandbox/Production refund verification, real browser testing, payment-provider certification, or a professional security review.

## Installation

Requirements: PHP 8.2+, MySQL 8+ (or compatible MariaDB), PDO MySQL, Fileinfo, and Apache with `mod_rewrite`. PHP cURL is additionally required for outbound iCal feed synchronization.

1. Upload the repository files to the hosting account.
2. Prefer setting the web root to the repository's `public/` directory. A `public/index.php` entrypoint routes requests to the application outside the public directory. If your host cannot use that layout, the repository root can be used temporarily with the supplied root `.htaccess`.
3. Ensure PHP can write to `config/`, `storage/`, and `public/uploads/`.
4. Visit the domain or subdirectory URL. The application redirects to that installation's `/install/` path while not installed.
5. Enter the database credentials, initial administrator account, and platform mode.
6. Sign in at the installation's `/login` path.

The application detects its base URL automatically and supports installation either at the domain root or inside a subdirectory (for example, `https://example.com/villas/`). Root-relative links, form actions, redirects, routing, and session cookie paths are adjusted to the detected installation path.

The installer runs `database/schema.sql` and every `database/migrations/*.sql` file in lexical order. Keep the `config/` and `storage/` directories outside the public document root. Review host-specific Apache/PHP permissions before deployment.


## Automatic deployment

Production deployment is handled by the server-side SSH-based automation configured for this project. Do not configure or use the retired FTP workflow. The admin Update Center is a read-only status page; it does not initiate deployments. Runtime configuration, installed database data, and uploaded media must be preserved by the server deployment process.