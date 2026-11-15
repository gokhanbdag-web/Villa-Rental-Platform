# Changelog

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
