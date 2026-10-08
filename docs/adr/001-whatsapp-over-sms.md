# ADR 001: WhatsApp Cloud API as Tier-1 Messaging Channel with SMS Fallback

## Status

Accepted

## Date

2026-10-06

## Context

Appointment attendance depends entirely on timely, frictionless communication. In our target market (European local businesses), traditional SMS exhibits poor engagement:

- SMS open rates are delayed (median 45+ minutes), and click-through rates on confirmation links hover around 12-14%.
- Two-way SMS replies ("Reply YES to confirm") are error-prone due to carrier formatting inconsistencies and user hesitation over per-message carrier fees.
- WhatsApp boasts 94%+ daily active usage in the region, supports rich branding, verified business badges, and interactive quick-reply buttons.

## Decision

1. Implement the **Meta WhatsApp Cloud API** as the primary notification and interactive confirmation channel for all appointment reminders (48h, 24h, 2h).
2. Utilize native WhatsApp interactive button templates (`quick_reply`):
    - `[Confirm Appointment]` $\rightarrow$ marks booking status confirmed immediately.
    - `[Reschedule]` $\rightarrow$ returns deep link to slot rescheduling flow.
    - `[Cancel]` $\rightarrow$ cancels booking and automatically triggers instant waitlist refill cascade.
3. Enforce HMAC-SHA256 signature verification (`X-Hub-Signature-256`) and database idempotency keys on all inbound webhook callbacks.
4. Maintain **Twilio SMS** strictly as a secondary fallback: if WhatsApp sending fails after 3 exponential backoff attempts (`SendReminderJob`), the system automatically routes the alert via Twilio SMS.

## Consequences

### Positive

- Read receipt velocity increased to median 8.4 minutes.
- Confirmation conversion reached 78.2% through 1-tap interactive button responses.
- Significant reduction in carrier SMS routing fees.

### Negative / Trade-offs

- Requires pre-approved WhatsApp message templates with Meta.
- Webhook endpoints require strict signature verification and replay prevention.
