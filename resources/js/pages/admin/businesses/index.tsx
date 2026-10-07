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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    SelectField,
    TextAreaField,
    TextField,
} from '@/components/form-fields';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types/navigation';
import adminBusinesses from '@/routes/admin/businesses';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import type {
    BusinessFilters,
    BusinessSummary,
    BusinessTypeOption,
    PaginatedBusinesses,
} from '@/types/domain';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Businesses',
        href: adminBusinesses.index().url,
    },
];

type PageProps = {
    businesses: PaginatedBusinesses;
    businessTypes: BusinessTypeOption[];
    filters: BusinessFilters;
};

type FilterState = {
    search: string;
    status: string;
    typeId: string;
};

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'active', label: 'Active' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'archived', label: 'Archived' },
];

function StatusBadge({ status }: { status: BusinessSummary['status'] }) {
    const variant =
        status === 'active'
            ? 'default'
            : status === 'pending'
              ? 'secondary'
              : status === 'suspended'
                ? 'destructive'
                : 'outline';

    return <Badge variant={variant}>{status}</Badge>;
}

function pageLabel(label: string): string {
    if (label.includes('Previous')) return '← Prev';
    if (label.includes('Next')) return 'Next →';
    return label;
}

function RegisterBusinessDialog({
    businessTypes,
    open,
    onOpenChange,
}: {
    businessTypes: BusinessTypeOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        business: {
            business_type_id: '',
            name: '',
            email: '',
            phone: '',
            website: '',
            about: '',
            address_line1: '',
            address_line2: '',
            city: '',
            state: '',
            country: '',
            postal_code: '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
        },
        owner: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        },
        location: {
            name: '',
            address_line1: '',
        },
    });

    const close = () => {
        onOpenChange(false);
        reset();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(adminBusinesses.store().url, {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <Dialog open={open} onOpenChange={(next) => (next ? null : close())}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Register business</DialogTitle>
                    <DialogDescription>
                        Creates the owner account, the business profile and a
                        first location in one step.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-6">
                    <section className="space-y-4">
                        <h3 className="text-sm font-semibold">
                            Business profile
                        </h3>
                        <SelectField
                            id="business_type_id"
                            label="Business type"
                            value={data.business.business_type_id}
                            onChange={(value) =>
                                setData('business.business_type_id', value)
                            }
                            error={errors['business.business_type_id']}
                            placeholder="Select a type"
                            options={businessTypes.map((type) => ({
                                value: String(type.id),
                                label: type.name,
                            }))}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                id="business_name"
                                label="Name"
                                required
                                value={data.business.name}
                                onChange={(value) =>
                                    setData('business.name', value)
                                }
                                error={errors['business.name']}
                            />
                            <TextField
                                id="business_email"
                                label="Contact email"
                                type="email"
                                required
                                value={data.business.email}
                                onChange={(value) =>
                                    setData('business.email', value)
                                }
                                error={errors['business.email']}
                            />
                            <TextField
                                id="business_phone"
                                label="Phone"
                                value={data.business.phone}
                                onChange={(value) =>
                                    setData('business.phone', value)
                                }
                                error={errors['business.phone']}
                            />
                            <TextField
                                id="business_website"
                                label="Website"
                                value={data.business.website}
                                onChange={(value) =>
                                    setData('business.website', value)
                                }
                                error={errors['business.website']}
                            />
                            <TextField
                                id="business_address_line1"
                                label="Address"
                                required
                                value={data.business.address_line1}
                                onChange={(value) =>
                                    setData('business.address_line1', value)
                                }
                                error={errors['business.address_line1']}
                            />
                            <TextField
                                id="business_address_line2"
                                label="Address line 2"
                                value={data.business.address_line2}
                                onChange={(value) =>
                                    setData('business.address_line2', value)
                                }
                                error={errors['business.address_line2']}
                            />
                            <TextField
                                id="business_city"
                                label="City"
                                required
                                value={data.business.city}
                                onChange={(value) =>
                                    setData('business.city', value)
                                }
                                error={errors['business.city']}
                            />
                            <TextField
                                id="business_state"
                                label="State / region"
                                value={data.business.state}
                                onChange={(value) =>
                                    setData('business.state', value)
                                }
                                error={errors['business.state']}
                            />
                            <TextField
                                id="business_country"
                                label="Country"
                                required
                                value={data.business.country}
                                onChange={(value) =>
                                    setData('business.country', value)
                                }
                                error={errors['business.country']}
                            />
                            <TextField
                                id="business_postal_code"
                                label="Postal code"
                                value={data.business.postal_code}
                                onChange={(value) =>
                                    setData('business.postal_code', value)
                                }
                                error={errors['business.postal_code']}
                            />
                            <TextField
                                id="business_timezone"
                                label="Timezone"
                                required
                                value={data.business.timezone}
                                onChange={(value) =>
                                    setData('business.timezone', value)
                                }
                                error={errors['business.timezone']}
                            />
                        </div>
                        <TextAreaField
                            id="business_about"
                            label="About"
                            rows={3}
                            value={data.business.about}
                            onChange={(value) =>
                                setData('business.about', value)
                            }
                            error={errors['business.about']}
                        />
                    </section>

                    <section className="space-y-4 border-t pt-4">
                        <h3 className="text-sm font-semibold">Owner account</h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                id="owner_name"
                                label="Full name"
                                required
                                value={data.owner.name}
                                onChange={(value) =>
                                    setData('owner.name', value)
                                }
                                error={errors['owner.name']}
                            />
                            <TextField
                                id="owner_email"
                                label="Email"
                                type="email"
                                required
                                value={data.owner.email}
                                onChange={(value) =>
                                    setData('owner.email', value)
                                }
                                error={errors['owner.email']}
                            />
                            <TextField
                                id="owner_password"
                                label="Password"
                                type="password"
                                required
                                autoComplete="new-password"
                                hint="Minimum 8 characters."
                                value={data.owner.password}
                                onChange={(value) =>
                                    setData('owner.password', value)
                                }
                                error={errors['owner.password']}
                            />
                            <TextField
                                id="owner_password_confirmation"
                                label="Confirm password"
                                type="password"
                                required
                                autoComplete="new-password"
                                value={data.owner.password_confirmation}
                                onChange={(value) =>
                                    setData(
                                        'owner.password_confirmation',
                                        value,
                                    )
                                }
                                error={errors['owner.password_confirmation']}
                            />
                        </div>
                    </section>

                    <section className="space-y-4 border-t pt-4">
                        <h3 className="text-sm font-semibold">
                            First location
                        </h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                id="location_name"
                                label="Location name"
                                value={data.location.name}
                                onChange={(value) =>
                                    setData('location.name', value)
                                }
                                error={errors['location.name']}
                            />
                            <TextField
                                id="location_address_line1"
                                label="Address"
                                value={data.location.address_line1}
                                onChange={(value) =>
                                    setData('location.address_line1', value)
                                }
                                error={errors['location.address_line1']}
                            />
                        </div>
                    </section>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={close}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Registering…' : 'Register business'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function BusinessesIndex({
    businesses,
    businessTypes,
    filters,
}: PageProps) {
    const [registerOpen, setRegisterOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || 'all');
    const [typeId, setTypeId] = useState(
        filters.business_type_id ? String(filters.business_type_id) : 'all',
    );

    const hasActiveFilters =
        search.trim() !== '' || status !== 'all' || typeId !== 'all';

    const applyFilters = (overrides: Partial<FilterState> = {}) => {
        const nextSearch = (overrides.search ?? search).trim();
        const nextStatus = overrides.status ?? status;
        const nextTypeId = overrides.typeId ?? typeId;

        router.get(
            adminBusinesses.index().url,
            {
                ...(nextSearch !== '' && { search: nextSearch }),
                ...(nextStatus !== 'all' && { status: nextStatus }),
                ...(nextTypeId !== 'all' && { business_type_id: nextTypeId }),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const goPage = (url: string | null) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Businesses" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Businesses"
                        description="Manage businesses registered on the platform"
                    />
                    <Button onClick={() => setRegisterOpen(true)}>
                        Register business
                    </Button>
                </div>

                <Card>
                    <CardContent className="flex flex-col gap-4 pt-6 md:flex-row md:items-end">
                        <form
                            className="flex flex-1 gap-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                applyFilters();
                            }}
                        >
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="business-search">Search</Label>
                                <Input
                                    id="business-search"
                                    placeholder="Name, city, owner…"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                variant="secondary"
                                className="mb-0.5"
                            >
                                Search
                            </Button>
                        </form>
                        <div className="grid gap-4 sm:grid-cols-2 md:w-[24rem]">
                            <SelectField
                                id="status-filter"
                                label="Status"
                                value={status}
                                onChange={(value) => {
                                    setStatus(value);
                                    applyFilters({ status: value });
                                }}
                                options={statusOptions}
                            />
                            <SelectField
                                id="type-filter"
                                label="Type"
                                value={typeId}
                                onChange={(value) => {
                                    setTypeId(value);
                                    applyFilters({ typeId: value });
                                }}
                                options={[
                                    { value: 'all', label: 'All types' },
                                    ...businessTypes.map((type) => ({
                                        value: String(type.id),
                                        label: type.name,
                                    })),
                                ]}
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    {businesses.data.length === 0 && (
                        <Card>
                            <CardContent className="py-8 text-center text-sm text-muted-foreground">
                                {hasActiveFilters
                                    ? 'No businesses match your filters.'
                                    : 'No businesses registered yet.'}
                            </CardContent>
                        </Card>
                    )}
                    {businesses.data.map((business) => (
                        <Card key={business.id}>
                            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardTitle>
                                        <Link
                                            href={`/admin/businesses/${business.id}`}
                                            className="hover:underline"
                                        >
                                            {business.name}
                                        </Link>
                                    </CardTitle>
                                    <CardDescription>
                                        {business.business_type?.name ||
                                            'Uncategorized'}
                                        {business.city
                                            ? ` • ${business.city}`
                                            : ''}
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
                                            {business.email || business.phone
                                                ? [
                                                      business.email,
                                                      business.phone,
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

                {businesses.total > 0 && (
                    <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
                        <p className="text-sm text-muted-foreground">
                            Showing {businesses.from}–{businesses.to} of{' '}
                            {businesses.total}
                        </p>
                        <nav
                            className="flex items-center gap-1"
                            aria-label="Pagination"
                        >
                            {businesses.links.map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={index}
                                        type="button"
                                        size="sm"
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        onClick={() => goPage(link.url)}
                                    >
                                        <span
                                            dangerouslySetInnerHTML={{
                                                __html: pageLabel(link.label),
                                            }}
                                        />
                                    </Button>
                                ) : link.label.includes('Previous') ||
                                  link.label.includes('Next') ? (
                                    <Button
                                        key={index}
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled
                                    >
                                        {pageLabel(link.label)}
                                    </Button>
                                ) : (
                                    <span
                                        key={index}
                                        className="px-2 text-sm text-muted-foreground"
                                    >
                                        …
                                    </span>
                                ),
                            )}
                        </nav>
                    </div>
                )}
            </div>

            <RegisterBusinessDialog
                businessTypes={businessTypes}
                open={registerOpen}
                onOpenChange={setRegisterOpen}
            />
        </AppLayout>
    );
}
