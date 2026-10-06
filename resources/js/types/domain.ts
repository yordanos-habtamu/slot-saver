export type BusinessTypeOption = {
    id: number;
    name: string;
    description: string | null;
    serviceCount: number;
};

export type BusinessSummary = {
    id: number;
    name: string;
    slug: string;
    contact_email: string | null;
    contact_phone: string | null;
    status: 'active' | 'inactive' | 'pending';
    created_at: string;
    updated_at: string;
    business_type?: {
        id: number;
        name: string;
    } | null;
    owner?: {
        id: number;
        name: string;
        email: string;
    } | null;
    services_count?: number;
};

export type PaginatedBusinesses = {
    data: BusinessSummary[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
