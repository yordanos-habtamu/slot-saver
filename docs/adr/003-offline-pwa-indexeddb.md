# ADR 003: Progressive Web App with IndexedDB Offline Action Queue

## Status
Accepted

## Date
2026-10-06

## Context
Service businesses often operate in physical environments with inconsistent Wi-Fi connectivity (e.g. basement salons, thick masonry buildings, crowded clinic front desks). 

When staff attempt to mark a client as arrived, completed, or no-show:
- Network latency or connectivity drops lead to blocked UI spinners, failed HTTP requests, or forgotten attendance updates.
- If staff abandon attendance check-in due to slow internet, the historical data model becomes inaccurate, undermining future machine learning predictions.

## Decision
1. Package the frontend as an offline-first **Progressive Web App (PWA)** using modern Service Worker caching:
   - Cache-First strategy for static assets, styles, and web fonts.
   - Network-First strategy with stale-while-revalidate fallback for daily schedule views.
2. Implement a local **IndexedDB Action Queue** (`slotsaver_offline_db`):
   - When a staff member performs a quick action (Check In, Mark No-Show) and the device is offline, the action is encapsulated with a unique UUID, timestamp, and payload and stored in the IndexedDB object store.
   - The UI updates optimistically with immediate visual confirmation.
3. Implement an autonomous **Sync Manager**:
   - Listens to `window.addEventListener('online', ...)`.
   - When connectivity resumes, the manager flushes all queued actions in a single batch to `POST /api/offline/sync`.
   - Server processes the actions idempotently and returns confirmed sync IDs, which are then cleared from IndexedDB.

## Consequences
### Positive
- Sub-50ms perceived UI responsiveness for staff regardless of network health.
- Zero lost attendance data during Wi-Fi outages.
- Front desk staff can operate with full confidence on mobile tablets and iPads.

### Negative / Trade-offs
- Requires careful conflict handling if an appointment was modified concurrently elsewhere.
- Increases frontend client-side complexity with service workers and IndexedDB lifecycle handling.
