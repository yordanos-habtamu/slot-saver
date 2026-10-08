/**
 * SlotSaver Offline Queue Sync Manager
 */

import { getQueuedOfflineActions, removeOfflineActions } from './db';

export async function flushOfflineQueue(): Promise<{
    synced: number;
    error?: string;
}> {
    if (typeof window === 'undefined' || !navigator.onLine) {
        return { synced: 0 };
    }

    try {
        const queuedActions = await getQueuedOfflineActions();
        if (queuedActions.length === 0) {
            return { synced: 0 };
        }

        const csrfToken =
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.content || '';

        const response = await fetch('/api/offline/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ actions: queuedActions }),
        });

        if (!response.ok) {
            throw new Error(`Sync failed with HTTP ${response.status}`);
        }

        const syncedIds = queuedActions.map((a) => a.id);
        await removeOfflineActions(syncedIds);

        console.log(
            `[SlotSaver PWA] Successfully synchronized ${syncedIds.length} offline actions.`,
        );
        return { synced: syncedIds.length };
    } catch (err: any) {
        console.warn('[SlotSaver PWA] Error flushing offline queue:', err);
        return { synced: 0, error: err.message };
    }
}

export function initOfflineSyncManager(): void {
    if (typeof window === 'undefined') return;

    window.addEventListener('online', () => {
        console.log(
            '[SlotSaver PWA] Network connectivity restored. Replaying queued actions...',
        );
        void flushOfflineQueue();
    });

    // Also attempt flush on startup
    if (navigator.onLine) {
        void flushOfflineQueue();
    }
}
