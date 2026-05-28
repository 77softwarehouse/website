# Stack Decisions

## Hosting

Recommended production host: WP Engine or Kinsta.

Reason: both support professional WordPress workflows, staging environments, backups, SSL, caching, CDN integrations, and custom code better than a simple hosted WordPress.com setup.

## Booking

Production default: MotoPress Hotel Booking.

Reason: the project is a single-apartment rental, so MotoPress is lower friction than a broader ecommerce workflow. The custom `One Water Booking Core` plugin provides project-specific exact 3-month validation, admin controls, API readiness, and a request-to-book calendar that can be connected to MotoPress data if MotoPress becomes the source of truth.

## Payments

Default: Stripe deposits after manager approval.

The reservation is not final until approval, payment/deposit, and BC lease signature are complete.

## Email

Default: WP Mail SMTP plus a transactional provider such as Postmark, SendGrid, or Mailgun.

## Chat

Provider must support:

- Web chat.
- Mobile SDKs.
- Manager assignment.
- Persistent history.
- Push notifications.

Shortlist: Intercom, Zendesk, Crisp, or Twilio Conversations.

## Future Mobile App

Mobile app is a future phase after the website works. The REST API foundation should remain documented and stable enough to support a future app without rebuilding reservation logic.
