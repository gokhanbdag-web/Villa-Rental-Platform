# Changelog

## 1.5.8 - 2026-11-16
- Add MySQL-backed iyzico callback integration coverage for successful payment application, booking confirmation, duplicate webhook idempotency, and amount-mismatch rejection without contacting the provider or charging a card.


## 1.5.7 - 2026-11-16
- Enable iyzico Checkout Form in the explicitly selected Sandbox or Production environment, with production hosted payment URL allow-listing.
- Persist each transaction's originating environment and use it for callback and webhook result retrieval, preventing cross-environment lookups.
- Add a selected-environment BIN connection test that validates API credentials without creating a payment or charging a card; clarify production activation guidance and add CI regression coverage.
- Correct the PHP environment-validation syntax and ensure successful and failed checkout responses retain the transaction's selected Sandbox or Production environment.


## 1.5.6 - 2026-11-16
- Add customer manual bank transfer submission with bank instructions, booking reference, sender details, and transfer reference.
- Add admin-only approval/rejection workflow with audit and customer notifications; approval creates an idempotent paid transaction and confirms only conflict-free pending bookings.
- Enforce one pending transfer report per booking and preserve rejected reports for audit while allowing resubmission.

## 1.5.5 - 2026-11-16
- Add versioned manual bank transfer settings for bank name, account holder, IBAN, transfer instructions, and booking reference prefix.
- Add server-side manual payment provider configuration with IBAN validation; manual transfers remain unconfirmed until an authenticated admin verifies the payment.
- Keep online payment activation separate from the manual transfer setup; no new live card-charge path is enabled.

## 1.5.4 - 2026-11-16
- Add super-admin-only cleanup controls for slow HTTP and SQL diagnostic logs, with keep-latest retention choices, explicit confirmation, CSRF protection, and exact removal counts.
- Preserve selected newest log lines in place without touching application, booking, user, or other business data.


## 1.5.3 - 2026-11-16
- Add the diagnostics dashboard to the actual shared admin-shell navigation.
- Move the JSON feed to a dedicated data URL and redirect the former raw diagnostics URL to the styled dashboard.
- Keep the dashboard title visible inside the shared admin shell.


## 1.5.2 - 2026-11-16
- Replace the raw JSON diagnostics endpoint in the admin menu with a responsive system diagnostics dashboard, parsed performance/SQL tables, summary metrics, filtering, and expandable record details.
- Exclude successful MP4/WebM byte-range delivery time from PHP slow-request logs so large hero-video downloads are not misreported as slow application requests.


## 1.5.1 - 2026-11-16
- Make villa average ratings and individual guest review scores more prominent with larger, high-contrast gold rating badges, including mobile sizing.

## 1.5.0 - 2026-11-16
- Fix horizontal overflow in mobile account pages, including bookings and notifications.
- Add the customer My Reviews page with review ratings, property links, and moderation statuses.
- Seed five clearly labeled demo reviews per published villa using inactive demo-only reviewer accounts; demo reviews do not create fake bookings and are excluded from customer-owned reviews.


## 1.4.0 - 2026-11-16
- Unify responsive header drawer styling across public pages and keep the burger icon readable after repeated open/close interactions.
- Remove the conflicting legacy listing-menu handler that replaced the shared icon markup.
- Add indexed full-text blog search, narrow archive result columns, deduplicate repeat view-count writes per session, and batch homepage category-card queries.


## 1.3.1 - 2026-11-16
- Add a selectable recent blog posts widget to footer column 2, with configurable heading, thumbnail/title links, and up to eight individually selected published posts.


## 1.3.0 - 2026-11-16
- Add a shared live SEO score and actionable checklist to page and blog editors, including keyword, snippet preview, headings, readability, image alt text, links, URL, and indexability guidance.
- Add focus keyword storage to CMS pages through a versioned migration and output canonical/indexability metadata on published pages.


## 1.2.2 - 2026-11-16
- Rework admin blog list and editor responsive layouts: prevent grid overflow, stack editor/sidebar fields on compact screens, improve filters/actions, and keep the dense post table safely scrollable on mobile.


## 1.2.1 - 2026-11-16
- Add the WordPress-style blog archive as a selectable system page in menu management, pre-filling its `/blog` URL.


## 1.2.0 - 2026-11-16
- Expand blog into a WordPress-style CMS with SEO metadata, focus keyword, author, reading time, scheduled publishing, featured posts, categories, tags, and view counts.
- Add responsive blog archive and single-post templates with sidebar search, categories, popular posts, tags, newsletter signup, related posts, social sharing, breadcrumbs, and BlogPosting structured data.
- Add 15 Turkish demo articles and starter categories/tags through an idempotent versioned migration.
- Add category management and blog CMS regression smoke tests.

## 1.1.11 - 2026-11-16
- Refine the destination autocomplete smoke assertion to avoid PHP interpolation and escaping ambiguity.

## 1.1.10 - 2026-11-16
- Correct a PHP string-literal issue in the destination autocomplete regression test; no runtime behavior changed.

## 1.1.9 - 2026-11-16
- Improve destination autocomplete matching for full hierarchical labels and ensure mobile suggestion panels are not clipped or hidden behind adjacent sections.
- Retry native hero video seeking when duration/media readiness becomes available after initial metadata; preserve saved start-second settings.

## 1.1.8 - 2026-11-16
- Tighten destination autocomplete regression coverage to count the two actual form controls separately from the shared JavaScript selector.

## 1.1.7 - 2026-11-16
- Wire the mobile destination field into the shared location autocomplete, render selectable suggestions, and submit the selected canonical location ID; keep desktop autocomplete working.

## 1.1.6 - 2026-11-16
- Further compact mobile hero typography and vertical spacing by about 20px so the full search panel is more likely to fit within the initial viewport; desktop remains unchanged.

## 1.1.5 - 2026-11-16
- Slightly reduce the eyebrow, headline and subtitle font sizes inside the mobile homepage hero; desktop typography remains unchanged.

## 1.1.4 - 2026-11-15
- Place the mobile premium search button in the second column beside the guest selector in the first column.

## 1.1.3 - 2026-11-15
- Align the mobile premium search button in the same column as the guest selector.

## 1.1.2 - 2026-11-15
- Prepare a verified deployment-pipeline smoke test and release version marker.

## 1.1.1 - 2026-11-15
- Correct the mobile premium search section ordering so it appears immediately after the hero video, regardless of the homepage section sort order. Desktop and floating search controls remain unchanged.

## 1.1.0 - 2026-11-15
- Add a premium, clean horizontal search bar directly below the homepage hero video on mobile only. Keep desktop hero search and floating comparison/search controls untouched.

## 1.0.9 - 2026-11-15
- Display the current application version from the root VERSION file at the bottom of the admin sidebar, so the admin panel reflects each release.

## 1.0.8 - 2026-11-15
- Remove the redundant standalone mobile hero search form. Keep one canonical booking form inside the hero so the search controls appear in the intended prominent position; desktop layout is unchanged.

## 1.0.7 - 2026-11-15
- Make the mobile homepage hero search unmistakably prominent: move the search panel above the hero copy, add a clear heading, distinct full-width fields and a high-contrast primary search action. Desktop styles remain unchanged.

## 1.0.6 - 2026-11-15
- Stack all footer widgets into one full-width column on mobile and center footer content; leave desktop layout unchanged.

## 1.0.5 - 2026-11-15
- Constrain the desktop homepage hero search form to two flexible columns and force the search button to span the card width without overflowing.

## 1.0.4 - 2026-11-15
- Replace the mobile villa photo carousel with a compact desktop-style collage showing three photos at once; remove the partial next-photo preview and mobile photo counter.

## 1.0.3 - 2026-11-15
- Remove mobile villa-feature tooltips and show feature names beside icons in a balanced two-column layout.

## 1.0.2 - 2026-11-15
- Force white backgrounds and primary-color text for unselected days in the second booking month, including focused states; preserve selected, booked and blocked styles.
- Hide villa feature labels on mobile and reveal each label as a tap-triggered tooltip.

## 1.0.1 - 2026-11-15
- Keep villa features in three columns on mobile screens, including widths at or below 420px.
- Reduce feature icon and label sizing on narrow screens to preserve readability and fit.

## 1.0.0
- Initial version marker.
