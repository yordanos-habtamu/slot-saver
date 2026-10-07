# SlotSaver: Demo Credentials & Exploration Guide

Use these credentials and endpoints to test all roles and flows across the SlotSaver platform.

---

## 1. User Credentials

All demo user accounts are pre-seeded with the password `password`.

| Role | Name | Email | Password | Primary Interface |
| :--- | :--- | :--- | :--- | :--- |
| **Business Owner** | Mateo Silva | `mateo@crownblade.test` | `password` | `/dashboard` (Owner KPI Dashboard) |
| **Business Owner** | Nadia Ferreira | `owner@aurorahair.test` | `password` | `/dashboard` (Aurora Hair Studio) |
| **Platform Admin** | Platform Admin | `admin@slotsaver.test` | `password` | `/admin/businesses` (Platform Admin) |
| **Staff Specialist** | André Rocha | `andre@crownblade.test` | `password` | `/dashboard` (Shared Team Dashboard) |
| **Regular Client** | Carlos Gomes | `client1@example.com` | `password` | `/book` (Booking Flow) |

---

## 2. Interactive URLs & Endpoints

| Environment | Route | Description |
| :--- | :--- | :--- |
| **Public PWA Booking** | [`/book`](http://localhost:8000/book) | Client booking flow — browse services and book without an account (dynamic 10-min buffers and deposit triggers). |
| **Crown & Blade Booking** | [`/book/crown-and-blade`](http://localhost:8000/book/crown-and-blade) | Direct booking landing page for Chiado flagship barbershop. |
| **Owner KPI Dashboard** | [`/dashboard`](http://localhost:8000/dashboard) | Executive command center with No-Show reduction KPIs, Reminder Funnel, and Live Ledger. |
| **Waitlist Claim Screen** | `/waitlist/claim/{token}` | Real-time 15-minute countdown claim portal for freed slots. |
| **Risk Microservice API** | `http://127.0.0.1:8001/docs` | Interactive Swagger OpenAPI documentation for Python risk service. |
| **WhatsApp Webhook** | `/webhooks/whatsapp` | Inbound challenge and interactive button callback endpoint. |

---

## 3. Recommended Demo Exploration Walkthrough

1. **Step 1: Inspect Owner Dashboard (`/dashboard`)**
   - Log in as `mateo@crownblade.test` (`password`).
   - Observe the 4 KPI cards demonstrating the **22% $\rightarrow$ 4.5%** no-show reduction.
   - Inspect the **WhatsApp Reminder Funnel** and **Risk Distribution** charts.
   - Check out the **Live Appointment Ledger** and test quick actions (*Check In*, *Mark No-Show*, *Trigger WhatsApp Reminder*).
   - Open the **Waitlist Queue Drawer** to see active candidates and 15-minute holds.

2. **Step 2: Experience Booking Flow (`/book`)**
   - Browse services (e.g. *Royal Treatment*), select a specialist, and choose tomorrow's date — no account needed.
   - Observe real-time slot generation taking the 10-minute sanitation buffer into account.
   - Select a high-demand slot to see the **Refundable Deposit Modal** appear dynamically.
   - Confirm booking to view the **Confirmation Page** with calendar sync (`.ics` / Google Calendar) and WhatsApp timeline preview.

3. **Step 3: Test Waitlist Auto-Refill**
   - Open a booking and cancel it.
   - Observe the system dispatching `TriggerWaitlistRefillOnCancellation`, generating a secure claim token, and locking a 15-minute hold for waitlist candidate #1.
