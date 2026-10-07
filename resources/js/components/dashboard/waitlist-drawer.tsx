import React from 'react';
import { Sparkles, Clock, CheckCircle2, User, Calendar, X, AlertCircle } from 'lucide-react';

interface WaitlistQueueItem {
    id: number;
    client_name: string;
    service_name: string;
    preferred_date: string;
    preferred_window: string;
    status: string;
    offer_expires_at: string | null;
}

interface WaitlistDrawerProps {
    isOpen: boolean;
    onClose: () => void;
    entries: WaitlistQueueItem[];
}

export function WaitlistDrawer({ isOpen, onClose, entries }: WaitlistDrawerProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex justify-end bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div className="relative w-full max-w-md h-full bg-neutral-950 border-l border-neutral-800 p-6 flex flex-col text-neutral-100 shadow-2xl animate-in slide-in-from-right duration-300">
                {/* Header */}
                <div className="flex items-center justify-between pb-4 border-b border-neutral-800">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400">
                            <Sparkles className="h-4 w-4" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-white">Waitlist Cascade Queue</h3>
                            <p className="text-xs text-neutral-400">Automated 15-min refill pipeline</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-800 hover:text-white transition"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Queue List */}
                <div className="mt-4 flex-1 overflow-y-auto space-y-3 pr-1">
                    {entries.length === 0 ? (
                        <div className="py-12 text-center text-xs text-neutral-500">
                            No active candidates in the waitlist queue.
                        </div>
                    ) : (
                        entries.map((entry) => {
                            const isOffered = entry.status === 'offered';
                            const isClaimed = entry.status === 'claimed';

                            return (
                                <div
                                    key={entry.id}
                                    className={`rounded-xl border p-4 text-xs transition ${
                                        isOffered
                                            ? 'border-cyan-500/60 bg-cyan-950/20 shadow-md shadow-cyan-950/30'
                                            : isClaimed
                                            ? 'border-emerald-500/40 bg-emerald-950/20'
                                            : 'border-neutral-800 bg-neutral-900/40'
                                    }`}
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-800 text-neutral-300 font-bold">
                                                {entry.client_name.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-white">{entry.client_name}</p>
                                                <p className="text-[11px] text-neutral-400">{entry.service_name}</p>
                                            </div>
                                        </div>
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${
                                                isOffered
                                                    ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 animate-pulse'
                                                    : isClaimed
                                                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                                                    : 'bg-neutral-800 text-neutral-400'
                                            }`}
                                        >
                                            {entry.status}
                                        </span>
                                    </div>

                                    <div className="mt-3 flex items-center justify-between text-[11px] text-neutral-400 pt-2 border-t border-neutral-800/60">
                                        <div className="flex items-center gap-1">
                                            <Calendar className="h-3 w-3 text-neutral-500" />
                                            <span>{entry.preferred_date}</span>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Clock className="h-3 w-3 text-neutral-500" />
                                            <span>{entry.preferred_window}</span>
                                        </div>
                                    </div>

                                    {isOffered && entry.offer_expires_at && (
                                        <div className="mt-2.5 rounded-lg bg-cyan-500/10 border border-cyan-500/20 p-2 text-[11px] text-cyan-300 flex items-center gap-1.5">
                                            <Clock className="h-3.5 w-3.5 shrink-0" />
                                            <span>Exclusive 15-min claim hold active. Cascades if unclaimed.</span>
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>

                {/* Footer */}
                <div className="pt-4 border-t border-neutral-800 text-[11px] text-neutral-500 text-center">
                    When any appointment is cancelled, SlotSaver automatically selects candidate #1 and fires an instant WhatsApp claim alert.
                </div>
            </div>
        </div>
    );
}
