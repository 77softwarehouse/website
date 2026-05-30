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
7. Add pages for Home, Apartment, Availability & Booking, Seasons, Rates, Location, FAQ, and Contact. Use the slug `apartment` for the Apartment page so the `page-apartment` template renders the page content with the built-in photo gallery.
8. Place `[onewater_booking_calendar]` on the Availability & Booking page.
9. Add a "My Bookings" page with the slug `my-bookings` and place `[onewater_my_bookings]` on it. This is where guests sign in and review, change, or cancel their reservations.
10. Use Stripe test keys and staging-safe email settings only until production launch.

## Guest Login (Self-Service Bookings)

Guests sign in to manage their own reservations on the `my-bookings` page. The custom plugin provides the booking ownership, the `My Bookings` review UI, the auth-aware header nav, and the authenticated REST endpoints (`/onewater/v1/my-reservations`, `PATCH /onewater/v1/reservations/{id}`, `POST /onewater/v1/reservations/{id}/cancel`). Identity itself is provided by free third-party plugins you install in WordPress:

1. Install and activate **Nextend Social Login** (free tier: Google + Facebook). Add Google OAuth and Facebook App credentials in its settings. New accounts should default to the `subscriber` role.
2. Install and activate a free magic-link plugin (e.g. **Passwordless Login** by Cozmoslabs) so guests without Google/Facebook can sign in with any email.
3. The `My Bookings` login panel automatically renders the `[nextend_social_login]` and `[passwordless-login]` shortcodes when those plugins are active, plus a standard email/password fallback.
4. OAuth requires HTTPS callbacks. In Local by Flywheel, enable and trust the site SSL certificate so the site serves at `https://onewaterweststay.local`, then use that URL for provider redirect URIs. Magic-link emails are captured by Local's built-in MailHog mailbox.

### Registration policy

You can leave **Settings > General > "Anyone can register"** unchecked. The plugin filters `option_users_can_register` to allow account creation only during a recognized social-login or magic-link callback, so guests can still sign up through Google/Facebook/magic-link while the generic `wp-login.php?action=register` form stays closed to spam. Set **New User Default Role** to `Subscriber` so self-service guests get the low-privilege role.

## Booking Availability Rules

- Every stay is an exact 3-month term: the renter picks a start date and checkout is calculated automatically 3 months later.
- Once a reservation is both **confirmed** and fully **paid**, it reserves up to **90 days per calendar year**.
- For stays that cross into a new year, only the days that fall within a given year count toward that year's 90-day limit, so a cross-year booking does not block both years entirely.
- Because an exact 3-month quarter can run slightly longer than 90 days, the first confirmed-and-paid stay in an otherwise-open year is always allowed (90 days, or the full 3 months otherwise). Additional bookings are then blocked once a year reaches its limit.

## Important Production Notes

This repo intentionally does not include WordPress core, uploads, database dumps, secrets, plugin license keys, or third-party paid plugins. MotoPress should be installed and licensed directly in the target WordPress environment.

The custom plugin provides the project's API-ready booking foundation and a working request-to-book calendar. In production, it can either remain the source of truth or be adapted to read/write MotoPress reservation data once MotoPress is configured.
