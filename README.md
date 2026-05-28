# One Water West Stay

High-end WordPress website foundation for a furnished 3-month seasonal apartment rental at ONE Water Street in Kelowna, BC.

## What This Project Contains

- `wp-content/themes/onewaterweststay`: a block theme for the public luxury rental website.
- `wp-content/plugins/onewater-booking-core`: custom booking, availability, admin-control, REST API, and shortcode foundation.
- `docs`: local development, QA, API, and policy documentation.

## Selected Stack

- WordPress CMS on WP Engine or Kinsta.
- MotoPress Hotel Booking as the production booking system.
- Stripe for deposits and payment status.
- WordPress REST API for future mobile app support.
- Chat provider with web chat, mobile SDKs, manager assignment, persistent history, and push notifications.
- WP Mail SMTP with a transactional provider.
- Cloudflare for DNS, CDN, security, and caching.

## Local Setup

1. Create a fresh WordPress site in Local by Flywheel named `onewaterweststay`.
2. Copy or symlink this repo's `wp-content/themes/onewaterweststay` into Local's `app/public/wp-content/themes/`.
3. Copy or symlink this repo's `wp-content/plugins/onewater-booking-core` into Local's `app/public/wp-content/plugins/`.
4. Activate the `One Water West Stay` theme.
5. Activate the `One Water Booking Core` plugin.
6. Visit `Settings > Permalinks` and save once to refresh rewrite rules.
7. Add pages for Home, Apartment, Availability & Booking, Seasons, Rates, Location, FAQ, and Contact.
8. Place `[onewater_booking_calendar]` on the Availability & Booking page.
9. Use Stripe test keys and staging-safe email settings only until production launch.

## Important Production Notes

This repo intentionally does not include WordPress core, uploads, database dumps, secrets, plugin license keys, or third-party paid plugins. MotoPress should be installed and licensed directly in the target WordPress environment.

The custom plugin provides the project's API-ready booking foundation and a working request-to-book calendar. In production, it can either remain the source of truth or be adapted to read/write MotoPress reservation data once MotoPress is configured.
