# SlotSaver: File Structure

```
slotsaver/
├── README.md
├── docker-compose.yml
├── .env.example
├── .github/workflows/ci.yml
│
├── docs/
│   ├── architecture.png
│   ├── demo-credentials.md
│   ├── case-study.md                 # 1-page write-up for recruiters
│   ├── discovery/
│   │   ├── interview-notes.md
│   │   ├── current-process-map.md
│   │   └── decisions-from-discovery.md
│   ├── adr/
│   │   ├── 001-whatsapp-over-sms.md
│   │   ├── 002-deposit-prompt.md
│   │   └── 003-offline-pwa.md
│   └── runbook.md                    # what to do when reminders fail
│
├── app/                              # Laravel
│   ├── Domain/
│   │   ├── Booking/
│   │   │   ├── Actions/              # CreateBooking, CancelBooking, RescheduleBooking
│   │   │   ├── Models/               # Booking, Service, Staff, Customer
│   │   │   └── Policies/
│   │   ├── Reminders/
│   │   │   ├── Jobs/                 # SendReminder, ProcessInboundReply
│   │   │   ├── Channels/             # WhatsAppChannel, SmsChannel
│   │   │   └── Schedulers/
│   │   ├── Waitlist/
│   │   │   └── Actions/              # OfferFreedSlot, ExpireOffer
│   │   └── Risk/
│   │       ├── RiskScoreClient.php   # calls FastAPI service
│   │       └── DepositPolicy.php
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Webhooks/                 # WhatsAppWebhookController (idempotent)
│   └── Console/Commands/ExportRiskFeatures.php
│
├── database/
│   ├── migrations/                   # includes exclusion constraint for double-booking
│   └── seeders/DemoBusinessSeeder.php
│
├── resources/js/                     # Inertia + React
│   ├── Pages/
│   │   ├── Booking/
│   │   ├── Dashboard/                # NoShowRate, RecoveredRevenue, ReminderFunnel
│   │   ├── Waitlist/
│   │   └── Settings/                 # per-trade config
│   ├── Components/
│   ├── lib/offline/                  # service worker helpers, IndexedDB queue
│   └── sw.js
│
├── risk-service/                     # Python
│   ├── app/main.py                   # FastAPI: POST /score
│   ├── app/features.py
│   ├── model/train.py
│   ├── notebooks/eval.ipynb
│   ├── tests/
│   └── Dockerfile
│
├── analytics/
│   └── metabase-dashboards/export.json
│
└── tests/
    ├── Feature/                      # booking, reminders, webhook idempotency
    └── Unit/
```
