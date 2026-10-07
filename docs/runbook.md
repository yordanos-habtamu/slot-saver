# SlotSaver Operational Runbook & Disaster Recovery

This runbook details routine operational workflows, health checks, troubleshooting procedures, and disaster recovery commands for SlotSaver production deployments.

---

## 1. System Health Checks

### Primary Application & Database Health
```bash
# Check Laravel application status
php artisan about

# Verify database migrations and connection
php artisan migrate:status

# Verify Redis cache & queue connectivity
redis-cli ping
```

### Risk Microservice Health Check
```bash
# FastAPI health check endpoint
curl -s http://127.0.0.1:8001/health | jq .

# Expected response:
# {
#   "status": "healthy",
#   "model_loaded": true,
#   "version": "1.0.0"
# }
```

### Background Queue Health
```bash
# Verify failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 2. Inbound Webhook Monitoring & Meta Verification

### Verify WhatsApp Webhook Verification
```bash
# Simulate Meta webhook verification handshake
curl -X GET "http://localhost:8000/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=slotsaver_webhook_secret_token&hub.challenge=test_challenge_code"

# Expected response:
# test_challenge_code (HTTP 200)
```

### Webhook Deduplication & Replay Audit
All processed webhooks are recorded in the `webhook_events` table with unique idempotency keys (`whatsapp_msg_<wamid>`).
```sql
SELECT event_id, event_type, status, created_at 
FROM webhook_events 
ORDER BY created_at DESC 
LIMIT 20;
```

---

## 3. Scheduled Cron Tasks & Queue Sweepers

SlotSaver relies on three mission-critical scheduled tasks configured in `routes/console.php`:

| Schedule | Command | Function |
| :--- | :--- | :--- |
| `*/5 * * * *` | `reminders:dispatch` | Scans upcoming bookings due for 48h, 24h, or 2h reminders |
| `* * * * *` | `waitlist:sweep-expired` | Sweeps expired 15-minute hold offers and cascades to candidate #2 |
| `0 2 * * *` | `risk:export-features` | Nightly export of appointment records to CSV for ML retraining |

To trigger manually:
```bash
php artisan reminders:dispatch
php artisan waitlist:sweep-expired
php artisan risk:export-features
```

---

## 4. Disaster Recovery & Common Scenarios

### Scenario A: Risk Microservice is Unreachable
- **Behavior:** The Laravel `RiskScoreClient` enforces a strict 500ms timeout (`config/services.php`). If the microservice is offline or throws a 500, it automatically activates the `localHeuristicsFallback` rule engine.
- **Action:**
  ```bash
  # Check container logs
  docker logs slotsaver-risk-service
  
  # Restart service
  docker restart slotsaver-risk-service
  ```

### Scenario B: PostgreSQL Exclusion Violation Spike
- **Symptom:** Logs report SQLSTATE `23P01` (`exclusion_violation`).
- **Explanation:** This is an expected PostgreSQL protection mechanism indicating that two concurrent transactions attempted to claim the exact same employee time slot. The application catches this cleanly and returns HTTP 409 (`SlotAlreadyBookedException`).
- **Action:** No database cleanup required; PostgreSQL successfully preserved scheduling integrity.

### Scenario C: Offline Staff Queue Out of Sync
- Staff devices will automatically sync upon network reconnect. To manually flush a client queue, open DevTools $\rightarrow$ Application $\rightarrow$ IndexedDB $\rightarrow$ `slotsaver_offline_db` $\rightarrow$ inspect `queued_actions`.

---

## 5. Machine Learning Model Retraining

To retrain the Logistic Regression model using newly accumulated appointment data:
```bash
# 1. Export fresh features from database
php artisan risk:export-features --path=risk-service/data/training_data.csv

# 2. Train new model artifact
.venv/bin/python risk-service/model/train.py

# 3. Run evaluation & tests
.venv/bin/pytest risk-service/tests
```
