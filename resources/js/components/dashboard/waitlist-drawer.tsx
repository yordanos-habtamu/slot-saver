import React from 'react';
import { Sparkles, Clock, Calendar, X } from 'lucide-react';

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

export function WaitlistDrawer({
    isOpen,
    onClose,
    entries,
}: WaitlistDrawerProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex animate-in justify-end bg-black/60 backdrop-blur-sm duration-200 fade-in">
            <div className="relative flex h-full w-full max-w-md animate-in flex-col border-l border-neutral-800 bg-neutral-950 p-6 text-neutral-100 shadow-2xl duration-300 slide-in-from-right">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-neutral-800 pb-4">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-500/30 bg-cyan-500/10 text-cyan-400">
                            <Sparkles className="h-4 w-4" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-white">
                                Waitlist Cascade Queue
                            </h3>
                            <p className="text-xs text-neutral-400">
                                Automated 15-min refill pipeline
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Queue List */}
                <div className="mt-4 flex-1 space-y-3 overflow-y-auto pr-1">
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
                                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-800 font-bold text-neutral-300">
                                                {entry.client_name.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-white">
                                                    {entry.client_name}
                                                </p>
                                                <p className="text-[11px] text-neutral-400">
                                                    {entry.service_name}
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase ${
                                                isOffered
                                                    ? 'animate-pulse border border-cyan-500/40 bg-cyan-500/20 text-cyan-300'
                                                    : isClaimed
                                                      ? 'border border-emerald-500/40 bg-emerald-500/20 text-emerald-300'
                                                      : 'bg-neutral-800 text-neutral-400'
                                            }`}
                                        >
                                            {entry.status}
                                        </span>
                                    </div>

                                    <div className="mt-3 flex items-center justify-between border-t border-neutral-800/60 pt-2 text-[11px] text-neutral-400">
                                        <div className="flex items-center gap-1">
                                            <Calendar className="h-3 w-3 text-neutral-500" />
                                            <span>{entry.preferred_date}</span>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Clock className="h-3 w-3 text-neutral-500" />
                                            <span>
                                                {entry.preferred_window}
                                            </span>
                                        </div>
                                    </div>

                                    {isOffered && entry.offer_expires_at && (
                                        <div className="mt-2.5 flex items-center gap-1.5 rounded-lg border border-cyan-500/20 bg-cyan-500/10 p-2 text-[11px] text-cyan-300">
                                            <Clock className="h-3.5 w-3.5 shrink-0" />
                                            <span>
                                                Exclusive 15-min claim hold
                                                active. Cascades if unclaimed.
                                            </span>
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>

                {/* Footer */}
                <div className="border-t border-neutral-800 pt-4 text-center text-[11px] text-neutral-500">
                    When any appointment is cancelled, SlotSaver automatically
                    selects candidate #1 and fires an instant WhatsApp claim
                    alert.
                </div>
            </div>
        </div>
    );
}
