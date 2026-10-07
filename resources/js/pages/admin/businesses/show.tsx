import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types/navigation';
import adminBusinesses from '@/routes/admin/businesses';
import { Head, Link } from '@inertiajs/react';
import type { BusinessSummary } from '@/types/domain';

type BusinessDetail = {
    id: number;
    name: string;
    slug: string;
    email: string;
    phone: string | null;
    website: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    timezone: string;
    status: BusinessSummary['status'];
    created_at: string;
    business_type: { id: number; name: string } | null;
    owner: { id: number; name: string; email: string } | null;
};

type WeeklyPoint = {
    week_start: string;
    total: number;
    completed: number;
    cancelled: number;
    no_show: number;
};

type Analytics = {
    period_weeks: number;
    totals: {
        bookings: number;
        completed: number;
        cancelled: number;
        no_show: number;
        confirmed: number;
        pending: number;
        revenue: number;
        cancellation_fees: number;
        waitlist_refills: number;
        no_show_rate: number;
        average_ticket: number;
    };
    weekly: WeeklyPoint[];
    top_services: Array<{
        service_id: number;
        name: string;
        bookings_count: number;
        revenue: number;
    }>;
    recent_bookings: Array<{
        reference_code: string;
        client_name: string;
        service_name: string;
        location_name: string | null;
        start_at: string;
        status: string;
        total_amount: number;
    }>;
    recent_reviews: Array<{
        rating: number;
        title: string | null;
        body: string | null;
        client_name: string;
        created_at: string;
        would_recommend: boolean;
    }>;
    rating: {
        average: number | null;
        count: number;
    };
};

type LocationRow = {
    id: number;
    name: string;
    city: string | null;
    address: string;
    is_active: boolean;
    bookings_count: number;
    employees_count: number;
};

type PageProps = {
    business: BusinessDetail;
    analytics: Analytics;
    locations: LocationRow[];
};

function currency(amount: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function KpiCard({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-2xl">{value}</CardTitle>
            </CardHeader>
            {hint && (
                <CardContent className="pt-0 text-xs text-muted-foreground">
                    {hint}
                </CardContent>
            )}
        </Card>
    );
}

function WeeklyChart({ weekly }: { weekly: WeeklyPoint[] }) {
    const width = 720;
    const height = 200;
    const baseline = height - 24;
    const max = Math.max(1, ...weekly.map((point) => point.total));
    const slot = weekly.length > 0 ? width / weekly.length : width;
    const barWidth = Math.max(3, slot - 4);
    const scale = (value: number) => (value / max) * (baseline - 16);

    return (
        <div className="space-y-2">
            <svg
                viewBox={`0 0 ${width} ${height}`}
                className="h-52 w-full"
                role="img"
                aria-label={`Bookings per week over the last ${weekly.length} weeks`}
            >
                <line
                    x1="0"
                    y1={baseline}
                    x2={width}
                    y2={baseline}
                    className="stroke-border"
                />
                <line
                    x1="0"
                    y1={baseline - scale(max)}
                    x2={width}
                    y2={baseline - scale(max)}
                    className="stroke-border"
                    strokeDasharray="4 4"
                />
                <text
                    x="2"
                    y={baseline - scale(max) - 4}
                    className="fill-muted-foreground text-[10px]"
                >
                    {max}
                </text>
                {weekly.map((point, index) => {
                    const x = index * slot + 2;
                    const totalHeight = scale(point.total);
                    const completedHeight = scale(point.completed);
                    const noShowHeight = scale(point.no_show);
                    const cancelledHeight = scale(point.cancelled);
                    const confirmedHeight = Math.max(
                        0,
                        totalHeight -
                            completedHeight -
                            noShowHeight -
                            cancelledHeight,
                    );
                    let cursorY = baseline;

                    const segments = [
                        { height: completedHeight, className: 'fill-primary' },
                        {
                            height: confirmedHeight,
                            className: 'fill-primary/30',
                        },
                        {
                            height: cancelledHeight,
                            className: 'fill-muted-foreground/40',
                        },
                        { height: noShowHeight, className: 'fill-destructive' },
                    ];

                    return (
                        <g key={point.week_start}>
                            {segments.map((segment, segmentIndex) => {
                                if (segment.height <= 0) return null;
                                cursorY -= segment.height;

                                return (
                                    <rect
                                        key={segmentIndex}
                                        x={x}
                                        y={cursorY}
                                        width={barWidth}
                                        height={segment.height}
                                        className={segment.className}
                                    />
                                );
                            })}
                        </g>
                    );
                })}
                {weekly.map((point, index) =>
                    index % Math.max(1, Math.ceil(weekly.length / 8)) === 0 ? (
                        <text
                            key={`label-${point.week_start}`}
                            x={index * slot + slot / 2}
                            y={height - 6}
                            textAnchor="middle"
                            className="fill-muted-foreground text-[9px]"
                        >
                            {point.week_start.slice(5)}
                        </text>
                    ) : null,
                )}
            </svg>
            <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-primary" />{' '}
                    Completed
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-primary/30" />{' '}
                    Scheduled
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-muted-foreground/40" />{' '}
                    Cancelled
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-destructive" />{' '}
                    No-show
                </span>
            </div>
        </div>
    );
}

function StatusBreakdown({ totals }: { totals: Analytics['totals'] }) {
    const segments = [
        {
            label: 'Completed',
            value: totals.completed,
            className: 'bg-primary',
        },
        {
            label: 'Confirmed',
            value: totals.confirmed,
            className: 'bg-primary/30',
        },
        {
            label: 'Pending',
            value: totals.pending,
            className: 'bg-secondary',
        },
        {
            label: 'Cancelled',
            value: totals.cancelled,
            className: 'bg-muted-foreground/40',
        },
        {
            label: 'No-show',
            value: totals.no_show,
            className: 'bg-destructive',
        },
    ];
    const total = Math.max(1, totals.bookings);

    return (
        <div className="space-y-3">
            <div className="flex h-3 w-full overflow-hidden rounded-full bg-muted">
                {segments
                    .filter((segment) => segment.value > 0)
                    .map((segment) => (
                        <div
                            key={segment.label}
                            className={segment.className}
                            style={{
                                width: `${(segment.value / total) * 100}%`,
                            }}
                            title={`${segment.label}: ${segment.value}`}
                        />
                    ))}
            </div>
            <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {segments.map((segment) => (
                    <span
                        key={segment.label}
                        className="flex items-center gap-1.5"
                    >
                        <span
                            className={`size-2.5 rounded-sm ${segment.className}`}
                        />
                        {segment.label} ({segment.value})
                    </span>
                ))}
            </div>
        </div>
    );
}

export default function BusinessShow({
    business,
    analytics,
    locations,
}: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Businesses',
            href: adminBusinesses.index().url,
        },
        {
            title: business.name,
            href: `/admin/businesses/${business.id}`,
        },
    ];
    const { totals, rating } = analytics;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${business.name} · Business`} />
            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-3">
                    <Link href={adminBusinesses.index().url}>
                        <Button variant="ghost" size="sm">
                            ← Back to businesses
                        </Button>
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Heading
                            title={business.name}
                            description={[
                                business.business_type?.name,
                                business.city,
                                business.country,
                            ]
                                .filter(Boolean)
                                .join(' • ')}
                        />
                        <Badge
                            variant={
                                business.status === 'active'
                                    ? 'default'
                                    : business.status === 'pending'
                                      ? 'secondary'
                                      : business.status === 'suspended'
                                        ? 'destructive'
                                        : 'outline'
                            }
                        >
                            {business.status}
                        </Badge>
                    </div>
                    <dl className="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <dt className="font-medium text-muted-foreground">
                                Owner
                            </dt>
                            <dd>
                                {business.owner
                                    ? `${business.owner.name} (${business.owner.email})`
                                    : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium text-muted-foreground">
                                Contact
                            </dt>
                            <dd>
                                {[business.email, business.phone]
                                    .filter(Boolean)
                                    .join(' • ')}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium text-muted-foreground">
                                Registered
                            </dt>
                            <dd>{formatDate(business.created_at)}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-muted-foreground">
                                Timezone
                            </dt>
                            <dd>{business.timezone}</dd>
                        </div>
                    </dl>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <KpiCard
                        label="Bookings"
                        value={String(totals.bookings)}
                        hint={`${totals.completed} completed`}
                    />
                    <KpiCard
                        label="Revenue"
                        value={currency(totals.revenue)}
                        hint={`${currency(totals.cancellation_fees)} in fees`}
                    />
                    <KpiCard
                        label="Avg. ticket"
                        value={currency(totals.average_ticket)}
                        hint="Completed appointments"
                    />
                    <KpiCard
                        label="No-show rate"
                        value={`${totals.no_show_rate}%`}
                        hint={`${totals.no_show} of ${totals.bookings}`}
                    />
                    <KpiCard
                        label="Waitlist refills"
                        value={String(totals.waitlist_refills)}
                    />
                    <KpiCard
                        label="Rating"
                        value={
                            rating.average !== null
                                ? `★ ${rating.average.toFixed(1)}`
                                : '—'
                        }
                        hint={`${rating.count} published reviews`}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Bookings per week</CardTitle>
                            <CardDescription>
                                Last {analytics.period_weeks} weeks · stacked by
                                outcome
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WeeklyChart weekly={analytics.weekly} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Status breakdown</CardTitle>
                            <CardDescription>
                                All-time booking outcomes
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <StatusBreakdown totals={totals} />
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Top services</CardTitle>
                            <CardDescription>By booking volume</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {analytics.top_services.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No bookings yet.
                                </p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="pb-2 font-medium">
                                                Service
                                            </th>
                                            <th className="pb-2 text-right font-medium">
                                                Bookings
                                            </th>
                                            <th className="pb-2 text-right font-medium">
                                                Revenue
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {analytics.top_services.map(
                                            (service) => (
                                                <tr
                                                    key={service.service_id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-2">
                                                        {service.name}
                                                    </td>
                                                    <td className="py-2 text-right">
                                                        {service.bookings_count}
                                                    </td>
                                                    <td className="py-2 text-right">
                                                        {currency(
                                                            service.revenue,
                                                        )}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Locations</CardTitle>
                            <CardDescription>
                                {locations.length} location
                                {locations.length === 1 ? '' : 's'} on file
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {locations.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No locations yet.
                                </p>
                            )}
                            {locations.map((location) => (
                                <div
                                    key={location.id}
                                    className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {location.name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {[location.address, location.city]
                                                .filter(Boolean)
                                                .join(', ')}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {!location.is_active && (
                                            <Badge variant="outline">
                                                inactive
                                            </Badge>
                                        )}
                                        <span className="text-xs text-muted-foreground">
                                            {location.bookings_count} bookings
                                            <br />
                                            {location.employees_count} staff
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent bookings</CardTitle>
                        <CardDescription>
                            Latest 10 appointments on record
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {analytics.recent_bookings.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No bookings yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="pb-2 font-medium">
                                                Reference
                                            </th>
                                            <th className="pb-2 font-medium">
                                                Client
                                            </th>
                                            <th className="pb-2 font-medium">
                                                Service
                                            </th>
                                            <th className="pb-2 font-medium">
                                                When
                                            </th>
                                            <th className="pb-2 font-medium">
                                                Status
                                            </th>
                                            <th className="pb-2 text-right font-medium">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {analytics.recent_bookings.map(
                                            (booking) => (
                                                <tr
                                                    key={booking.reference_code}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-2 font-mono text-xs">
                                                        {booking.reference_code}
                                                    </td>
                                                    <td className="py-2">
                                                        {booking.client_name}
                                                    </td>
                                                    <td className="py-2">
                                                        {booking.service_name}
                                                        {booking.location_name && (
                                                            <span className="text-muted-foreground">
                                                                {' '}
                                                                ·{' '}
                                                                {
                                                                    booking.location_name
                                                                }
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-2">
                                                        {formatDateTime(
                                                            booking.start_at,
                                                        )}
                                                    </td>
                                                    <td className="py-2">
                                                        <Badge
                                                            variant={
                                                                booking.status ===
                                                                'completed'
                                                                    ? 'default'
                                                                    : booking.status ===
                                                                        'cancelled'
                                                                      ? 'secondary'
                                                                      : booking.status ===
                                                                          'no_show'
                                                                        ? 'destructive'
                                                                        : 'outline'
                                                            }
                                                        >
                                                            {booking.status}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-2 text-right">
                                                        {currency(
                                                            booking.total_amount,
                                                        )}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent reviews</CardTitle>
                        <CardDescription>
                            Latest published client feedback
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {analytics.recent_reviews.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No published reviews yet.
                            </p>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2">
                                {analytics.recent_reviews.map(
                                    (review, index) => (
                                        <div
                                            key={index}
                                            className="rounded-md border p-3 text-sm"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium">
                                                    {'★'.repeat(review.rating)}
                                                    <span className="text-muted-foreground">
                                                        {'★'.repeat(
                                                            5 - review.rating,
                                                        )}
                                                    </span>
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDate(
                                                        review.created_at,
                                                    )}
                                                </span>
                                            </div>
                                            {review.title && (
                                                <p className="mt-1 font-medium">
                                                    {review.title}
                                                </p>
                                            )}
                                            {review.body && (
                                                <p className="mt-1 text-muted-foreground">
                                                    {review.body}
                                                </p>
                                            )}
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                {review.client_name}
                                            </p>
                                        </div>
                                    ),
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
