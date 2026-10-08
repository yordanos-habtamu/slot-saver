import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Building2, Clock, MapPin, Sparkles } from 'lucide-react';
import booking from '@/routes/booking';

interface BrowseService {
    id: number;
    name: string;
    price: number;
    duration_minutes: number;
    category: string | null;
    is_recommended: boolean;
}

interface BrowseBusiness {
    id: number;
    name: string;
    slug: string;
    about: string | null;
    city: string;
    country: string;
    phone: string | null;
    location_count: number;
    services: BrowseService[];
}

interface BrowseProps {
    businesses: BrowseBusiness[];
}

export default function BookingBrowse({ businesses }: BrowseProps) {
    return (
        <div className="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950">
            <Head title="Book an Appointment — SlotSaver" />

            {/* Glowing aesthetic backdrop */}
            <div className="pointer-events-none fixed inset-0 overflow-hidden">
                <div className="absolute top-0 left-1/3 h-96 w-96 rounded-full bg-cyan-500/10 blur-[120px]" />
                <div className="absolute top-1/2 right-1/4 h-96 w-96 rounded-full bg-indigo-500/10 blur-[140px]" />
            </div>

            {/* Header */}
            <header className="relative sticky top-0 z-30 border-b border-neutral-800/80 bg-neutral-900/40 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 font-bold text-neutral-950 shadow-md shadow-cyan-500/20">
                            SS
                        </div>
                        <div>
                            <h1 className="text-base font-bold tracking-tight text-white">
                                SlotSaver
                            </h1>
                            <p className="text-xs text-neutral-400">
                                Find a salon and book in under a minute
                            </p>
                        </div>
                    </div>
                    <span className="hidden rounded-full border border-neutral-800 bg-neutral-900/90 px-3.5 py-1.5 text-xs text-neutral-400 sm:inline-flex sm:items-center sm:gap-1.5">
                        <Building2 className="h-3.5 w-3.5 text-cyan-400" />
                        {businesses.length} salon
                        {businesses.length === 1 ? '' : 's'}
                    </span>
                </div>
            </header>

            {/* Hero */}
            <main className="relative mx-auto max-w-6xl px-4 py-10">
                <section className="max-w-2xl">
                    <h2 className="text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                        Book your next appointment
                    </h2>
                    <p className="mt-2 text-sm text-neutral-400">
                        Choose a salon below to view its services, specialists
                        and live availability — then reserve your slot with
                        automated WhatsApp reminders.
                    </p>
                </section>

                {/* Directory */}
                <section className="mt-10">
                    {businesses.length === 0 && (
                        <div className="rounded-2xl border border-neutral-800 bg-neutral-900/40 p-12 text-center text-sm text-neutral-400">
                            No salons are accepting bookings yet. Please check
                            back soon.
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {businesses.map((business) => (
                            <div
                                key={business.id}
                                className="flex flex-col rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-5 shadow-lg backdrop-blur-md transition hover:border-neutral-700"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex min-w-0 items-start gap-2.5">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-neutral-800 to-neutral-700 text-sm font-bold text-cyan-400">
                                            {business.name.charAt(0)}
                                        </div>
                                        <div className="min-w-0">
                                            <h3 className="truncate text-sm font-bold text-white">
                                                {business.name}
                                            </h3>
                                            <p className="flex items-center gap-1 text-xs text-neutral-400">
                                                <MapPin className="h-3 w-3 shrink-0 text-cyan-400" />
                                                <span className="truncate">
                                                    {business.city},{' '}
                                                    {business.country}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <p className="mt-3 line-clamp-2 text-xs leading-relaxed text-neutral-400">
                                    {business.about}
                                </p>

                                <div className="mt-3 flex items-center gap-1.5">
                                    <span className="inline-flex items-center gap-1 rounded-full border border-neutral-800 bg-neutral-900/60 px-2 py-0.5 text-[10px] font-medium text-neutral-300">
                                        <Building2 className="h-2.5 w-2.5 text-neutral-500" />
                                        {business.location_count} location
                                        {business.location_count === 1
                                            ? ''
                                            : 's'}
                                    </span>
                                    <span className="inline-flex items-center gap-1 rounded-full border border-neutral-800 bg-neutral-900/60 px-2 py-0.5 text-[10px] font-medium text-neutral-300">
                                        <Sparkles className="h-2.5 w-2.5 text-neutral-500" />
                                        {business.services.length} services
                                    </span>
                                </div>

                                {/* Services preview */}
                                <div className="mt-4 space-y-2 border-t border-neutral-800/70 pt-3">
                                    {business.services
                                        .slice(0, 5)
                                        .map((srv) => (
                                            <div
                                                key={srv.id}
                                                className="flex items-center justify-between gap-3 text-xs"
                                            >
                                                <div className="flex min-w-0 items-center gap-2">
                                                    {srv.is_recommended && (
                                                        <Sparkles className="h-3 w-3 shrink-0 text-amber-400" />
                                                    )}
                                                    <span className="truncate text-neutral-300">
                                                        {srv.name}
                                                    </span>
                                                </div>
                                                <div className="flex shrink-0 items-center gap-2 text-neutral-400">
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="h-3 w-3 text-neutral-500" />
                                                        {srv.duration_minutes}m
                                                    </span>
                                                    <span className="font-semibold text-white">
                                                        ${srv.price.toFixed(2)}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    {business.services.length === 0 && (
                                        <p className="text-xs text-neutral-500">
                                            Services coming soon.
                                        </p>
                                    )}
                                </div>

                                <div className="mt-auto pt-4">
                                    <Link
                                        href={
                                            booking.index({
                                                slug: business.slug,
                                            }).url
                                        }
                                        className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-4 py-2.5 text-xs font-semibold text-neutral-950 shadow-md shadow-cyan-500/20 transition hover:brightness-110 active:scale-[0.99]"
                                    >
                                        View services &amp; book
                                        <ArrowRight className="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </main>
        </div>
    );
}
