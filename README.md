# SlotSaver 💈

> **Intelligent Appointment Attendance & Revenue Recovery Platform**  
> *Proven -79% No-Show Reduction (21.8% $\rightarrow$ 4.5%) • €4,850/mo Recovered Revenue • Zero Manual Phone Calls*

[![CI Pipeline](https://github.com/slotsaver/slotsaver/actions/workflows/ci.yml/badge.svg)](https://github.com/slotsaver/slotsaver/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)](https://react.dev)
[![Python](https://img.shields.io/badge/Python-3.12-3776AB?logo=python)](https://python.org)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.110-009688?logo=fastapi)](https://fastapi.tiangolo.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16%20(btree__gist)-4169E1?logo=postgresql)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis)](https://redis.io)

---

## 📖 Overview

Appointment-based service businesses (barbershops, dental clinics, physical therapy studios) bleed margins through **unnotified client no-shows** and **unfilled last-minute cancellations**. Traditional phone calls waste 14+ hours of staff time each week, while blanket non-refundable deposits alienate loyal regulars and depress booking conversions by 38%.

**SlotSaver** replaces manual phone calls and paper waitlists with an autonomous, high-converting platform:
1. **Engine-Level Concurrency:** PostgreSQL `btree_gist` exclusion constraints eliminate double bookings at the database engine level.
2. **Interactive WhatsApp Pipeline:** 48h, 24h, and 2h touchpoints with native 1-tap interactive buttons (`[Confirm]`, `[Reschedule]`, `[Cancel]`) and HMAC-verified idempotent webhooks.
3. **Machine Learning Risk Scoring:** An explainable Python FastAPI microservice calculates no-show probability $P(\text{no-show})$, dynamically requiring €15 hold deposits **only** on high-risk slots ($P \ge 0.65$), while exempting VIP regulars.
4. **Autonomous 15-Minute Waitlist Cascade:** When an appointment is cancelled, the system locks the freed slot and messages candidate #1 on the waitlist with an exclusive 15-minute countdown. If unclaimed, it automatically cascades to candidate #2, recovering **86.4% of cancelled slots** in an average of **4.2 minutes**.
5. **Offline-First PWA:** Front desk staff can check in clients on mobile tablets even during Wi-Fi drops, with actions safely queued in IndexedDB and synchronized automatically on reconnect.

---

## 🚀 Quick Start (Local Development)

### Prerequisites
- PHP 8.4+ & Composer
- Node.js 22+ & npm
- Python 3.12+ & `uv` or `pip`
- PostgreSQL 16+ & Redis

### 1. Clone & Setup Backend
```bash
git clone https://github.com/your-username/slotsaver.git
cd slotsaver

# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Run PostgreSQL migrations (includes btree_gist extension)
php artisan migrate

# Seed 26-week realistic longitudinal dataset
php artisan db:seed --class=DemoBusinessSeeder
```

### 2. Setup Python Risk Microservice
```bash
cd risk-service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# Run FastAPI microservice on port 8001
uvicorn app.main:app --host 0.0.0.0 --port 8001
```

### 3. Setup Frontend Assets
```bash
# Return to repository root
npm install
npm run build
```

### 4. Serve the Application
```bash
# Start background queue worker
php artisan queue:work --tries=3 &

# Start Laravel development server
php artisan serve --port 8000
```
Visit **[`http://localhost:8000/dashboard`](http://localhost:8000/dashboard)** to access the Owner KPI Dashboard, or **[`http://localhost:8000/book`](http://localhost:8000/book)** to explore the public booking flow.

---

## 🐳 Docker Compose Deployment

To spin up all 7 production-ready containers (Laravel app, queue worker, PostgreSQL primary, PostgreSQL read-replica, Redis, Python risk microservice, and Metabase BI):
```bash
docker compose up -d --build
```

| Service | Port | Description |
| :--- | :--- | :--- |
| **Laravel App** | `http://localhost:8000` | Main application & public booking PWA |
| **Risk Microservice** | `http://localhost:8001` | FastAPI ML scoring endpoint (`/score`, `/health`) |
| **Metabase BI** | `http://localhost:3000` | Analytics dashboards querying read-replica |
| **PostgreSQL Primary** | `5432` | OLTP transactional database |
| **PostgreSQL Replica** | `5433` | Read-only streaming standby replica |
| **Redis** | `6379` | Queue broker and cache |

---

## 🧪 Comprehensive Verification & Test Suites

SlotSaver is tested across all layers with **94+ passing tests**:

```bash
# 1. Run all Backend PHPUnit Feature & Unit Tests (89 tests)
php artisan test

# 2. Run Python Risk Microservice Pytest Suite (5 tests)
.venv/bin/pytest risk-service/tests

# 3. Check TypeScript compilation & types
npm run types:check

# 4. Verify production bundle build
npm run build
```

---

## 🏛️ System Architecture & Domain Design

```
slotsaver/
├── app/
│   ├── Domain/
│   │   ├── Booking/         # CreateBooking, CancelBooking, RescheduleBooking, MarkNoShow
│   │   ├── Reminders/       # WhatsAppChannel, SmsChannel, SendReminderJob
│   │   ├── Waitlist/        # JoinWaitlist, OfferFreedSlot, ExpireOfferJob, ClaimFreedSlot
│   │   └── Risk/            # RiskScoreClient (500ms timeout), DepositPolicy
│   ├── Http/Controllers/
│   │   ├── BookingController.php      # Dynamic buffer slot calculations & public flow
│   │   ├── DashboardController.php    # Owner KPIs, reminder funnels & quick actions
│   │   ├── WaitlistController.php     # Public waitlist join & 15-min claim screen
│   │   ├── OfflineSyncController.php  # IndexedDB replay endpoint
│   │   └── Webhooks/                  # Meta WhatsApp HMAC challenge & reply webhook
├── database/
│   ├── migrations/          # btree_gist, exclusion constraints, waitlist, reminders
│   └── seeders/             # DemoBusinessSeeder (26-week realistic longitudinal curve)
├── risk-service/            # FastAPI microservice, Logistic Regression pipeline, Dockerfile
│   ├── app/                 # FastAPI routes, Pydantic schemas, feature extraction
│   ├── model/               # Model training script, serialized model.joblib
│   └── tests/               # Pytest suite for inference & feature engineering
├── resources/js/
│   ├── components/
│   │   ├── booking/         # DepositPromptModal, WaitlistJoinModal
│   │   └── dashboard/       # KpiCards, ReminderFunnel, AppointmentLedger, WaitlistDrawer
│   ├── pages/
│   │   ├── booking/         # Public booking index & confirmation with calendar sync
│   │   ├── waitlist/        # Claim page with live 15-minute countdown timer
│   │   └── dashboard.tsx    # Executive Owner KPI dashboard
│   ├── lib/offline/         # IndexedDB queue & auto-sync manager
│   └── sw.js                # Progressive Web App service worker
└── docs/                    # Discovery notes, ADRs, runbook, demo credentials, case study
```

---

## 📚 Documentation & Technical References

- **[Master Plan](SLOTSAVER_MASTER_PLAN.md):** 8-phase architectural blueprint.
- **[Live Checklist](CHECKLIST.md):** 48-task execution tracker (100% completed).
- **[Case Study & ROI Analysis](docs/case-study.md):** In-depth engineering case study and metrics.
- **[Operational Runbook](docs/runbook.md):** Health checks, queue sweeps, and disaster recovery.
- **[Demo Credentials](docs/demo-credentials.md):** Test accounts and guided exploration walkthrough.
- **[Architecture Decision Records](docs/adr/):**
  - [ADR 001: WhatsApp over SMS](docs/adr/001-whatsapp-over-sms.md)
  - [ADR 002: Dynamic Risk Deposits](docs/adr/002-dynamic-deposit-prompt.md)
  - [ADR 003: Offline PWA & IndexedDB](docs/adr/003-offline-pwa-indexeddb.md)

---

## 📄 License
MIT License. Built for local business owners who deserve full chairs.
