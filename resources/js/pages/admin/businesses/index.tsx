import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
import type { BusinessSummary, PaginatedBusinesses } from '@/types/domain';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Businesses',
        href: adminBusinesses.index().url,
    },
];

type PageProps = {
    businesses: PaginatedBusinesses;
};

function StatusBadge({ status }: { status: BusinessSummary['status'] }) {
    const variant =
        status === 'active'
            ? 'default'
            : status === 'pending'
              ? 'secondary'
              : 'destructive';

    return <Badge variant={variant}>{status}</Badge>;
}

export default function BusinessesIndex({ businesses }: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Businesses" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Businesses"
                        description="Manage businesses registered on the platform"
                    />
                    <Link
                        href={adminBusinesses.create().url}
                        className="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        Register business
                    </Link>
                </div>
                <div className="space-y-4">
                    {businesses.data.length === 0 && (
                        <Card>
                            <CardContent className="py-8 text-center text-sm text-muted-foreground">
                                No businesses registered yet.
                            </CardContent>
                        </Card>
                    )}
                    {businesses.data.map((business) => (
                        <Card key={business.id}>
                            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardTitle>{business.name}</CardTitle>
                                    <CardDescription>
                                        {business.business_type?.name ||
                                            'Uncategorized'}
                                    </CardDescription>
                                </div>
                                <StatusBadge status={business.status} />
                            </CardHeader>
                            <CardContent>
                                <dl className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
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
                                            {business.contact_email ||
                                            business.contact_phone
                                                ? [
                                                      business.contact_email,
                                                      business.contact_phone,
                                                  ]
                                                      .filter(Boolean)
                                                      .join(' • ')
                                                : '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="font-medium text-muted-foreground">
                                            Services
                                        </dt>
                                        <dd>{business.services_count ?? 0}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
