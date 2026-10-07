# SlotSaver Discovery: User Interview Notes

**Date:** September 2026  
**Context:** Field research across Lisbon metropolitan local businesses (barbershops, dental clinics, pilates studios)  
**Lead Interviewee:** Mateo Silva, Owner & Master Barber at *Crown & Blade Barbershop* (Chiado, Lisbon)  
**Additional Interviewees:** Sofia Nunes (Front Desk Manager), Carlos Gomes (Frequent Client)

---

## 1. Executive Summary of Discovery

Local appointment-based service businesses face an existential margin bleed caused by **unnotified client no-shows** and **last-minute cancellations**. 
- Crown & Blade booked **160 appointments/week** across 3 chairs.
- Average appointment ticket: **€35.00 – €55.00**.
- Baseline no-show rate: **21.8% (~35 missed appointments per week)**.
- Weekly lost revenue: **€1,225 – €1,925/week (~€5,000 – €7,700/month)**.

---

## 2. Deep Dive: Key Pain Points

### Pain Point 1: The "Invisible No-Show" Phenomenon
> *"Clients don't intentionally malicious skip; they book 5 days ahead, forget about it on Thursday afternoon, and feel too guilty to call once they realize they are 20 minutes late. So they ghost us completely."* — Mateo Silva

- Traditional SMS reminders are ignored or flagged as spam in Portugal (94%+ smartphone users use WhatsApp daily).
- Phone calls from reception are disruptive, labor-intensive (Sofia spent **2.5 hours every day** calling tomorrow's clients), and 60% went directly to voicemail.

### Pain Point 2: Empty Chair Panic & Unfilled Cancellations
> *"When a client cancels 2 hours before, that slot almost never gets rebooked. Our staff is standing idle waiting for walk-ins, while 10 clients on our Instagram DMs were asking if we had an opening that day."* — Sofia Nunes

- When a client did cancel responsibly, the shop had **no real-time mechanism** to offer that freed slot to clients who previously wanted that day.
- Instagram DMs and manual text message chains were too slow to fill same-day slots.

### Pain Point 3: The Blanket Deposit Dilemma
> *"If I demand a €20 upfront deposit on every single haircut, my regular VIPs get offended, and booking conversions drop by 40%. But if I require zero deposits, first-time Saturday afternoon bookers skip with zero consequence."* — Mateo Silva

- Blanket deposit policies punish loyal customers.
- Businesses need **selective, risk-based deposits** that only trigger when an appointment has a high statistical probability of abandonment (e.g. first-time clients booking peak Friday/Saturday slots 4+ days in advance).

### Pain Point 4: Spotty Internet & Front Desk Velocity
- During peak morning rushes, the shop's Wi-Fi occasionally drops or slows down.
- Barbers need to tap "Client Arrived" or "No-Show" on their iPad without staring at an endless spinning loading indicator.

---

## 3. Key Persona Archetypes

| Persona | Role | Core Goal | Frustration |
| :--- | :--- | :--- | :--- |
| **Mateo (Owner)** | Business Leader | Maximize chair utilization and revenue predictability | Losing €5,000+/mo to empty chairs and paying idle staff |
| **Sofia (Reception)** | Operations Lead | Smooth calendar, minimal manual phone calls | Spending 14+ hours/week calling clients and managing fragmented waitlists |
| **Carlos (Client)** | Busy Professional | Frictionless booking, reminders in his preferred app | Needing to make phone calls to cancel or reschedule |

---

## 4. Key Takeaways for SlotSaver Architecture

1. **WhatsApp Native Over SMS:** Interactive buttons (`[Confirm Appointment]`, `[Reschedule]`) directly in WhatsApp achieve >90% read and response rates within 15 minutes.
2. **Selective Risk Scoring:** Logistic Regression model scores appointments dynamically ($P_{\text{no-show}}$). Deposits are only prompted when $P \ge 0.65$, and VIP regulars ($\ge 5$ completed visits, 0 no-shows) are always exempt.
3. **Instant Waitlist Cascade (The 15-Minute Rule):** When a cancellation occurs, the system automatically messages the #1 waitlist candidate with an exclusive 15-minute claim hold. If unclaimed, it immediately cascades to candidate #2.
4. **Offline Resilience:** PWA service worker with IndexedDB action queue allows staff to check in clients offline, replaying actions automatically upon reconnection.
