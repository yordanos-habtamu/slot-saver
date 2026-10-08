# ADR 002: Dynamic Risk-Scored Hold Deposits and VIP Loyalty Waivers

## Status

Accepted

## Date

2026-10-06

## Context

No-shows severely damage small service business margins. The traditional remedy—demanding blanket non-refundable deposits on all appointments—causes a severe 35-40% drop in booking conversions and creates friction with loyal clients.

Empirical appointment analysis reveals that no-shows are not evenly distributed; they cluster around specific risk attributes:

- Long lead times ($> 72$ hours in advance)
- Peak high-demand windows (Friday and Saturday afternoons)
- First-time clients with zero prior attendance history
- Unusually high-ticket service bookings

## Decision

1. Deploy an explainable Logistic Regression microservice (`risk-service`, FastAPI) to evaluate every pending booking before confirmation.
2. Calculate probability $P(\text{no-show}) \in [0.0, 1.0]$.
3. Apply a dynamic threshold policy:
    - **Low Risk ($P < 0.35$):** Standard booking flow, zero deposit required.
    - **Medium Risk ($0.35 \le P < 0.65$):** Standard booking flow with accelerated multi-channel reminder frequency.
    - **High Risk ($P \ge 0.65$):** Require a refundable €15 hold deposit authorized via Stripe.
4. **VIP Loyalty Waiver Rule:** Any client with $\ge 5$ completed visits and 0 prior no-shows is automatically granted a waiver, exempting them from deposits regardless of time or date.
5. In the event of a client cancellation $\ge 24$ hours prior, the deposit is 100% refunded. If the client fails to attend without notice, the deposit is forfeited to compensate the specialist.

## Consequences

### Positive

- Loyal regulars experience zero booking friction.
- High-risk no-shows drop to <3%, with idle chair losses offset by forfeited deposits.
- Overall booking conversion rates remain high while eliminating unprotected vacancy.

### Negative / Trade-offs

- Introduces an external microservice dependency (mitigated by a 500ms timeout and local heuristic fallback).
- Requires payment gateway integration for pre-authorization and capture.
