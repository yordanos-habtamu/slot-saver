import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { KpiCards } from '@/components/dashboard/kpi-cards';
import { ReminderFunnel } from '@/components/dashboard/reminder-funnel';
import { Building2, CalendarClock } from 'lucide-react';
import { dashboard } from '@/routes';
import adminBusinesses from '@/routes/admin/businesses';
import type { BreadcrumbItem } from '@/types/navigation';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Platform Overview',
        href: dashboard().url,
    },
];

interface PlatformOverviewProps {
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
    summary: {
        business_count: number;
        bookings_count: number;
        waitlist_refills: number;
    };
    top_businesses: Array<{
        id: number;
        name: string;
        bookings_count: number;
        no_show_rate: number;
    }>;
    recent_bookings: Array<{
        reference_code: string;
        business_name: string;
        client_name: string;
        service_name: string;
        location_name: string;
        start_at: string;
        status: string;
        total_amount: number;
    }>;
}

function BookingStatusBadge({ status }: { status: string }) {
    const variant =
        status === 'completed'
            ? 'default'
            : status === 'confirmed' || status === 'pending'
              ? 'secondary'
              : status === 'cancelled' || status === 'no_show'
                ? 'destructive'
                : 'outline';

    return <Badge variant={variant}>{status}</Badge>;
}

export default function PlatformOverview({
    kpis,
    funnel,
    risk_distribution,
    summary,
    top_businesses,
    recent_bookings,
}: PlatformOverviewProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Platform Overview — SlotSaver" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 pb-2 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-xl font-extrabold tracking-tight text-white">
                            Platform Overview
                        </h1>
                        <p className="mt-0.5 text-xs text-neutral-400">
                            {summary.business_count} businesses ·{' '}
                            {summary.bookings_count} bookings (26 weeks) ·{' '}
                            {summary.waitlist_refills} waitlist refills
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button asChild variant="secondary" size="sm">
                            <Link href={adminBusinesses.index().url}>
                                <Building2 className="h-4 w-4" />
                                Manage businesses
                            </Link>
                        </Button>
                    </div>
                </div>

                <KpiCards kpis={kpis} />

                <ReminderFunnel
                    funnel={funnel}
                    riskDistribution={risk_distribution}
                />

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    {/* Top businesses */}
                    <Card className="lg:col-span-5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Building2 className="h-4 w-4 text-cyan-400" />
                                Top businesses
                            </CardTitle>
                            <CardDescription>
                                Busiest salons over the last 26 weeks
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {top_businesses.length === 0 && (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No bookings recorded yet.
                                </p>
                            )}
                            {top_businesses.map((business) => (
                                <Link
                                    key={business.id}
                                    href={
                                        adminBusinesses.show({
                                            business: business.id,
                                        }).url
                                    }
                                    className="flex items-center justify-between rounded-xl border border-neutral-800/80 bg-neutral-950/40 p-3.5 transition hover:border-neutral-700"
                                >
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-sm font-bold text-white">
                                            {business.name.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-white">
                                                {business.name}
                                            </p>
                                            <p className="text-[11px] text-neutral-400">
                                                {business.bookings_count}{' '}
                                                bookings
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        className={`rounded-full border px-2 py-0.5 text-[11px] font-semibold ${
                                            business.no_show_rate > 15
                                                ? 'border-rose-500/30 bg-rose-500/10 text-rose-400'
                                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                        }`}
                                    >
                                        {business.no_show_rate}% no-show
                                    </span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Recent appointments across the platform */}
                    <Card className="lg:col-span-7">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <CalendarClock className="h-4 w-4 text-emerald-400" />
                                Latest appointments
                            </CardTitle>
                            <CardDescription>
                                Most recent bookings across all salons
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-800 text-left text-[11px] tracking-wider text-neutral-500 uppercase">
                                        <th className="pr-4 pb-2 font-medium">
                                            Salon
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Client
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Service
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            When
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            Status
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recent_bookings.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                No bookings yet.
                                            </td>
                                        </tr>
                                    )}
                                    {recent_bookings.map((booking) => (
                                        <tr
                                            key={booking.reference_code}
                                            className="border-b border-neutral-800/60"
                                        >
                                            <td className="py-2.5 pr-4 font-medium text-white">
                                                {booking.business_name}
                                            </td>
                                            <td className="py-2.5 pr-4 text-neutral-300">
                                                {booking.client_name}
                                            </td>
                                            <td className="py-2.5 pr-4 text-neutral-400">
                                                {booking.service_name}
                                            </td>
                                            <td className="py-2.5 pr-4 text-xs text-neutral-400">
                                                {new Date(
                                                    booking.start_at,
                                                ).toLocaleString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    hour: 'numeric',
                                                    minute: '2-digit',
                                                })}
                                            </td>
                                            <td className="py-2.5 pr-4">
                                                <BookingStatusBadge
                                                    status={booking.status}
                                                />
                                            </td>
                                            <td className="py-2.5 text-right font-mono text-neutral-300">
                                                $
                                                {booking.total_amount.toFixed(
                                                    2,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </div>

                <div className="py-2 text-center text-[11px] text-neutral-500">
                    Appointment handling (check-in, no-show, WhatsApp reminders)
                    is performed by business owners and staff.
                </div>
            </div>
        </AppLayout>
    );
}
