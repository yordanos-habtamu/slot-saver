import React, { useState } from 'react';
import {
    Phone,
    Check,
    UserX,
    MessageSquare,
    Search,
    Sparkles,
} from 'lucide-react';

export interface AppointmentLedgerItem {
    id: number;
    reference_code: string;
    client_name: string;
    client_phone: string;
    service_name: string;
    employee_name: string;
    location_name: string;
    start_at: string;
    end_at: string;
    status: string;
    risk_score: number;
    risk_tier: string;
    deposit_status: string;
    deposit_amount: number;
    total_amount: number;
}

interface AppointmentLedgerProps {
    appointments: AppointmentLedgerItem[];
    onOpenWaitlist?: () => void;
}

export function AppointmentLedger({
    appointments: initialAppointments,
    onOpenWaitlist,
}: AppointmentLedgerProps) {
    const [appointments, setAppointments] =
        useState<AppointmentLedgerItem[]>(initialAppointments);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [actionMessage, setActionMessage] = useState<string | null>(null);

    const showNotification = (msg: string) => {
        setActionMessage(msg);
        setTimeout(() => setActionMessage(null), 3000);
    };

    const handleCheckIn = async (booking: AppointmentLedgerItem) => {
        try {
            const res = await fetch(`/api/bookings/${booking.id}/check-in`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
            });
            if (res.ok) {
                setAppointments((prev) =>
                    prev.map((b) =>
                        b.id === booking.id ? { ...b, status: 'completed' } : b,
                    ),
                );
                showNotification(`Checked in: ${booking.client_name}`);
            }
        } catch {
            showNotification('Error checking in appointment.');
        }
    };

    const handleMarkNoShow = async (booking: AppointmentLedgerItem) => {
        if (
            !confirm(
                `Mark ${booking.client_name} (${booking.reference_code}) as No-Show? This will forfeit any deposit.`,
            )
        ) {
            return;
        }

        try {
            const res = await fetch(
                `/api/bookings/${booking.id}/mark-no-show`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            (
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                ) as HTMLMetaElement
                            )?.content || '',
                    },
                },
            );
            if (res.ok) {
                setAppointments((prev) =>
                    prev.map((b) =>
                        b.id === booking.id
                            ? {
                                  ...b,
                                  status: 'no_show',
                                  deposit_status: 'forfeited',
                              }
                            : b,
                    ),
                );
                showNotification(
                    `Marked as No-Show: ${booking.client_name}. Deposit forfeited.`,
                );
            }
        } catch {
            showNotification('Error marking no-show.');
        }
    };

    const handleSendReminder = async (booking: AppointmentLedgerItem) => {
        try {
            const res = await fetch(
                `/api/bookings/${booking.id}/send-reminder`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            (
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                ) as HTMLMetaElement
                            )?.content || '',
                    },
                },
            );
            if (res.ok) {
                showNotification(
                    `WhatsApp reminder sent to ${booking.client_phone}`,
                );
            }
        } catch {
            showNotification('Error dispatching reminder.');
        }
    };

    const filtered = appointments.filter((b) => {
        const matchesSearch =
            b.client_name.toLowerCase().includes(search.toLowerCase()) ||
            b.reference_code.toLowerCase().includes(search.toLowerCase()) ||
            b.client_phone.includes(search);
        const matchesStatus =
            statusFilter === 'all' || b.status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    return (
        <div className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6 backdrop-blur-md">
            {/* Header with Search and Filters */}
            <div className="flex flex-col justify-between gap-4 border-b border-neutral-800 pb-5 sm:flex-row sm:items-center">
                <div>
                    <h3 className="text-base font-bold tracking-tight text-white">
                        Live Appointment Ledger
                    </h3>
                    <p className="mt-0.5 text-xs text-neutral-400">
                        Real-time attendance tracking, ML risk scoring, and
                        quick staff actions
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    {onOpenWaitlist && (
                        <button
                            type="button"
                            onClick={onOpenWaitlist}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3.5 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-cyan-500/20"
                        >
                            <Sparkles className="h-3.5 w-3.5 text-cyan-400" />
                            Waitlist Queue
                        </button>
                    )}

                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-neutral-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search client or ref..."
                            className="w-44 rounded-xl border border-neutral-800 bg-neutral-950/80 py-1.5 pr-3 pl-8 text-xs text-neutral-200 outline-none focus:border-cyan-500"
                        />
                    </div>

                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="rounded-xl border border-neutral-800 bg-neutral-950/80 px-3 py-1.5 text-xs text-neutral-300 outline-none focus:border-cyan-500"
                    >
                        <option value="all">All Statuses</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No-Show</option>
                    </select>
                </div>
            </div>

            {/* Notification alert */}
            {actionMessage && (
                <div className="mt-4 flex animate-in items-center gap-2 rounded-xl border border-cyan-500/30 bg-cyan-950/30 p-3 text-xs text-cyan-300 duration-200 fade-in">
                    <Check className="h-4 w-4 shrink-0 text-cyan-400" />
                    <span>{actionMessage}</span>
                </div>
            )}

            {/* Ledger Table */}
            <div className="mt-4 overflow-x-auto">
                <table className="w-full text-left text-xs">
                    <thead>
                        <tr className="border-b border-neutral-800/80 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                            <th className="px-3 py-3">Appointment</th>
                            <th className="px-3 py-3">Client</th>
                            <th className="px-3 py-3">Service & Specialist</th>
                            <th className="px-3 py-3">Attendance Risk</th>
                            <th className="px-3 py-3">Deposit</th>
                            <th className="px-3 py-3">Status</th>
                            <th className="px-3 py-3 text-right">
                                Quick Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-800/50">
                        {filtered.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="py-8 text-center text-neutral-500"
                                >
                                    No appointments matching current filters.
                                </td>
                            </tr>
                        ) : (
                            filtered.map((item) => {
                                const startDate = new Date(item.start_at);
                                const timeStr = startDate.toLocaleTimeString(
                                    'en-US',
                                    {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true,
                                    },
                                );
                                const dateStr = startDate.toLocaleDateString(
                                    'en-US',
                                    {
                                        month: 'short',
                                        day: 'numeric',
                                    },
                                );

                                return (
                                    <tr
                                        key={item.id}
                                        className="transition hover:bg-neutral-800/20"
                                    >
                                        {/* Time & Reference */}
                                        <td className="px-3 py-3.5">
                                            <div className="font-semibold text-white">
                                                {timeStr}
                                            </div>
                                            <div className="text-[11px] text-neutral-400">
                                                {dateStr}
                                            </div>
                                            <span className="font-mono text-[10px] text-cyan-400">
                                                {item.reference_code}
                                            </span>
                                        </td>

                                        {/* Client */}
                                        <td className="px-3 py-3.5">
                                            <div className="font-medium text-white">
                                                {item.client_name}
                                            </div>
                                            <div className="mt-0.5 flex items-center gap-1 text-[11px] text-neutral-400">
                                                <Phone className="h-3 w-3 text-neutral-500" />
                                                {item.client_phone}
                                            </div>
                                        </td>

                                        {/* Service & Staff */}
                                        <td className="px-3 py-3.5">
                                            <div className="font-medium text-neutral-200">
                                                {item.service_name}
                                            </div>
                                            <div className="text-[11px] text-neutral-400">
                                                {item.employee_name}
                                            </div>
                                        </td>

                                        {/* ML Attendance Risk */}
                                        <td className="px-3 py-3.5">
                                            <div className="flex items-center gap-1.5">
                                                <span
                                                    className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                                                        item.risk_score >= 0.65
                                                            ? 'border border-rose-500/30 bg-rose-500/10 text-rose-400'
                                                            : item.risk_score >=
                                                                0.35
                                                              ? 'border border-amber-500/30 bg-amber-500/10 text-amber-400'
                                                              : 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                                    }`}
                                                >
                                                    {(
                                                        item.risk_score * 100
                                                    ).toFixed(0)}
                                                    % Risk
                                                </span>
                                            </div>
                                        </td>

                                        {/* Deposit */}
                                        <td className="px-3 py-3.5">
                                            {item.deposit_status === 'paid' ? (
                                                <span className="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-400">
                                                    Paid $
                                                    {item.deposit_amount.toFixed(
                                                        0,
                                                    )}
                                                </span>
                                            ) : item.deposit_status ===
                                              'forfeited' ? (
                                                <span className="rounded-md border border-rose-500/30 bg-rose-500/10 px-2 py-0.5 text-[10px] font-semibold text-rose-400">
                                                    Forfeited $
                                                    {item.deposit_amount.toFixed(
                                                        0,
                                                    )}
                                                </span>
                                            ) : (
                                                <span className="text-[11px] text-neutral-500">
                                                    None
                                                </span>
                                            )}
                                        </td>

                                        {/* Status */}
                                        <td className="px-3 py-3.5">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase ${
                                                    item.status === 'confirmed'
                                                        ? 'border border-emerald-500/40 bg-emerald-500/20 text-emerald-300'
                                                        : item.status ===
                                                            'completed'
                                                          ? 'border border-blue-500/40 bg-blue-500/20 text-blue-300'
                                                          : item.status ===
                                                              'no_show'
                                                            ? 'border border-rose-500/40 bg-rose-500/20 text-rose-400'
                                                            : 'bg-neutral-800 text-neutral-400'
                                                }`}
                                            >
                                                {item.status}
                                            </span>
                                        </td>

                                        {/* Actions */}
                                        <td className="px-3 py-3.5 text-right">
                                            <div className="flex items-center justify-end gap-1.5">
                                                {item.status ===
                                                    'confirmed' && (
                                                    <>
                                                        <button
                                                            onClick={() =>
                                                                handleCheckIn(
                                                                    item,
                                                                )
                                                            }
                                                            className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-1.5 text-emerald-400 transition hover:bg-emerald-500/20"
                                                            title="Mark Completed"
                                                        >
                                                            <Check className="h-3.5 w-3.5" />
                                                        </button>
                                                        <button
                                                            onClick={() =>
                                                                handleMarkNoShow(
                                                                    item,
                                                                )
                                                            }
                                                            className="rounded-lg border border-rose-500/30 bg-rose-500/10 p-1.5 text-rose-400 transition hover:bg-rose-500/20"
                                                            title="Mark No-Show"
                                                        >
                                                            <UserX className="h-3.5 w-3.5" />
                                                        </button>
                                                        <button
                                                            onClick={() =>
                                                                handleSendReminder(
                                                                    item,
                                                                )
                                                            }
                                                            className="rounded-lg border border-neutral-700 bg-neutral-800 p-1.5 text-neutral-300 transition hover:text-white"
                                                            title="Trigger WhatsApp Reminder"
                                                        >
                                                            <MessageSquare className="h-3.5 w-3.5" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
