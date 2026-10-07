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
    email: string;
    phone: string | null;
    city: string | null;
    status: 'active' | 'pending' | 'suspended' | 'archived';
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

export type BusinessFilters = {
    search: string;
    status: string;
    business_type_id: number | null;
};

export type PaginatedBusinesses = {
    data: BusinessSummary[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    per_page: number;
    path: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};
