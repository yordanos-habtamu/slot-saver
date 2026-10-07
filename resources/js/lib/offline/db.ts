/**
 * IndexedDB Offline Action Queue for SlotSaver
 */

const DB_NAME = 'slotsaver_offline_db';
const DB_VERSION = 1;
const STORE_NAME = 'queued_actions';

export interface QueuedOfflineAction {
    id: string;
    type: 'check_in' | 'mark_no_show';
    booking_id: number;
    timestamp: number;
    payload?: Record<string, any>;
}

export function openOfflineDB(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        if (typeof window === 'undefined' || !window.indexedDB) {
            reject(new Error('IndexedDB not supported in this environment'));
            return;
        }

        const request = window.indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event: IDBVersionChangeEvent) => {
            const db = (event.target as IDBOpenDBRequest).result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function queueOfflineAction(
    type: 'check_in' | 'mark_no_show',
    bookingId: number,
    payload?: Record<string, any>
): Promise<string> {
    const db = await openOfflineDB();
    const id = `action_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    const action: QueuedOfflineAction = {
        id,
        type,
        booking_id: bookingId,
        timestamp: Date.now(),
        payload,
    };

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const req = store.add(action);

        req.onsuccess = () => resolve(id);
        req.onerror = () => reject(req.error);
    });
}

export async function getQueuedOfflineActions(): Promise<QueuedOfflineAction[]> {
    const db = await openOfflineDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const req = store.getAll();

        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => reject(req.error);
    });
}

export async function removeOfflineActions(ids: string[]): Promise<void> {
    if (ids.length === 0) return;
    const db = await openOfflineDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);

        ids.forEach((id) => store.delete(id));
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}
