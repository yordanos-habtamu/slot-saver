import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ReviewModal } from '@/components/client/review-modal';
import {
    AlertCircle,
    CalendarCheck,
    CheckCircle2,
    ExternalLink,
    Star,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { dashboard } from '@/routes';

interface ClientBookingItem {
    id: number;
    reference_code: string;
    business_name: string;
    service_name: string;
    location_name: string;
    employee_name: string;
    start_at: string;
    end_at: string;
    status: string;
    total_amount: number;
    cancellation_fee: number;
    deposit_status: string;
    rating: number | null;
    reviewable: boolean;
}

interface ClientDashboardProps {
    profile: {
        name: string;
        email: string;
    };
    summary: {
        upcoming: number;
        visits: number;
        total_spent: number;
        cancellation_fees: number;
    };
    upcoming: ClientBookingItem[];
    past: ClientBookingItem[];
}

const STATUS_STYLES: Record<string, string> = {
    confirmed: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
    completed: 'border-sky-500/30 bg-sky-500/10 text-sky-400',
    pending: 'border-amber-500/30 bg-amber-500/10 text-amber-300',
    cancelled: 'border-neutral-700 bg-neutral-800/60 text-neutral-400',
    no_show: 'border-rose-500/30 bg-rose-500/10 text-rose-400',
};

const formatMoney = (value: number): string => `$${value.toFixed(2)}`;

const formatDateTime = (iso: string): string => {
    const d = new Date(iso);
    return `${d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
};

export default function ClientDashboard({
    profile,
    summary,
    upcoming,
    past,
}: ClientDashboardProps) {
    const [reviewBooking, setReviewBooking] =
        useState<ClientBookingItem | null>(null);
    const [ratedBookings, setRatedBookings] = useState<Record<number, number>>(
        {},
    );

    const stats = [
        {
            label: 'Upcoming Appointments',
            value: summary.upcoming,
            icon: CalendarCheck,
            hint:
                summary.upcoming > 0
                    ? 'on your schedule'
                    : 'nothing booked yet',
        },
        {
            label: 'Completed Visits',
            value: summary.visits,
            icon: CheckCircle2,
            hint: 'all-time',
        },
        {
            label: 'Total Spent',
            value: formatMoney(summary.total_spent),
            icon: Wallet,
            hint: 'completed appointments',
        },
        {
            label: 'Cancellation Fees',
            value: formatMoney(summary.cancellation_fees),
            icon: TrendingUp,
            hint: 'all-time',
        },
    ];

    const handleRated = (bookingId: number, rating: number) => {
        setRatedBookings((prev) => ({ ...prev, [bookingId]: rating }));
        setReviewBooking(null);
    };

    const ratingFor = (booking: ClientBookingItem): number | null =>
        ratedBookings[booking.id] ?? booking.rating;

    return (
        <AppLayout
            breadcrumbs={[{ title: 'My Appointments', href: dashboard() }]}
        >
            <Head title="My Appointments — SlotSaver" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 pb-2 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-xl font-extrabold tracking-tight text-white">
                            Hi, {profile.name}
                        </h1>
                        <p className="mt-0.5 text-xs text-neutral-400">
                            Your appointments, visit history and reviews — all
                            in one place.
                        </p>
                    </div>

                    <a
                        href="/book"
                        className="inline-flex w-fit items-center gap-1.5 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-4 py-2 text-xs font-bold text-neutral-950 shadow-md shadow-cyan-500/20 transition hover:brightness-110"
                    >
                        <span>Book a Visit</span>
                        <ExternalLink className="h-3.5 w-3.5" />
                    </a>
                </div>

                {/* Summary cards */}
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

                {/* Upcoming appointments */}
                <section className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6 backdrop-blur-md">
                    <h2 className="text-base font-bold tracking-tight text-white">
                        Upcoming Appointments
                    </h2>

                    {upcoming.length === 0 ? (
                        <div className="mt-4 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-800 bg-neutral-950/40 px-4 py-8 text-center">
                            <CalendarCheck className="h-6 w-6 text-neutral-600" />
                            <p className="text-xs text-neutral-400">
                                Nothing booked yet. Your next visit is one click
                                away.
                            </p>
                            <a
                                href="/book"
                                className="rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-cyan-500/20"
                            >
                                Book a Visit
                            </a>
                        </div>
                    ) : (
                        <ul className="mt-4 divide-y divide-neutral-800/80">
                            {upcoming.map((booking) => (
                                <li
                                    key={booking.id}
                                    className="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-semibold text-white">
                                                {booking.service_name}
                                            </p>
                                            <span
                                                className={`rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase ${STATUS_STYLES[booking.status] ?? STATUS_STYLES.pending}`}
                                            >
                                                {booking.status.replace(
                                                    '_',
                                                    '-',
                                                )}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-xs text-neutral-400">
                                            {formatDateTime(booking.start_at)} ·{' '}
                                            {booking.business_name}
                                            {booking.location_name
                                                ? ` · ${booking.location_name}`
                                                : ''}
                                        </p>
                                        {booking.employee_name && (
                                            <p className="mt-0.5 text-[11px] text-neutral-500">
                                                with {booking.employee_name} ·{' '}
                                                {booking.reference_code}
                                            </p>
                                        )}
                                    </div>
                                    <div className="shrink-0 text-right text-xs text-neutral-300">
                                        <p className="font-semibold text-white">
                                            {formatMoney(booking.total_amount)}
                                        </p>
                                        <p className="text-[11px] text-neutral-500">
                                            {formatMoney(
                                                booking.cancellation_fee,
                                            )}{' '}
                                            fee if cancelled late
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {/* Visit history */}
                <section className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6 backdrop-blur-md">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-bold tracking-tight text-white">
                            Visit History
                        </h2>
                        <span className="text-xs text-neutral-500">
                            {past.length} record{past.length === 1 ? '' : 's'}
                        </span>
                    </div>

                    {past.length === 0 ? (
                        <p className="mt-4 text-xs text-neutral-400">
                            Your completed and past appointments will appear
                            here.
                        </p>
                    ) : (
                        <div className="mt-4 overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-neutral-800 text-[11px] tracking-wider text-neutral-500 uppercase">
                                        <th className="pr-4 pb-2 font-medium">
                                            Date
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Service
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Business
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Status
                                        </th>
                                        <th className="pr-4 pb-2 text-right font-medium">
                                            Amount
                                        </th>
                                        <th className="pr-4 pb-2 text-right font-medium">
                                            Fee
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Review
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-800/70">
                                    {past.map((booking) => {
                                        const rating = ratingFor(booking);
                                        return (
                                            <tr
                                                key={booking.id}
                                                className="text-neutral-300"
                                            >
                                                <td className="py-3 pr-4 whitespace-nowrap">
                                                    {formatDateTime(
                                                        booking.start_at,
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <span className="font-medium text-white">
                                                        {booking.service_name}
                                                    </span>
                                                    <span className="block text-[11px] text-neutral-500">
                                                        {booking.reference_code}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {booking.business_name}
                                                    {booking.location_name && (
                                                        <span className="block text-[11px] text-neutral-500">
                                                            {
                                                                booking.location_name
                                                            }
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <span
                                                        className={`rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase ${STATUS_STYLES[booking.status] ?? STATUS_STYLES.cancelled}`}
                                                    >
                                                        {booking.status.replace(
                                                            '_',
                                                            '-',
                                                        )}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4 text-right whitespace-nowrap">
                                                    {formatMoney(
                                                        booking.total_amount,
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4 text-right whitespace-nowrap text-neutral-500">
                                                    {booking.cancellation_fee >
                                                    0
                                                        ? formatMoney(
                                                              booking.cancellation_fee,
                                                          )
                                                        : '—'}
                                                </td>
                                                <td className="py-3 text-right">
                                                    {rating !== null ? (
                                                        <span className="inline-flex items-center gap-1 text-amber-300">
                                                            <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                                            {rating}.0
                                                        </span>
                                                    ) : booking.reviewable ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setReviewBooking(
                                                                    booking,
                                                                )
                                                            }
                                                            className="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-3 py-1.5 text-[11px] font-semibold text-cyan-300 transition hover:bg-cyan-500/20"
                                                        >
                                                            Rate your visit
                                                        </button>
                                                    ) : (
                                                        <span className="text-neutral-600">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                {/* Fee disclosure */}
                {summary.cancellation_fees > 0 && (
                    <p className="flex items-start gap-1.5 text-[11px] text-neutral-500">
                        <AlertCircle className="mt-px h-3 w-3 shrink-0 text-amber-400" />
                        Cancellation fees are charged when an appointment is
                        cancelled inside the free-cancellation window.
                    </p>
                )}
            </div>

            {reviewBooking && (
                <ReviewModal
                    booking={reviewBooking}
                    onClose={() => setReviewBooking(null)}
                    onRated={handleRated}
                />
            )}
        </AppLayout>
    );
}
