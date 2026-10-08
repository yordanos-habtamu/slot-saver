import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { KpiCards } from '@/components/dashboard/kpi-cards';
import { ReminderFunnel } from '@/components/dashboard/reminder-funnel';
import {
    AppointmentLedger,
    AppointmentLedgerItem,
} from '@/components/dashboard/appointment-ledger';
import { WaitlistDrawer } from '@/components/dashboard/waitlist-drawer';
import { ExternalLink, Wifi, WifiOff } from 'lucide-react';
import { dashboard } from '@/routes';

interface DashboardProps {
    business: {
        id: number;
        name: string;
        slug?: string | null;
        city: string;
    };
    kpis: {
        no_show_rate: number;
        baseline_no_show_rate: number;
        reduction_percentage: number;
        recovered_revenue: number;
        refilled_slots_count: number;
        admin_hours_saved: number;
    };
    funnel: {
        sent: number;
        delivered: number;
        read: number;
        confirmed: number;
        rescheduled: number;
        cancelled: number;
        no_show: number;
    };
    risk_distribution: {
        low: number;
        medium: number;
        high: number;
    };
    appointments: AppointmentLedgerItem[];
    waitlist: Array<{
        id: number;
        client_name: string;
        service_name: string;
        preferred_date: string;
        preferred_window: string;
        status: string;
        offer_expires_at: string | null;
    }>;
}

export default function Dashboard({
    business,
    kpis,
    funnel,
    risk_distribution,
    appointments,
    waitlist,
}: DashboardProps) {
    const [waitlistOpen, setWaitlistOpen] = useState(false);
    const [isOnline, setIsOnline] = useState(true);

    useEffect(() => {
        setIsOnline(navigator.onLine);
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Owner Dashboard', href: dashboard() }]}
        >
            <Head title="Owner KPI Dashboard — SlotSaver" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Top Status Bar */}
                <div className="flex flex-col justify-between gap-4 pb-2 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-extrabold tracking-tight text-white">
                                {business.name}
                            </h1>
                            <span
                                className={`flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold ${
                                    kpis.refilled_slots_count > 0
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                        : 'border-neutral-700 bg-neutral-800/60 text-neutral-400'
                                }`}
                            >
                                <span
                                    className={`h-1.5 w-1.5 rounded-full ${
                                        kpis.refilled_slots_count > 0
                                            ? 'animate-pulse bg-emerald-400'
                                            : 'bg-neutral-500'
                                    }`}
                                />
                                {kpis.refilled_slots_count > 0
                                    ? `Refill Engine Active · ${kpis.refilled_slots_count} refills`
                                    : 'Refill Engine Standby'}
                            </span>
                        </div>
                        <p className="mt-0.5 text-xs text-neutral-400">
                            Real-time attendance protection, dynamic deposit
                            thresholds & automated refills
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Offline / Online indicator */}
                        <div
                            className={`flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-medium ${
                                isOnline
                                    ? 'border-neutral-800 bg-neutral-900/60 text-neutral-400'
                                    : 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                            }`}
                        >
                            {isOnline ? (
                                <>
                                    <Wifi className="h-3.5 w-3.5 text-emerald-400" />
                                    <span>Cloud Sync Online</span>
                                </>
                            ) : (
                                <>
                                    <WifiOff className="h-3.5 w-3.5 text-amber-400" />
                                    <span>Offline PWA Mode Active</span>
                                </>
                            )}
                        </div>

                        {/* Public booking preview link */}
                        <a
                            href={
                                business.slug
                                    ? `/book/${business.slug}`
                                    : '/book'
                            }
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-neutral-950 shadow-md shadow-cyan-500/20 transition hover:brightness-110"
                        >
                            <span>Client Booking Page</span>
                            <ExternalLink className="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>

                {/* 1. Primary KPI Metric Cards (T6.4) */}
                <KpiCards kpis={kpis} />

                {/* 2. WhatsApp Reminder Funnel & Risk Distribution (T6.5) */}
                <ReminderFunnel
                    funnel={funnel}
                    riskDistribution={risk_distribution}
                />

                {/* 3. Live Appointment Ledger with Quick Staff Actions (T6.6) */}
                <AppointmentLedger
                    appointments={appointments}
                    onOpenWaitlist={() => setWaitlistOpen(true)}
                />

                {/* Slide-over Waitlist Queue Drawer (T6.6) */}
                <WaitlistDrawer
                    isOpen={waitlistOpen}
                    onClose={() => setWaitlistOpen(false)}
                    entries={waitlist}
                />
            </div>
        </AppLayout>
    );
}
