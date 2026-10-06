import Heading from '@/components/heading';
import { TextField, SelectField } from '@/components/form-fields';
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
import type { BusinessTypeOption } from '@/types/domain';
import adminBusinesses from '@/routes/admin/businesses';
import BusinessController from '@/actions/App/Http/Controllers/Admin/BusinessController';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Businesses',
        href: adminBusinesses.index().url,
    },
    {
        title: 'Register business',
        href: adminBusinesses.create().url,
    },
];

type PageProps = {
    businessTypes: BusinessTypeOption[];
};

type FormData = {
    business: {
        business_type_id: string;
        name: string;
        slug: string;
        contact_email: string;
        contact_phone: string;
        website: string;
        description: string;
        status: string;
    };
    owner: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    };
    location: {
        name: string;
        address_line1: string;
        address_line2: string;
        city: string;
        state: string;
        postal_code: string;
        country: string;
        timezone: string;
        contact_phone: string;
        contact_email: string;
        notes: string;
        is_default: boolean;
    };
};

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'pending', label: 'Pending' },
    { value: 'inactive', label: 'Inactive' },
];

export default function BusinessesCreate({ businessTypes }: PageProps) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        business: {
            business_type_id: businessTypes[0]?.id.toString() || '',
            name: '',
            slug: '',
            contact_email: '',
            contact_phone: '',
            website: '',
            description: '',
            status: 'active',
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
            address_line2: '',
            city: '',
            state: '',
            postal_code: '',
            country: '',
            timezone: 'UTC',
            contact_phone: '',
            contact_email: '',
            notes: '',
            is_default: true,
        },
    });

    const businessTypeOptions = businessTypes.map((type) => ({
        value: type.id.toString(),
        label: type.name,
    }));

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(BusinessController.store().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Register business" />
            <div className="space-y-6 p-4">
                <Heading
                    title="Register business"
                    description="Create a business with its owner and first location"
                />
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Business details</CardTitle>
                            <CardDescription>
                                Core information about the business
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <SelectField
                                id="business-business_type_id"
                                label="Business type"
                                value={data.business.business_type_id}
                                onChange={(value) =>
                                    setData('business.business_type_id', value)
                                }
                                options={businessTypeOptions}
                                error={errors['business.business_type_id']}
                            />
                            <TextField
                                id="business-name"
                                label="Business name"
                                value={data.business.name}
                                onChange={(value) =>
                                    setData('business.name', value)
                                }
                                error={errors['business.name']}
                                required
                            />
                            <TextField
                                id="business-slug"
                                label="Slug"
                                value={data.business.slug}
                                onChange={(value) =>
                                    setData('business.slug', value)
                                }
                                error={errors['business.slug']}
                                hint="Used for URLs"
                            />
                            <TextField
                                id="business-contact_email"
                                label="Contact email"
                                type="email"
                                value={data.business.contact_email}
                                onChange={(value) =>
                                    setData('business.contact_email', value)
                                }
                                error={errors['business.contact_email']}
                            />
                            <TextField
                                id="business-contact_phone"
                                label="Contact phone"
                                value={data.business.contact_phone}
                                onChange={(value) =>
                                    setData('business.contact_phone', value)
                                }
                                error={errors['business.contact_phone']}
                            />
                            <TextField
                                id="business-website"
                                label="Website"
                                value={data.business.website}
                                onChange={(value) =>
                                    setData('business.website', value)
                                }
                                error={errors['business.website']}
                            />
                            <TextField
                                id="business-description"
                                label="Description"
                                value={data.business.description}
                                onChange={(value) =>
                                    setData('business.description', value)
                                }
                                error={errors['business.description']}
                            />
                            <SelectField
                                id="business-status"
                                label="Status"
                                value={data.business.status}
                                onChange={(value) =>
                                    setData('business.status', value)
                                }
                                options={statusOptions}
                                error={errors['business.status']}
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Owner account</CardTitle>
                            <CardDescription>
                                Primary account for managing the business
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <TextField
                                id="owner-name"
                                label="Owner name"
                                value={data.owner.name}
                                onChange={(value) =>
                                    setData('owner.name', value)
                                }
                                error={errors['owner.name']}
                                required
                            />
                            <TextField
                                id="owner-email"
                                label="Email address"
                                type="email"
                                value={data.owner.email}
                                onChange={(value) =>
                                    setData('owner.email', value)
                                }
                                error={errors['owner.email']}
                                required
                            />
                            <TextField
                                id="owner-password"
                                label="Password"
                                type="password"
                                value={data.owner.password}
                                onChange={(value) =>
                                    setData('owner.password', value)
                                }
                                error={errors['owner.password']}
                                required
                            />
                            <TextField
                                id="owner-password_confirmation"
                                label="Confirm password"
                                type="password"
                                value={data.owner.password_confirmation}
                                onChange={(value) =>
                                    setData(
                                        'owner.password_confirmation',
                                        value,
                                    )
                                }
                                error={errors['owner.password_confirmation']}
                                required
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>First location</CardTitle>
                            <CardDescription>
                                The initial location for this business
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <TextField
                                id="location-name"
                                label="Location name"
                                value={data.location.name}
                                onChange={(value) =>
                                    setData('location.name', value)
                                }
                                error={errors['location.name']}
                                required
                            />
                            <TextField
                                id="location-address_line1"
                                label="Address line 1"
                                value={data.location.address_line1}
                                onChange={(value) =>
                                    setData('location.address_line1', value)
                                }
                                error={errors['location.address_line1']}
                                required
                            />
                            <TextField
                                id="location-address_line2"
                                label="Address line 2"
                                value={data.location.address_line2}
                                onChange={(value) =>
                                    setData('location.address_line2', value)
                                }
                                error={errors['location.address_line2']}
                            />
                            <TextField
                                id="location-city"
                                label="City"
                                value={data.location.city}
                                onChange={(value) =>
                                    setData('location.city', value)
                                }
                                error={errors['location.city']}
                                required
                            />
                            <TextField
                                id="location-state"
                                label="State/Province"
                                value={data.location.state}
                                onChange={(value) =>
                                    setData('location.state', value)
                                }
                                error={errors['location.state']}
                                required
                            />
                            <TextField
                                id="location-postal_code"
                                label="Postal code"
                                value={data.location.postal_code}
                                onChange={(value) =>
                                    setData('location.postal_code', value)
                                }
                                error={errors['location.postal_code']}
                                required
                            />
                            <TextField
                                id="location-country"
                                label="Country"
                                value={data.location.country}
                                onChange={(value) =>
                                    setData('location.country', value)
                                }
                                error={errors['location.country']}
                                required
                            />
                            <TextField
                                id="location-timezone"
                                label="Timezone"
                                value={data.location.timezone}
                                onChange={(value) =>
                                    setData('location.timezone', value)
                                }
                                error={errors['location.timezone']}
                                required
                            />
                            <TextField
                                id="location-contact_phone"
                                label="Contact phone"
                                value={data.location.contact_phone}
                                onChange={(value) =>
                                    setData('location.contact_phone', value)
                                }
                                error={errors['location.contact_phone']}
                            />
                            <TextField
                                id="location-contact_email"
                                label="Contact email"
                                type="email"
                                value={data.location.contact_email}
                                onChange={(value) =>
                                    setData('location.contact_email', value)
                                }
                                error={errors['location.contact_email']}
                            />
                            <TextField
                                id="location-notes"
                                label="Notes"
                                value={data.location.notes}
                                onChange={(value) =>
                                    setData('location.notes', value)
                                }
                                error={errors['location.notes']}
                            />
                        </CardContent>
                    </Card>
                    <div className="flex items-center justify-end gap-2">
                        <Button asChild variant="outline">
                            <Link href={adminBusinesses.index().url}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Registering...'
                                : 'Register business'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
