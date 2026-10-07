import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import {
    AppointmentLedger,
    AppointmentLedgerItem,
} from '@/components/dashboard/appointment-ledger';
import {
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    MapPin,
    Star,
    Wifi,
    WifiOff,
} from 'lucide-react';
import { dashboard } from '@/routes';

interface EmployeeDashboardProps {
    profile: {
        name: string;
        rating_average: number;
        rating_count: number;
        locations: Array<{ id: number; name: string; city: string }>;
        services: Array<{ id: number; name: string }>;
    };
    selected_date: string;
    today: string;
    summary: {
        total: number;
        completed: number;
        no_show: number;
        cancelled: number;
        remaining: number;
        week_total: number;
    };
    appointments: AppointmentLedgerItem[];
}

const shiftDate = (date: string, days: number): string => {
    const d = new Date(`${date}T12:00:00`);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
};

const formatDay = (date: string): string =>
    new Date(`${date}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

export default function EmployeeDashboard({
    profile,
    selected_date,
    today,
    summary,
    appointments,
}: EmployeeDashboardProps) {
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

    const goToDay = (date: string) => {
        router.get(
            dashboard().url,
            { date },
            { preserveState: true, preserveScroll: true },
        );
    };

    const stats = [
        {
            label: 'Appointments',
            value: summary.total,
            icon: CalendarDays,
            hint:
                summary.remaining > 0
                    ? `${summary.remaining} still to see`
                    : 'day fully handled',
        },
        {
            label: 'Completed',
            value: summary.completed,
            icon: CheckCircle2,
            hint:
                summary.no_show > 0
                    ? `${summary.no_show} no-show`
                    : 'no no-shows',
        },
        {
            label: 'Remaining',
            value: summary.remaining,
            icon: Clock3,
            hint:
                summary.total > 0
                    ? `${summary.cancelled} cancelled`
                    : 'nothing queued',
        },
        {
            label: 'Next 7 Days',
            value: summary.week_total,
            icon: CalendarDays,
            hint: 'confirmed & pending',
        },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'My Schedule', href: dashboard() }]}>
            <Head title="My Schedule — SlotSaver" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header: greeting + date navigation + status */}
                <div className="flex flex-col justify-between gap-4 pb-2 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-xl font-extrabold tracking-tight text-white">
                            {profile.name}
                        </h1>
                        <p className="mt-0.5 text-xs text-neutral-400">
                            {formatDay(selected_date)}
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
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

                        {/* Day navigation */}
                        <div className="flex items-center gap-1 rounded-xl border border-neutral-800 bg-neutral-900/60 p-1">
                            <button
                                type="button"
                                onClick={() =>
                                    goToDay(shiftDate(selected_date, -1))
                                }
                                className="rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                                aria-label="Previous day"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => goToDay(today)}
                                disabled={selected_date === today}
                                className="rounded-lg px-3 py-1 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white disabled:cursor-default disabled:text-emerald-400"
                            >
                                {selected_date === today ? 'Today' : 'Today'}
                            </button>
                            <button
                                type="button"
                                onClick={() =>
                                    goToDay(shiftDate(selected_date, 1))
                                }
                                className="rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                                aria-label="Next day"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Profile strip: rating + locations + specialties */}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-neutral-800 bg-neutral-900/60 px-4 py-3 text-xs">
                    <span className="inline-flex items-center gap-1.5 font-semibold text-amber-300">
                        <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                        {profile.rating_average > 0
                            ? profile.rating_average.toFixed(1)
                            : 'New'}
                        <span className="font-normal text-neutral-400">
                            ({profile.rating_count}{' '}
                            {profile.rating_count === 1 ? 'review' : 'reviews'})
                        </span>
                    </span>

                    {profile.locations.length > 0 && (
                        <span className="inline-flex items-center gap-1.5 text-neutral-400">
                            <MapPin className="h-3.5 w-3.5 text-cyan-400" />
                            {profile.locations
                                .map((l) => `${l.name}, ${l.city}`)
                                .join(' · ')}
                        </span>
                    )}

                    {profile.services.length > 0 && (
                        <span className="text-neutral-400">
                            <span className="text-neutral-500">Services:</span>{' '}
                            {profile.services.map((s) => s.name).join(' · ')}
                        </span>
                    )}
                </div>

                {/* Day stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-4 backdrop-blur-md"
                        >
                            <div className="flex items-center justify-between">
                                <p className="text-xs text-neutral-400">
                                    {stat.label}
                                </p>
                                <stat.icon className="h-4 w-4 text-cyan-400" />
                            </div>
                            <p className="mt-1.5 text-2xl font-extrabold tracking-tight text-white">
                                {stat.value}
                            </p>
                            <p className="mt-0.5 text-[11px] text-neutral-500">
                                {stat.hint}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Personal ledger with quick staff actions */}
                <AppointmentLedger appointments={appointments} />
            </div>
        </AppLayout>
    );
}
