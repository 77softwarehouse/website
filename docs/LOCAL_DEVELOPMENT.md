# Local Development Workflow

## Tooling

- Local by Flywheel for local WordPress, PHP, MySQL, and HTTPS.
- Git/GitHub for custom theme, custom plugin, and documentation.
- WP-CLI for search-replace, plugin activation, exports, and sanity checks.
- Stripe test mode only.
- Mailpit, Mailhog, or staging-safe SMTP for email testing.

## Setup

1. Create a Local site named `onewaterweststay`.
2. Match PHP/MySQL versions to WP Engine or Kinsta where possible.
3. Link this repo's theme and plugin into Local:
   - `wp-content/themes/onewaterweststay`
   - `wp-content/plugins/onewater-booking-core`
4. Activate the theme and plugin in WordPress admin.
5. Save permalinks once.
6. Create the main pages:
   - Home
   - Apartment (use slug `apartment`; the `page-apartment` template adds the photo gallery)
   - Availability & Booking
   - Seasons
   - Rates
   - Location
   - FAQ
   - Contact
7. Add `[onewater_booking_calendar]` to the Availability & Booking page.

## Daily Workflow

1. Make theme/plugin changes locally.
2. Test the public pages and booking calendar locally.
3. Create sample reservations and blocked dates in WordPress admin.
4. Test REST endpoints with sample data.
5. Push code changes to GitHub.
6. Deploy code to staging.
7. Repeat booking, payment, lease, email, mobile layout, and API checks on staging.
8. Deploy to production only after staging passes QA.

## Data Rules

- Do not commit WordPress core.
- Do not commit `wp-config.php`.
- Do not commit uploads.
- Do not commit database dumps containing renter/payment data.
- Do not commit plugin license keys, Stripe keys, SMTP credentials, or chat-provider secrets.
- Pull production data to local only after sanitizing renter identity, payment, and lease data.
