# Mobile-App-Ready API

The website must work first. The mobile app is a future phase, but these API contracts keep the backend ready.

Base namespace:

```text
/wp-json/onewater/v1
```

## Availability

```text
GET /availability?month=YYYY-MM
```

Returns each date in the requested month with:

- `date`
- `checkout_date`
- `available`
- `reason`

Used by the website calendar and future mobile app to show valid exact 3-month starts.

## Create Reservation Request

```text
POST /reservations
```

Body:

```json
{
  "start_date": "2026-01-15",
  "name": "Guest Name",
  "email": "guest@example.com",
  "phone": "+1 555 555 5555",
  "message": "Interested in a winter stay."
}
```

Creates a `pending_request` reservation only if the exact 3-month stay does not overlap confirmed, pending, owner-blocked, or maintenance dates.

## Read Reservation

```text
GET /reservations/{id}
```

Requires manager/editor permission in the current implementation. A future renter-facing mobile app should add authenticated renter access scoped to that renter's own reservation.

## Chat Handoff

```text
POST /chat-handoff
```

Returns the selected chat-provider requirements and a placeholder handoff response. The production chat provider must support:

- Web chat.
- Mobile SDKs.
- Manager assignment.
- Persistent history.
- Push notifications.

## Future Mobile App Notes

Before building the mobile app, add authenticated renter accounts, scoped reservation reads, payment-status views, chat-provider identity handoff, and push notifications through OneSignal or Firebase Cloud Messaging.
