# SlotSaver: Product & Architectural Decisions from Discovery

This document details the core product and engineering decisions that were directly driven by empirical findings during field discovery interviews with salon and clinic owners.

---

## Decision 1: WhatsApp Cloud API as Primary Channel (over SMS & Email)

### Discovery Insight
- In Southern and Western Europe (Portugal, Spain, Italy), SMS has evolved into a channel dominated by delivery notices and carrier spam. Open rates are low, and SMS click-through rates for appointment confirmations hovered under **14%**.
- Conversely, **94% of clients** actively use WhatsApp throughout the day.
- Telephone confirmation calls by receptionists consumed **14+ hours per week** with a dismal 35% live connection rate.

### Engineering Decision
- Implement Meta WhatsApp Cloud API as the Tier-1 communication channel.
- Implement interactive native buttons (`[Confirm Appointment]`, `[Reschedule]`, `[Cancel]`) with webhook payloads.
- Retain Twilio SMS strictly as a secondary fallback if WhatsApp message delivery encounters consecutive timeouts or outages.

---

## Decision 2: Selective ML Risk Scoring vs. Blanket Deposits

### Discovery Insight
- Requiring a blanket €15-€25 deposit on all appointments decreased overall booking conversion by **38%**, alienating regular customers who had frequented the business for years.
- However, with 0 deposits, first-time bookers scheduling prime Friday/Saturday slots 4+ days in advance exhibited a **34% no-show probability**.

### Engineering Decision
- Develop a Python microservice exposing a Logistic Regression model ($P_{\text{no-show}}$) scoring:
  - Lead time hours
  - Day of week (Friday/Saturday premium)
  - Time of day
  - Service ticket value
  - Client historical attendance and cancellation velocity
- Enforce refundable hold deposits **only** when predicted risk $P \ge 0.65$.
- Implement an explicit **VIP Loyalty Waiver**: Clients with $\ge 5$ completed visits and 0 historical no-shows are guaranteed 0 deposit requirements regardless of slot demand.

---

## Decision 3: 15-Minute Cascading Queue vs. Broadcast Blasts

### Discovery Insight
- When a slot opens up, sending a simultaneous broadcast text message to 20 waitlisted people creates client frustration: 19 clients click the link only to see "Sorry, already taken by someone else 3 seconds ago."
- Broadcast blasts feel spammy and diminish brand prestige for high-end boutique businesses.

### Engineering Decision
- Implement a serialized **Cascade Engine**:
  - The freed slot is locked and offered exclusively to candidate #1 on the waitlist.
  - An atomic claim token with an exact **15-minute expiration timestamp** (`offer_expires_at`) is sent via WhatsApp.
  - If candidate #1 claims it, the slot is confirmed immediately.
  - If the 15 minutes expire without claim, the job marks the entry expired and immediately cascades the offer to candidate #2.

---

## Decision 4: PostgreSQL Engine-Level Exclusion Constraints

### Discovery Insight
- High-volume booking platforms using application-level `whereNotExists` checks suffer from race conditions when two clients attempt to book the final 11:00 AM slot within 100 milliseconds of each other.
- Double bookings cause extreme customer dissatisfaction and staff embarrassment.

### Engineering Decision
- Install PostgreSQL `btree_gist` extension.
- Apply database-level exclusion constraint:
  ```sql
  CONSTRAINT no_overlapping_staff_bookings 
  EXCLUDE USING gist (
      employee_user_id WITH =,
      tstzrange(start_at, end_at) WITH &&
  ) WHERE (status NOT IN ('cancelled', 'no_show'));
  ```
- Any concurrent transaction attempting an overlapping booking is rejected with SQLSTATE `23P01` (`exclusion_violation`), which the domain action catches and translates to a polite customer-facing retry message.

---

## Decision 5: Offline-First Staff Tablet Check-In

### Discovery Insight
- Barbers and dental assistants frequently use iPads or tablets with spotty Wi-Fi in backrooms or basements.
- Staff refusing to wait for network spinners would skip marking client attendance, corrupting historical data.

### Engineering Decision
- Implement a Progressive Web App (PWA) with a Service Worker caching the application shell and schedule.
- Implement an **IndexedDB Action Queue** for staff quick actions:
  - When offline, "Check In" and "Mark No-Show" actions are stored locally in IndexedDB with cryptographic IDs and timestamps.
  - The client provides instant visual feedback.
  - When `navigator.onLine` fires, the queue automatically posts to `/api/offline/sync`, replaying actions idempotently to the server.
