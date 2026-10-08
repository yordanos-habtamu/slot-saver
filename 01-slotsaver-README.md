# SlotSaver: No-Show Reduction for a Real Local Business

> Deployed at **[Business name]** ([barbershop / clinic / tutoring]) for 4 weeks.
> No-show rate went from **\__%** to **\__%**, recovering about **$\__/month**.

## The problem

[Owner name] loses roughly __ appointments a week to no-shows. Reminders were
manual, deposits were inconsistent, and there was no visibility into who
repeatedly misses.

## What I built

- Booking PWA with per-trade service configuration (durations, buffers, staff)
- Automated WhatsApp/SMS reminders (48h, 24h, 2h) with one-tap **Confirm / Reschedule / Cancel**
- Waitlist auto-fill: a cancelled slot is offered to the waitlist within minutes
- No-show risk score; high-risk bookings trigger a deposit prompt
- Owner dashboard: no-show rate, recovered revenue, reminder funnel

## Results (fill from real data)

| Metric                       | Baseline (4 wks) | After (4 wks) |
| ---------------------------- | ---------------- | ------------- |
| No-show rate                 |                  |               |
| Late cancellations           |                  |               |
| Slots refilled from waitlist |                  |               |
| Revenue recovered            |                  |               |
| Owner admin time / week      |                  |               |

## Discovery

See [`docs/discovery/`](docs/discovery/): interview notes, current-process map,
and the decisions I made because of what I learned.

## Architecture

[Diagram: docs/architecture.png]
Booking app (Laravel + Inertia/React) -> Postgres
Reminder scheduler (Laravel queue + Redis) -> WhatsApp Cloud API / Twilio
Risk service (FastAPI + scikit-learn) <- nightly feature export
Metabase -> read-only Postgres replica

## Tech stack

Laravel 11, Inertia + React, Postgres 16, Redis, Horizon, WhatsApp Cloud API
(or Twilio), Python/FastAPI, scikit-learn, Metabase, Docker Compose, Pest

## Run locally

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed   # seeds demo business + 6 months of history
docker compose exec app npm install && npm run dev
```

Open http://localhost:8000. Demo login is in `docs/demo-credentials.md`.

## Risk model

Logistic regression on: lead time, prior no-show count, day/time, service type,
deposit paid, reminder confirmed. Trained on [N] bookings. Evaluation:
precision/recall at the deposit threshold in [`risk-service/notebooks/eval.ipynb`](risk-service/notebooks/eval.ipynb).
Chosen over gradient boosting because the owner needs to understand why a
customer is flagged.

## Design decisions

See [`docs/adr/`](docs/adr/). Key ones:

- ADR-001: WhatsApp over SMS (customer behavior at this business)
- ADR-002: Deposit prompt instead of blocking high-risk bookings
- ADR-003: Offline-tolerant PWA for the owner's phone

## Failure modes handled

- Provider outage: reminders retried with backoff, owner alerted after 3 failures
- Duplicate webhooks: idempotency keys on every inbound message
- Double booking: DB-level exclusion constraint, not just app validation
- Customer opt-out: honored across all channels

## Testing

```bash
docker compose exec app php artisan test
docker compose exec risk pytest
```

## Roadmap / what I'd do next

- [ ] ...

## License

MIT
