# SlotSaver: Implementation Checklist & Live Progress Tracker

> **Document Type:** Live Execution Tracker  
> **Master Reference:** [`SLOTSAVER_MASTER_PLAN.md`](file:///home/creed47/Desktop/DemoProject/slotsaver/SLOTSAVER_MASTER_PLAN.md)  
> **Repository Root:** `/home/creed47/Desktop/DemoProject/slotsaver`  
> **Last Updated:** 2026-10-06  
> **Overall Progress:** 100.0% Completed (48/48 Tasks)

---

## Progress Dashboard

```
Total Tasks: 48 | Completed: 48 | In Progress: 0 | Pending: 0
[==================================================] 100.0%
```

|    Phase    | Milestone Name                                | Total Tasks | Completed |    Status    |
| :---------: | :-------------------------------------------- | :---------: | :-------: | :----------: |
| **Phase 1** | Database Foundation & Concurrency             |      6      |     6     | ✅ Completed |
| **Phase 2** | Domain Architecture & Booking Actions         |      6      |     6     | ✅ Completed |
| **Phase 3** | Multi-Channel Reminders & Idempotent Webhooks |      7      |     7     | ✅ Completed |
| **Phase 4** | Instant Waitlist Auto-Fill Engine             |      6      |     6     | ✅ Completed |
| **Phase 5** | Python Risk Microservice & Feature Exporter   |      7      |     7     | ✅ Completed |
| **Phase 6** | Frontend PWA & Owner KPI Dashboard            |      7      |     7     | ✅ Completed |
| **Phase 7** | Metabase Analytics & 6-Month Demo Seeder      |      4      |     4     | ✅ Completed |
| **Phase 8** | Documentation, Docker & CI/CD Pipeline        |      5      |     5     | ✅ Completed |

---

## Status Legend

- `[ ]` **Pending:** Not yet started.
- `[/]` **In Progress:** Currently being implemented or tested.
- `[x]` **Completed:** Implemented, verified, and passes test suites.
- `[!]` **Blocked / Attention:** Requires resolution or external configuration.

---

## Phase 1: Database Foundation & Concurrency

> **Goal:** Guarantee zero double-bookings via PostgreSQL exclusion constraints and introduce schemas for waitlist, reminders, and webhook idempotency.

- [x] **T1.1: Enable PostgreSQL `btree_gist` extension**
    - **Files:** `database/migrations/2026_10_06_000100_enable_btree_gist_extension.php`
    - **Details:** Add migration executing `CREATE EXTENSION IF NOT EXISTS btree_gist;` in PostgreSQL. Include safe conditional check for SQLite test environments.
    - **Verification:** Migration executed cleanly on PostgreSQL via `php artisan migrate`.

- [x] **T1.2: Add PostgreSQL exclusion constraint to `bookings`**
    - **Files:** `database/migrations/2026_10_06_000200_add_exclusion_constraints_to_bookings_table.php`
    - **Details:** Add `EXCLUDE USING gist (employee_user_id WITH =, tstzrange(start_at, end_at, '[)') WITH &&) WHERE (status IN ('pending', 'confirmed') AND employee_user_id IS NOT NULL)`.
    - **Verification:** Live test confirmed simultaneous overlapping bookings trigger PostgreSQL `23P01` exclusion constraint (`no_overlapping_staff_bookings`).

- [x] **T1.3: Create `waitlist_entries` table migration**
    - **Files:** `database/migrations/2026_10_06_000300_create_waitlist_entries_table.php`, `app/Models/WaitlistEntry.php`
    - **Details:** Create table with `business_id`, `client_user_id`, `service_id`, `preferred_employee_id`, `preferred_date`, `preferred_time_from`, `preferred_time_to`, `status`, `claim_token`, `offered_at`, `offer_expires_at`, `freed_booking_id`.
    - **Verification:** Migration executed cleanly; Eloquent model created with scopes (`waiting`, `expiredOffers`) and `isClaimable()` method.

- [x] **T1.4: Create `reminders` table migration**
    - **Files:** `database/migrations/2026_10_06_000400_create_reminders_table.php`, `app/Models/Reminder.php`
    - **Details:** Create table with `booking_id`, `channel` (`whatsapp`, `sms`), `type` (`48h`, `24h`, `2h`), `scheduled_for`, `sent_at`, `provider_message_id`, `delivery_status`, `interactive_action`, `retry_count`, `error_details`.
    - **Verification:** Migration executed cleanly; Eloquent model created with `due()` and `successful()` scopes.

- [x] **T1.5: Create `webhook_events` (Idempotency Ledger) migration**
    - **Files:** `database/migrations/2026_10_06_000500_create_webhook_events_table.php`, `app/Models/WebhookEvent.php`
    - **Details:** Create table with `provider`, `idempotency_key` (unique), `event_type`, `payload`, `processed_at`, `response_status`.
    - **Verification:** Migration executed cleanly; `WebhookEvent::isDuplicate()` method and unique database index verified by test.

- [x] **T1.6: Add risk score and deposit tracking fields to `bookings`**
    - **Files:** `database/migrations/2026_10_06_000600_add_risk_and_deposit_columns_to_bookings_table.php`, `app/Models/Booking.php`
    - **Details:** Added columns: `buffer_minutes`, `deposit_amount`, `deposit_status` (`none`, `pending`, `paid`, `forfeited`, `refunded`), `deposit_paid_at`, `risk_score`, `risk_tier` (`low`, `medium`, `high`).
    - **Verification:** Migration executed; `Booking` fillable attributes and casts updated; verified in `ExclusionConstraintTest`.

---

## Phase 2: Domain Architecture & Core Booking Actions

> **Goal:** Restructure business logic into clean, domain-driven modules and implement core booking actions with race-condition resilience.

- [x] **T2.1: Fix routing syntax error in `routes/admin.php`**
    - **Files:** `routes/admin.php`
    - **Details:** Replaced `Route::inertia()` with `Route::get()` for `admin.businesses.index` and `admin.businesses.create`.
    - **Verification:** Verified via `php artisan route:list --path=admin`.

- [x] **T2.2: Establish `app/Domain/Booking/` structure & Eloquent models**
    - **Files:** `app/Domain/Booking/Data/BookingData.php`, `app/Domain/Booking/Exceptions/SlotAlreadyBookedException.php`, `app/Domain/Booking/Events/BookingCancelledEvent.php`
    - **Details:** Created typed DTO `BookingData`, custom exception `SlotAlreadyBookedException`, and domain event `BookingCancelledEvent`.
    - **Verification:** Models and domain classes load cleanly with proper type-hinting.

- [x] **T2.3: Implement `CreateBooking` Action with exclusion violation handling**
    - **Files:** `app/Domain/Booking/Actions/CreateBooking.php`
    - **Details:** Availability verification, end time and buffer calculation, reference code generation, transactional insert catching SQLSTATE `23P01` to rethrow `SlotAlreadyBookedException`, and automatic reminder scheduling.
    - **Verification:** Unit and feature tests verified in `BookingConcurrencyTest`.

- [x] **T2.4: Implement `CancelBooking` Action with waitlist trigger**
    - **Files:** `app/Domain/Booking/Actions/CancelBooking.php`
    - **Details:** Calculates cancellation fee due, cancels pending reminders, logs history entry, updates status, and dispatches `BookingCancelledEvent`.
    - **Verification:** Verified with `BookingCancelledEvent` event fakes in `BookingConcurrencyTest`.

- [x] **T2.5: Implement `RescheduleBooking` and `MarkNoShow` Actions**
    - **Files:** `app/Domain/Booking/Actions/RescheduleBooking.php`, `app/Domain/Booking/Actions/MarkNoShow.php`
    - **Details:** Supported slot shift with conflict detection and reminder rescheduling; implemented no-show status update with deposit forfeiture.
    - **Verification:** Tested in `BookingConcurrencyTest`.

- [x] **T2.6: Write Domain Feature Test Suite (`BookingConcurrencyTest`)**
    - **Files:** `tests/Feature/BookingConcurrencyTest.php`
    - **Details:** Comprehensive test suite testing creation, overlapping slot prevention, cancellation, rescheduling, and no-show deposit forfeiture.
    - **Verification:** All 5 tests passed cleanly with 23 assertions.

---

## Phase 3: Multi-Channel Reminders & Idempotent Webhooks

> **Goal:** Automate 48h, 24h, and 2h WhatsApp reminders with interactive buttons, Twilio SMS fallback, backoff retry queues, and replay-proof webhook handling.

- [x] **T3.1: Create notification channels (`WhatsAppChannel` & `SmsChannel`)**
    - **Files:** `app/Domain/Reminders/Channels/ReminderChannelInterface.php`, `app/Domain/Reminders/Channels/WhatsAppChannel.php`, `app/Domain/Reminders/Channels/SmsChannel.php`
    - **Details:** Implemented WhatsApp Cloud API channel with interactive buttons and Twilio SMS fallback channel.
    - **Verification:** Tested in `ReminderPipelineTest`.

- [x] **T3.2: Implement `SendReminderJob` with exponential backoff**
    - **Files:** `app/Domain/Reminders/Jobs/SendReminderJob.php`
    - **Details:** Queueable job with `tries = 3`, `backoff = [60, 300, 900]`. Dispatches via WhatsApp with automatic SMS fallback upon repeated outages.
    - **Verification:** Verified via `ReminderPipelineTest::test_send_reminder_job_executes_and_marks_reminder_sent`.

- [x] **T3.3: Build reminder dispatch scheduler command**
    - **Files:** `app/Console/Commands/DispatchUpcomingRemindersCommand.php`, `routes/console.php`
    - **Details:** Scans upcoming confirmed/pending appointments due for 48h, 24h, or 2h reminders and dispatches jobs. Scheduled every 5 minutes.
    - **Verification:** Verified via `ReminderPipelineTest::test_reminder_scheduler_command_dispatches_due_reminders`.

- [x] **T3.4: Implement `WhatsAppWebhookController` with HMAC verification**
    - **Files:** `app/Http/Controllers/Webhooks/WhatsAppWebhookController.php`, `routes/web.php`, `bootstrap/app.php`
    - **Details:** Webhook verification challenge (`hub.challenge`), CSRF exemption, and HMAC signature check.
    - **Verification:** Verified via `ReminderPipelineTest::test_whatsapp_webhook_verification_handshake`.

- [x] **T3.5: Implement strict webhook idempotency via `webhook_events`**
    - **Files:** `app/Http/Controllers/Webhooks/WhatsAppWebhookController.php`
    - **Details:** Enforces atomic unique idempotency key check. Subsequent duplicate webhooks return HTTP 200 with `ignored_duplicate`.
    - **Verification:** Verified via `ReminderPipelineTest::test_webhook_idempotency_ignores_duplicate_deliveries`.

- [x] **T3.6: Handle interactive reply actions (`Confirm`, `Reschedule`, `Cancel`)**
    - **Files:** `app/Domain/Reminders/Actions/HandleInteractiveButtonReply.php`
    - **Details:** Interactive button clicks automatically confirm bookings or execute `CancelBooking` (which in turn triggers waitlist refill).
    - **Verification:** Verified in `ReminderPipelineTest`.

- [x] **T3.7: Write reminder & webhook test suite**
    - **Files:** `tests/Feature/ReminderPipelineTest.php`
    - **Details:** Full test suite covering scheduling, sending, webhook challenges, button clicks, and duplicate replay deduplication.
    - **Verification:** All 6 tests in `ReminderPipelineTest` passed with 30 assertions.

---

## Phase 4: Instant Waitlist Auto-Fill Engine

> **Goal:** Re-monetize cancelled slots by instantly offering them to waitlisted clients with a 15-minute countdown and automatic cascading.

- [x] **T4.1: Create Waitlist Domain Actions (`JoinWaitlist`, `OfferFreedSlot`, `ExpireOffer`)**
    - **Files:** `app/Domain/Waitlist/Actions/JoinWaitlist.php`, `app/Domain/Waitlist/Actions/OfferFreedSlot.php`, `app/Domain/Waitlist/Jobs/ExpireOfferJob.php`
    - **Details:** Implemented customer waitlist registration, slot matching, secure claim token generation, and 15-minute hold dispatch.
    - **Verification:** Verified in `WaitlistAutoFillTest`.

- [x] **T4.2: Implement `ExpireOffer` job with automatic cascading**
    - **Files:** `app/Domain/Waitlist/Jobs/ExpireOfferJob.php`
    - **Details:** Delays 15 minutes; if unclaimed upon expiration, marks entry expired and automatically re-offers the slot to candidate #2.
    - **Verification:** Verified via `WaitlistAutoFillTest::test_expired_offer_cascades_to_second_waitlist_candidate`.

- [x] **T4.3: Implement `ClaimFreedSlot` Action & Controller**
    - **Files:** `app/Domain/Waitlist/Actions/ClaimFreedSlot.php`, `app/Http/Controllers/WaitlistController.php`
    - **Details:** Endpoint `GET /waitlist/claim/{token}` and `POST /waitlist/claim/{token}`. Validates active countdown window, locks slot, and creates confirmed booking.
    - **Verification:** Verified via `WaitlistAutoFillTest::test_candidate_can_claim_freed_slot_and_receive_confirmed_booking`.

- [x] **T4.4: Add public waitlist join API**
    - **Files:** `app/Http/Controllers/WaitlistController.php`, `routes/web.php`
    - **Details:** Public endpoint `POST /api/waitlist/join` enabling customers to register on waitlist.
    - **Verification:** Verified via `WaitlistAutoFillTest::test_public_api_can_join_waitlist`.

- [x] **T4.5: Add waitlist expiry cron scheduler**
    - **Files:** `app/Console/Commands/SweepExpiredWaitlistOffersCommand.php`, `routes/console.php`
    - **Details:** Scheduled command `waitlist:sweep-expired` running every minute to catch and cascade any unhandled expired holds.
    - **Verification:** Artisan command registered and scheduled.

- [x] **T4.6: Write Waitlist Test Suite**
    - **Files:** `tests/Feature/WaitlistAutoFillTest.php`
    - **Details:** Comprehensive feature test covering automatic cancellation refill, claiming with confirmed booking generation, cascading expiry, and public join API.
    - **Verification:** All 4 tests in `WaitlistAutoFillTest` passed with 27 assertions.

---

## Phase 5: Python Risk Microservice & Feature Engineering

> **Goal:** Deploy an explainable Logistic Regression microservice (FastAPI) that scores no-show risk and triggers dynamic deposit prompts.

- [x] **T5.1: Scaffold `risk-service/` structure and Python dependencies**
    - **Files:** `risk-service/pyproject.toml`, `risk-service/requirements.txt`, `risk-service/Dockerfile`
    - **Details:** Setup `FastAPI`, `uvicorn`, `scikit-learn`, `pandas`, `numpy`, `joblib`, `pytest`, `httpx` using `uv`.
    - **Verification:** Environment operational, all 5 tests passed in `risk-service/tests/`.

- [x] **T5.2: Implement feature extraction & encoding pipeline**
    - **Files:** `risk-service/app/features.py`
    - **Details:** Implemented Pydantic model `BookingFeatures` with 9 standardized feature columns and ratio normalization.
    - **Verification:** Verified via `test_features.py`.

- [x] **T5.3: Build model training pipeline (`train.py`)**
    - **Files:** `risk-service/model/train.py`, `risk-service/model/model.joblib`
    - **Details:** Trained Logistic Regression pipeline on 10,000 appointment samples with balanced class weights, achieving 0.8359 ROC-AUC.
    - **Verification:** Serialized model saved to `model.joblib` with coefficient report.

- [x] **T5.4: Create evaluation notebook (`eval.ipynb`)**
    - **Files:** `risk-service/notebooks/eval.ipynb`
    - **Details:** Documented Precision-Recall metrics, ROC curves, threshold calibration ($P \ge 0.60$), and explainability trade-offs.
    - **Verification:** Notebook created with complete metric evaluations.

- [x] **T5.5: Build FastAPI server (`POST /score` & `GET /health`)**
    - **Files:** `risk-service/app/main.py`, `risk-service/app/config.py`
    - **Details:** Implemented sub-10ms endpoints with Pydantic validation, feature factor explanations, and dynamic deposit calculations.
    - **Verification:** Verified via `test_api.py`.

- [x] **T5.6: Implement Laravel `RiskScoreClient` & `DepositPolicy`**
    - **Files:** `app/Domain/Risk/RiskScoreClient.php`, `app/Domain/Risk/DepositPolicy.php`, `config/services.php`
    - **Details:** Implemented Guzzle client with 500ms timeout and heuristic fallback; implemented deposit rules with VIP customer waivers.
    - **Verification:** Verified in `RiskServiceIntegrationTest`.

- [x] **T5.7: Implement `ExportRiskFeatures` console command**
    - **Files:** `app/Console/Commands/ExportRiskFeatures.php`
    - **Details:** Artisan command `risk:export-features` exporting historical appointments and features to CSV for retraining.
    - **Verification:** Tested in `RiskServiceIntegrationTest::test_export_risk_features_command_generates_csv`.

---

## Phase 6: Frontend PWA & Owner KPI Dashboard

> **Goal:** Create an ultra-premium booking PWA with dynamic buffer slots, deposit prompts, an executive owner dashboard with reminder funnels, and offline sync.

- [x] **T6.1: Build client public booking flow**
    - **Files:** `resources/js/pages/booking/index.tsx`, `resources/js/pages/booking/confirmation.tsx`, `app/Http/Controllers/BookingController.php`
    - **Details:** Multi-step responsive booking with dynamic 10-minute buffer slot calculations, specialist/staff filter, date picker shortcuts, WhatsApp reminder preview, and Google/iCal calendar download buttons.
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_public_booking_page_renders_with_business_details` and `test_available_slots_endpoint_calculates_slots_with_buffer_time`.

- [x] **T6.2: Implement dynamic deposit prompt modal**
    - **Files:** `resources/js/components/booking/deposit-prompt-modal.tsx`, `app/Http/Controllers/BookingController.php`
    - **Details:** Interactive modal popping up when predicted risk score $\ge 0.65$; transparent rationale with 100% refund guarantee if cancelled 24h prior; simulated 256-bit Stripe checkout card element.
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_booking_creation_enforces_deposit_when_required` and `test_booking_creation_succeeds_when_deposit_confirmed`.

- [x] **T6.3: Build waitlist join modal & slot claim screen**
    - **Files:** `resources/js/components/booking/waitlist-join-modal.tsx`, `resources/js/pages/waitlist/claim.tsx`, `app/Http/Controllers/WaitlistController.php`
    - **Details:** Modal capturing waitlist registrations on fully booked dates; dedicated claim screen displaying live 15-minute ticking countdown timer with auto-progress bar and one-tap "Claim My Appointment" action.
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_waitlist_claim_page_renders_with_countdown`.

- [x] **T6.4: Redesign owner KPI dashboard**
    - **Files:** `resources/js/pages/dashboard.tsx`, `resources/js/components/dashboard/kpi-cards.tsx`, `app/Http/Controllers/DashboardController.php`
    - **Details:** High-impact dark-mode dashboard displaying 4 primary KPI cards: No-Show Rate (baseline 22% vs current 4.8%, -78% drop), Recovered Revenue ($4,850/mo), Refilled Slots (18 saved), and Staff Time Saved (26.5 hrs/mo).
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_owner_dashboard_returns_kpis_and_ledger`.

- [x] **T6.5: Implement Reminder Funnel visualizer**
    - **Files:** `resources/js/components/dashboard/reminder-funnel.tsx`
    - **Details:** Multi-stage funnel visualization (Sent 280 $\rightarrow$ Delivered 275 $\rightarrow$ Read 257 $\rightarrow$ Confirmed 219 $\rightarrow$ Rescheduled 33 $\rightarrow$ Cancelled 21 $\rightarrow$ No-Show 7) and ML risk cohort distribution (Low 72%, Medium 21%, High 7%).
    - **Verification:** Fully integrated into `dashboard.tsx` with smooth gradients and tooltips.

- [x] **T6.6: Implement real-time appointment ledger & calendar drawer**
    - **Files:** `resources/js/components/dashboard/appointment-ledger.tsx`, `resources/js/components/dashboard/waitlist-drawer.tsx`
    - **Details:** Filterable table by date, staff, and status; risk badges, deposit badges, and inline staff quick actions: Check In / Complete, Mark No-Show (with deposit forfeiture), and Trigger WhatsApp Reminder Now; slide-over waitlist cascade queue drawer.
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_dashboard_staff_quick_actions`.

- [x] **T6.7: Implement Service Worker & IndexedDB offline queue**
    - **Files:** `public/sw.js`, `public/manifest.json`, `resources/js/sw.js`, `resources/js/lib/offline/db.ts`, `resources/js/lib/offline/sync.ts`, `app/Http/Controllers/OfflineSyncController.php`
    - **Details:** PWA service worker with Cache-First static assets and Network-First schedule API caching; IndexedDB offline queue capturing actions during network drops and automatically syncing to `POST /api/offline/sync` on reconnection.
    - **Verification:** Verified via `FrontendPwaAndDashboardTest::test_offline_sync_endpoint_replays_queued_actions`.

---

## Phase 7: Metabase Analytics & 6-Month Demo Seeder

> **Goal:** Seed realistic 6-month longitudinal business data showing before/after no-show reduction, and configure Metabase BI dashboards.

- [x] **T7.1: Build realistic 26-week longitudinal `DemoBusinessSeeder`**
    - **Files:** `database/seeders/DemoBusinessSeeder.php`
    - **Details:** Seeded "Crown & Blade Barbershop" (Lisbon). Generated 6 months of historical bookings:
        - _Weeks 1–4:_ Baseline manual period (22% no-show rate, no reminders, 0 deposits).
        - _Weeks 5–8:_ 24h WhatsApp reminder intro (12% no-show rate).
        - _Weeks 9–26:_ Full SlotSaver suite (48h/24h/2h, risk deposits, waitlist refills, sustained 4.5% no-show rate).
    - **Verification:** Verified via `AnalyticsMetricsTest::test_demo_business_seeder_generates_longitudinal_dataset` and `test_no_show_rate_drops_longitudinally_from_baseline_to_slotsaver`.

- [x] **T7.2: Build analytics export definition (`export.json`)**
    - **Files:** `analytics/metabase-dashboards/export.json`
    - **Details:** Exported dashboard schemas containing queries for No-Show Moving Average, Reminder Response Times, Revenue Recovery by Service, and Waitlist Claim Velocity.
    - **Verification:** JSON is structurally valid and matches Metabase import format.

- [x] **T7.3: Configure PostgreSQL read-replica container in Docker Compose**
    - **Files:** `docker/postgres/postgresql.conf`, `docker/postgres/primary-init.sh`, `docker/postgres/replica-init.sh`
    - **Details:** Configured PostgreSQL streaming replication with physical replication slots (`slotsaver_standby_slot`), WAL streaming, and hot standby mode for isolated BI workloads.
    - **Verification:** Configuration scripts created and verified.

- [x] **T7.4: Add analytics verification test**
    - **Files:** `tests/Feature/AnalyticsMetricsTest.php`
    - **Details:** Tests verifying that calculated dashboard metrics (no-show rate, recovered revenue, funnel stages) match mathematical expectations against seeded data.
    - **Verification:** All 3 tests passed with 31 assertions.

---

## Phase 8: Documentation, Docker Environment & CI/CD Pipeline

> **Goal:** Package the entire repository with production-ready Docker Compose, automated CI pipeline, complete discovery notes, ADRs, runbook, and recruiter case study.

- [x] **T8.1: Create complete discovery documentation**
    - **Files:** `docs/discovery/interview-notes.md`, `docs/discovery/current-process-map.md`, `docs/discovery/decisions-from-discovery.md`
    - **Details:** Documented realistic owner interview notes with Mateo Silva (Crown & Blade), before-and-after Mermaid workflow maps, and 5 key architectural decisions directly driven by customer discovery.
    - **Verification:** All discovery markdown documents formatted cleanly with GitHub alerts and Mermaid diagrams.

- [x] **T8.2: Create Architecture Decision Records (ADRs)**
    - **Files:** `docs/adr/001-whatsapp-over-sms.md`, `docs/adr/002-dynamic-deposit-prompt.md`, `docs/adr/003-offline-pwa-indexeddb.md`
    - **Details:** Formal architectural decision records documenting context, considered options, decision rationale, and operational consequences.
    - **Verification:** ADRs match exact production implementation.

- [x] **T8.3: Create operational runbook & credentials**
    - **Files:** `docs/runbook.md`, `docs/demo-credentials.md`, `docs/case-study.md`
    - **Details:** Operational runbook covering health checks, webhook audit, background schedulers, and ML model retraining. Demo credentials table for all 4 roles. Executive portfolio case study documenting ROI (€4,850/mo saved, -79% drop in no-shows).
    - **Verification:** Documents cross-referenced and verified.

- [x] **T8.4: Create production-ready `docker-compose.yml`**
    - **Files:** `docker-compose.yml`, `Dockerfile`, `docker/postgres/postgresql.conf`, `docker/postgres/primary-init.sh`, `docker/postgres/replica-init.sh`
    - **Details:** Multi-container production orchestration for `app` (PHP 8.4 dev), `queue-worker`, `postgres` (PostgreSQL 16 + btree_gist), `postgres-replica` (streaming read-replica), `redis` (7.2), `risk-service` (Python 3.12 FastAPI), and `metabase` (BI analytics).
    - **Verification:** Configuration validated; port mappings, health checks, and volume mounts aligned.

- [x] **T8.5: Create GitHub Actions CI workflow (`.github/workflows/ci.yml`)**
    - **Files:** `.github/workflows/ci.yml`
    - **Details:** Multi-job CI pipeline verifying backend tests (PHP 8.4 + PostgreSQL + Redis service containers), Python risk microservice tests (Pytest), and frontend build (`npm run types:check` + `npm run build`).
    - **Verification:** GitHub Actions workflow syntax verified against schema.

---

## How to Use & Update this Live Document

1. **Marking Progress:**
    - Change `[ ]` to `[/]` when work begins on a task.
    - Change `[/]` to `[x]` once code is implemented and verified with tests.
    - Update the **Progress Dashboard** metrics at the top of the file to reflect completed tasks.
2. **Recording Blockers:**
    - Change `[ ]` to `[!]` and add a sub-bullet explaining the blocking issue.
3. **Traceability:**
    - Link commits, PRs, or specific test results directly in task notes for auditability.
