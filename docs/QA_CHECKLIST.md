# QA Checklist

## Phase 0: Requirements

- Property details are verified or marked as placeholders.
- Exact 3-month booking rule is documented.
- Any-day start policy is documented.
- BC lease requirement is documented.
- Stripe deposit/payment workflow is documented.
- Chat provider requirements are documented.

## Phase 1: Local Foundation

- Local WordPress site opens over HTTPS.
- `One Water West Stay` theme activates.
- `One Water Booking Core` plugin activates.
- Permalinks have been saved.
- Git ignores uploads, secrets, WordPress core, and database dumps.

## Phase 2: Public Pages

- Homepage hero renders on desktop and mobile.
- Apartment facts are visible.
- Amenities section includes both pools, hot tub, pickleball, BBQs, firepits, gym, pilates machines, Peloton mirror, cardio stations, event room, and office space.
- Availability CTA links to the booking page.
- Apartment page (slug `apartment`) renders the `page-apartment` template with editable content followed by the photo gallery section groups.
- Gallery photos open in the lightbox viewer and close via the close button, backdrop click, or Escape key.
- Gallery grid collapses to a single column on mobile.
- Footer explains request-to-book and lease/payment requirements.

## Phase 3: Calendar Rules

- Calendar loads availability through `/wp-json/onewater/v1/availability`.
- Selecting a start date displays the exact 3-month checkout date.
- Dates inside minimum notice window are disabled.
- Dates overlapping confirmed reservations are disabled.
- Dates overlapping pending reservations are disabled.
- Dates overlapping blocked/maintenance windows are disabled.

## Phase 4: Admin Workflow

- Manager can create a reservation manually.
- Manager can set reservation status to pending request, pending payment, confirmed, owner blocked, or cancelled.
- Manager can set payment status.
- Manager can set BC lease status.
- Manager can add blocked date ranges.
- Public calendar reflects admin changes.

## Phase 5: Payment, Lease, Email

- Stripe test mode is configured before staging tests.
- Booking request creates pending request state.
- Manager approval can move request toward payment.
- Lease status remains required until signed.
- Admin notification sends through staging-safe email.
- No live emails are sent during local testing.

## Phase 6: API and Chat

- Availability endpoint returns expected JSON.
- Reservation endpoint rejects unavailable dates.
- Reservation endpoint creates valid pending requests.
- Chat provider has been selected or marked as a launch blocker.
- Future mobile app limitations are documented.

## Phase 7: Staging

- Plugin licenses are active where needed.
- Stripe remains in test mode.
- SMTP uses staging-safe configuration.
- Mobile layouts pass.
- Page speed, SEO metadata, analytics, backups, and security settings pass review.

## Phase 8: Production

- Final owned/licensed photos are installed.
- Live Stripe keys are configured.
- Production SMTP is configured.
- Domain, SSL, Cloudflare, caching, backups, and analytics are live.
- A rollback plan exists.
- A full test booking has been reviewed.
