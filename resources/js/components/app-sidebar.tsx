import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    CalendarDays,
    FolderGit2,
    LayoutGrid,
    Scissors,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import adminBusinesses from '@/routes/admin/businesses';
import { index as bookIndex } from '@/routes/booking';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Businesses',
        href: adminBusinesses.index(),
        icon: Building2,
    },
];

const employeeNavItems: NavItem[] = [
    {
        title: 'My Schedule',
        href: dashboard(),
        icon: CalendarDays,
    },
];

const clientNavItems: NavItem[] = [
    {
        title: 'My Appointments',
        href: dashboard(),
        icon: CalendarDays,
    },
    {
        title: 'Book a Visit',
        href: bookIndex(),
        icon: Scissors,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const items: NavItem[] =
        auth.user?.role === 'admin'
            ? [...mainNavItems, ...adminNavItems]
            : auth.user?.role === 'employee'
              ? employeeNavItems
              : auth.user?.role === 'client'
                ? clientNavItems
                : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
