import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Clock,
    MapPin,
    User,
    Check,
    AlertCircle,
    ShieldCheck,
    Sparkles,
    MessageSquare,
    Phone,
    Mail,
} from 'lucide-react';
import { DepositPromptModal } from '@/components/booking/deposit-prompt-modal';
import { WaitlistJoinModal } from '@/components/booking/waitlist-join-modal';
import { login } from '@/routes';

interface ServiceItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
    booking_fee: number;
    category: string | null;
    is_recommended: boolean;
    employee_ids: number[];
}

interface LocationOpeningHour {
    day_of_week: number; // ISO: 1 = Monday .. 7 = Sunday
    opens_at: string;
    closes_at: string;
    is_closed: boolean;
}

interface LocationClosure {
    starts_on: string;
    ends_on: string | null;
    reason: string | null;
}

interface LocationItem {
    id: number;
    name: string;
    address: string;
    city: string;
    is_active: boolean;
    max_capacity: number;
    opens_at: string | null;
    closes_at: string | null;
    employee_ids: number[];
    opening_hours: LocationOpeningHour[];
    upcoming_closures: LocationClosure[];
}

interface EmployeeItem {
    id: number;
    name: string;
    email: string;
    avatar_path: string | null;
    role: string;
}

interface BusinessData {
    id: number;
    name: string;
    slug: string;
    about: string | null;
    city: string;
    country: string;
    timezone: string;
    phone: string | null;
    cancellation_notice: string;
    locations: LocationItem[];
    services: ServiceItem[];
    employees: EmployeeItem[];
}

interface Props {
    business: BusinessData;
}

interface AvailableSlot {
    time: string;
    start_at: string;
    end_at: string;
    label: string;
    seats_left?: number;
}

const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/** ISO weekday (1 = Monday) for a YYYY-MM-DD date string. */
function isoWeekday(dateString: string): number {
    const date = new Date(`${dateString}T00:00:00`);

    return ((date.getDay() + 6) % 7) + 1;
}

/** The closure covering the given date, if any (null ends_on = single day). */
function closureCovering(
    location: LocationItem,
    dateString: string,
): LocationClosure | null {
    return (
        location.upcoming_closures.find((closure) =>
            closure.ends_on
                ? dateString >= closure.starts_on &&
                  dateString <= closure.ends_on
                : dateString === closure.starts_on,
        ) ?? null
    );
}

/**
 * Whether the location is closed on the given date. With a configured weekly
 * schedule a weekday without hours counts as closed; without a schedule the
 * legacy fallback window is assumed to be open.
 */
function isLocationClosedOn(
    location: LocationItem,
    dateString: string,
): boolean {
    if (closureCovering(location, dateString) !== null) {
        return true;
    }

    if (location.opening_hours.length === 0) {
        return false;
    }

    const day = location.opening_hours.find(
        (hour) => hour.day_of_week === isoWeekday(dateString),
    );

    return !day || day.is_closed;
}

/** Compact "Mon: Closed · Tue–Sat: 09:00–18:00" style summary. */
function summarizeHours(location: LocationItem): string {
    if (location.opening_hours.length === 0) {
        return location.opens_at && location.closes_at
            ? `Daily ${location.opens_at}–${location.closes_at}`
            : 'Opening hours confirmed at booking';
    }

    const sorted = [...location.opening_hours].sort(
        (a, b) => a.day_of_week - b.day_of_week,
    );
    const groups: { start: number; end: number; window: string }[] = [];

    for (const hour of sorted) {
        const window = hour.is_closed
            ? 'Closed'
            : `${hour.opens_at}–${hour.closes_at}`;
        const last = groups[groups.length - 1];

        if (
            last &&
            last.window === window &&
            last.end === hour.day_of_week - 1
        ) {
            last.end = hour.day_of_week;
        } else {
            groups.push({
                start: hour.day_of_week,
                end: hour.day_of_week,
                window,
            });
        }
    }

    return groups
        .map((group) => {
            const label =
                group.start === group.end
                    ? DAY_LABELS[group.start - 1]
                    : `${DAY_LABELS[group.start - 1]}–${DAY_LABELS[group.end - 1]}`;

            return `${label}: ${group.window}`;
        })
        .join(' · ');
}

/** Today's live status chip, e.g. "Open until 18:00" or "Closed today". */
function todayChip(
    location: LocationItem,
): { label: string; open: boolean } | null {
    const now = new Date();
    const todayString = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    if (closureCovering(location, todayString) !== null) {
        return { label: 'Closed today', open: false };
    }

    const iso = isoWeekday(todayString);
    const nowTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    if (location.opening_hours.length === 0) {
        const opens = location.opens_at ?? '09:00';
        const closes = location.closes_at ?? '18:00';

        if (nowTime < opens) {
            return { label: `Opens ${opens}`, open: false };
        }

        return nowTime >= closes
            ? { label: 'Closed for today', open: false }
            : { label: `Open until ${closes}`, open: true };
    }

    const day = location.opening_hours.find((hour) => hour.day_of_week === iso);

    if (!day || day.is_closed) {
        return { label: 'Closed today', open: false };
    }

    if (nowTime < day.opens_at) {
        return { label: `Opens ${day.opens_at}`, open: false };
    }

    return nowTime >= day.closes_at
        ? { label: 'Closed for today', open: false }
        : { label: `Open until ${day.closes_at}`, open: true };
}

export default function BookingIndex({ business }: Props) {
    // Bookings are account-bound: the signed-in user is the booker.
    const { auth } = usePage().props;
    const user = auth?.user ?? null;

    // Only locations that accept bookings appear in the picker
    const bookableLocations = business.locations.filter((loc) => loc.is_active);

    // Selection states
    const [selectedService, setSelectedService] = useState<ServiceItem | null>(
        business.services[0] || null,
    );
    const [selectedLocation, setSelectedLocation] =
        useState<LocationItem | null>(
            bookableLocations[0] || business.locations[0] || null,
        );
    const [selectedEmployeeId, setSelectedEmployeeId] = useState<number | null>(
        null,
    );

    // Date & slots state
    const today = new Date().toISOString().split('T')[0];
    const [selectedDate, setSelectedDate] = useState<string>(today);
    const [slots, setSlots] = useState<AvailableSlot[]>([]);
    const [selectedSlot, setSelectedSlot] = useState<AvailableSlot | null>(
        null,
    );
    const [isLoadingSlots, setIsLoadingSlots] = useState(false);
    const [isFullyBooked, setIsFullyBooked] = useState(false);
    const [isLocationClosed, setIsLocationClosed] = useState(false);
    const [closedReason, setClosedReason] = useState<string | null>(null);

    // Client form
    const [clientName, _setClientName] = useState(user?.name ?? '');
    const [clientEmail, _setClientEmail] = useState(user?.email ?? '');
    const [clientPhone, setClientPhone] = useState(
        String((user as { phone?: string | null } | null)?.phone ?? ''),
    );
    const [clientNote, setClientNote] = useState('');

    // Booking & modals
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [depositModalOpen, setDepositModalOpen] = useState(false);
    const [depositInfo, setDepositInfo] = useState<{
        amount: number;
        reason: string;
        riskScore: number;
    } | null>(null);
    const [waitlistModalOpen, setWaitlistModalOpen] = useState(false);

    // Picking a different location drops choices that no longer apply
    const handleLocationChange = (location: LocationItem) => {
        if (selectedLocation?.id === location.id) {
            return;
        }

        setSelectedLocation(location);

        if (
            selectedEmployeeId !== null &&
            !location.employee_ids.includes(selectedEmployeeId)
        ) {
            setSelectedEmployeeId(null);
        }

        setSelectedSlot(null);
    };

    // Only specialists assigned to the chosen location are offerable
    const availableEmployees = business.employees.filter(
        (emp) =>
            selectedLocation === null ||
            selectedLocation.employee_ids.includes(emp.id),
    );

    // Fetch slots when date, service, location, or employee changes
    useEffect(() => {
        if (!selectedService || !selectedLocation) return;

        setIsLoadingSlots(true);
        setSelectedSlot(null);
        setIsLocationClosed(false);
        setClosedReason(null);

        const params = new URLSearchParams({
            business_id: business.id.toString(),
            service_id: selectedService.id.toString(),
            location_id: selectedLocation.id.toString(),
            date: selectedDate,
        });
        if (selectedEmployeeId) {
            params.append('employee_user_id', selectedEmployeeId.toString());
        }

        fetch(`/api/booking/available-slots?${params.toString()}`)
            .then((res) => res.json())
            .then((data) => {
                setIsLocationClosed(Boolean(data.location_closed));
                setClosedReason(data.closed_reason || null);
                setSlots(data.available_slots || []);
                setIsFullyBooked(
                    data.is_fully_booked ||
                        (data.available_slots &&
                            data.available_slots.length === 0),
                );
                if (data.available_slots && data.available_slots.length > 0) {
                    setSelectedSlot(data.available_slots[0]);
                }
            })
            .catch(() => {
                setSlots([]);
                setIsFullyBooked(true);
            })
            .finally(() => {
                setIsLoadingSlots(false);
            });
    }, [
        selectedService?.id,
        selectedLocation?.id,
        selectedEmployeeId,
        selectedDate,
    ]);

    // Handle form submission
    const handleBookingSubmit = async (depositConfirmed: boolean = false) => {
        if (!user) {
            router.get(login().url);
            return;
        }

        if (!selectedService || !selectedLocation || !selectedSlot) {
            setErrorMessage('Please select a service, date, and time slot.');
            return;
        }

        if (!clientPhone) {
            setErrorMessage('Please enter your WhatsApp phone number.');
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            const res = await fetch('/api/booking/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
                body: JSON.stringify({
                    business_id: business.id,
                    location_id: selectedLocation.id,
                    service_id: selectedService.id,
                    employee_user_id: selectedEmployeeId,
                    start_at: selectedSlot.start_at,
                    client_phone: clientPhone,
                    client_note: clientNote,
                    deposit_confirmed: depositConfirmed,
                    deposit_amount: depositInfo?.amount ?? 0,
                }),
            });

            if (res.status === 401) {
                router.get(login().url);
                return;
            }

            const data = await res.json();

            if (res.status === 422 && data.status === 'deposit_required') {
                // High risk detected - open deposit prompt
                setDepositInfo({
                    amount: data.deposit_amount,
                    reason: data.reason,
                    riskScore: data.risk_score,
                });
                setDepositModalOpen(true);
                setIsSubmitting(false);
                return;
            }

            if (res.ok && data.status === 'success') {
                setDepositModalOpen(false);
                router.visit(data.redirect_url);
            } else {
                setErrorMessage(
                    data.message ||
                        'Error completing booking. Please try another slot.',
                );
                setIsSubmitting(false);
            }
        } catch {
            setErrorMessage('Network error occurred. Please try again.');
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950">
            <Head title={`Book Appointment — ${business.name}`} />

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
                                {business.name}
                            </h1>
                            <p className="flex items-center gap-1 text-xs text-neutral-400">
                                <MapPin className="h-3 w-3 text-cyan-400" />
                                {business.city}, {business.country}
                            </p>
                        </div>
                    </div>
                    <div className="hidden items-center gap-2 rounded-full border border-neutral-800 bg-neutral-900/90 px-3.5 py-1.5 text-xs text-neutral-400 sm:flex">
                        <ShieldCheck className="h-3.5 w-3.5 text-emerald-400" />
                        <span>{business.cancellation_notice}</span>
                    </div>
                </div>
            </header>

            {/* Main content grid */}
            <main className="relative mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 py-8 lg:grid-cols-12">
                {/* Left side: Selections (8 cols) */}
                <div className="space-y-8 lg:col-span-7 xl:col-span-8">
                    {/* Step 1: Location */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-semibold tracking-wide text-neutral-400 uppercase">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500/20 text-[10px] font-bold text-cyan-400">
                                    1
                                </span>
                                Choose Location
                            </h2>
                            <span className="text-xs text-neutral-500">
                                {bookableLocations.length} location
                                {bookableLocations.length === 1 ? '' : 's'}{' '}
                                available
                            </span>
                        </div>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {bookableLocations.map((loc) => {
                                const isSelected =
                                    selectedLocation?.id === loc.id;
                                const chip = todayChip(loc);
                                const nextClosure =
                                    loc.upcoming_closures[0] ?? null;
                                const closedOnSelectedDate = isLocationClosedOn(
                                    loc,
                                    selectedDate,
                                );

                                return (
                                    <div
                                        key={loc.id}
                                        onClick={() =>
                                            handleLocationChange(loc)
                                        }
                                        className={`relative cursor-pointer rounded-2xl border p-4 transition-all duration-200 ${
                                            isSelected
                                                ? 'border-cyan-500/80 bg-cyan-950/20 shadow-lg ring-1 shadow-cyan-950/30 ring-cyan-500/50'
                                                : 'border-neutral-800/80 bg-neutral-900/40 hover:border-neutral-700 hover:bg-neutral-900/60'
                                        }`}
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex min-w-0 items-start gap-2">
                                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-cyan-400" />
                                                <div className="min-w-0">
                                                    <h3 className="truncate text-sm font-semibold text-white">
                                                        {loc.name}
                                                    </h3>
                                                    <p className="truncate text-xs text-neutral-400">
                                                        {loc.address} ·{' '}
                                                        {loc.city}
                                                    </p>
                                                </div>
                                            </div>
                                            {isSelected && (
                                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-cyan-500 text-neutral-950">
                                                    <Check className="h-3 w-3" />
                                                </span>
                                            )}
                                        </div>

                                        <div className="mt-3 flex flex-wrap items-center gap-1.5">
                                            {chip && (
                                                <span
                                                    className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold ${
                                                        chip.open
                                                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                                            : 'border-neutral-700 bg-neutral-800/60 text-neutral-400'
                                                    }`}
                                                >
                                                    <Clock className="h-2.5 w-2.5" />
                                                    {chip.label}
                                                </span>
                                            )}
                                            <span className="inline-flex items-center gap-1 rounded-full border border-neutral-700 bg-neutral-800/40 px-2 py-0.5 text-[10px] font-medium text-neutral-300">
                                                <User className="h-2.5 w-2.5" />
                                                {loc.employee_ids.length}{' '}
                                                specialist
                                                {loc.employee_ids.length === 1
                                                    ? ''
                                                    : 's'}
                                            </span>
                                            {closedOnSelectedDate && (
                                                <span className="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-400">
                                                    Closed on selected date
                                                </span>
                                            )}
                                        </div>

                                        <p className="mt-2 text-[11px] leading-relaxed text-neutral-500">
                                            {summarizeHours(loc)}
                                        </p>

                                        {nextClosure && (
                                            <p className="mt-1.5 flex items-start gap-1 text-[11px] text-amber-400/90">
                                                <AlertCircle className="mt-px h-3 w-3 shrink-0" />
                                                <span>
                                                    {nextClosure.ends_on
                                                        ? `Closed ${nextClosure.starts_on} – ${nextClosure.ends_on}`
                                                        : `Closed ${nextClosure.starts_on}`}
                                                    {nextClosure.reason
                                                        ? ` · ${nextClosure.reason}`
                                                        : ''}
                                                </span>
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    {/* Step 2: Services */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-semibold tracking-wide text-neutral-400 uppercase">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500/20 text-[10px] font-bold text-cyan-400">
                                    2
                                </span>
                                Select Service
                            </h2>
                            <span className="text-xs text-neutral-500">
                                {business.services.length} services available
                            </span>
                        </div>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {business.services.map((srv) => {
                                const isSelected =
                                    selectedService?.id === srv.id;
                                return (
                                    <div
                                        key={srv.id}
                                        onClick={() => setSelectedService(srv)}
                                        className={`relative cursor-pointer rounded-2xl border p-4 transition-all duration-200 ${
                                            isSelected
                                                ? 'border-cyan-500/80 bg-cyan-950/20 shadow-lg ring-1 shadow-cyan-950/30 ring-cyan-500/50'
                                                : 'border-neutral-800/80 bg-neutral-900/40 hover:border-neutral-700 hover:bg-neutral-900/60'
                                        }`}
                                    >
                                        {srv.is_recommended && (
                                            <div className="absolute top-3 right-3 flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-400">
                                                <Sparkles className="h-2.5 w-2.5" />{' '}
                                                Popular
                                            </div>
                                        )}
                                        <div className="pr-12">
                                            <h3 className="text-sm font-semibold text-white">
                                                {srv.name}
                                            </h3>
                                            <p className="mt-1 line-clamp-2 text-xs text-neutral-400">
                                                {srv.description}
                                            </p>
                                        </div>
                                        <div className="mt-4 flex items-center justify-between border-t border-neutral-800/60 pt-3 text-xs">
                                            <span className="flex items-center gap-1.5 text-neutral-400">
                                                <Clock className="h-3.5 w-3.5 text-neutral-500" />
                                                {srv.duration_minutes} mins
                                            </span>
                                            <span className="text-sm font-bold text-white">
                                                ${srv.price.toFixed(2)}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    {/* Step 3: Specialist / Staff */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-semibold tracking-wide text-neutral-400 uppercase">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500/20 text-[10px] font-bold text-cyan-400">
                                    3
                                </span>
                                Choose Specialist
                            </h2>
                            <span className="text-xs text-neutral-500">
                                {selectedLocation
                                    ? `At ${selectedLocation.name}`
                                    : 'Any location'}
                            </span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <button
                                type="button"
                                onClick={() => setSelectedEmployeeId(null)}
                                className={`rounded-xl border p-3 text-left transition ${
                                    selectedEmployeeId === null
                                        ? 'border-cyan-500 bg-cyan-950/30 text-white'
                                        : 'border-neutral-800 bg-neutral-900/40 text-neutral-400 hover:border-neutral-700'
                                }`}
                            >
                                <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-800 text-xs font-semibold text-neutral-300">
                                    ★
                                </div>
                                <p className="text-xs font-semibold text-white">
                                    Any Specialist
                                </p>
                                <p className="text-[11px] text-neutral-500">
                                    Fastest Availability
                                </p>
                            </button>

                            {availableEmployees.map((emp) => {
                                const isSelected =
                                    selectedEmployeeId === emp.id;
                                return (
                                    <button
                                        key={emp.id}
                                        type="button"
                                        onClick={() =>
                                            setSelectedEmployeeId(emp.id)
                                        }
                                        className={`rounded-xl border p-3 text-left transition ${
                                            isSelected
                                                ? 'border-cyan-500 bg-cyan-950/30 text-white'
                                                : 'border-neutral-800 bg-neutral-900/40 text-neutral-400 hover:border-neutral-700'
                                        }`}
                                    >
                                        <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-neutral-800 to-neutral-700 text-xs font-bold text-cyan-400">
                                            {emp.name.charAt(0)}
                                        </div>
                                        <p className="truncate text-xs font-semibold text-white">
                                            {emp.name}
                                        </p>
                                        <p className="text-[11px] text-neutral-500">
                                            {emp.role}
                                        </p>
                                    </button>
                                );
                            })}
                        </div>
                    </section>

                    {/* Step 3: Date & Slots */}
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-semibold tracking-wide text-neutral-400 uppercase">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500/20 text-[10px] font-bold text-cyan-400">
                                    4
                                </span>
                                Select Date & Available Slot
                            </h2>
                            <div className="flex items-center gap-2 text-xs text-neutral-400">
                                <Clock className="h-3.5 w-3.5 text-cyan-400" />
                                <span>Includes 10-min turnover buffer</span>
                            </div>
                        </div>

                        {/* Date shortcuts & picker */}
                        <div className="flex items-center gap-2 overflow-x-auto pb-1">
                            {[0, 1, 2, 3, 4, 5].map((dayOffset) => {
                                const dateObj = new Date();
                                dateObj.setDate(dateObj.getDate() + dayOffset);
                                const dateString = dateObj
                                    .toISOString()
                                    .split('T')[0];
                                const isSelected = selectedDate === dateString;
                                const dayName =
                                    dayOffset === 0
                                        ? 'Today'
                                        : dayOffset === 1
                                          ? 'Tomorrow'
                                          : dateObj.toLocaleDateString(
                                                'en-US',
                                                { weekday: 'short' },
                                            );
                                const dayClosed =
                                    selectedLocation !== null &&
                                    isLocationClosedOn(
                                        selectedLocation,
                                        dateString,
                                    );

                                return (
                                    <button
                                        key={dateString}
                                        type="button"
                                        onClick={() =>
                                            setSelectedDate(dateString)
                                        }
                                        className={`shrink-0 rounded-xl border px-3.5 py-2.5 text-center transition ${
                                            isSelected
                                                ? 'border-cyan-500 bg-cyan-500/10 text-white'
                                                : dayClosed
                                                  ? 'border-neutral-800/60 bg-neutral-900/20 text-neutral-600'
                                                  : 'border-neutral-800 bg-neutral-900/40 text-neutral-400 hover:border-neutral-700'
                                        }`}
                                    >
                                        <p className="text-[11px] font-medium text-neutral-400 uppercase">
                                            {dayName}
                                        </p>
                                        <p className="mt-0.5 text-sm font-bold text-white">
                                            {dateObj.getDate()}
                                        </p>
                                        {dayClosed && !isSelected && (
                                            <p className="mt-0.5 text-[9px] font-semibold text-amber-500/80 uppercase">
                                                Closed
                                            </p>
                                        )}
                                    </button>
                                );
                            })}
                            <input
                                type="date"
                                value={selectedDate}
                                min={today}
                                onChange={(e) =>
                                    setSelectedDate(e.target.value)
                                }
                                className="rounded-xl border border-neutral-800 bg-neutral-900/60 px-3 py-2 text-xs text-neutral-300 outline-none focus:border-cyan-500"
                            />
                        </div>

                        {/* Time slots container */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/30 p-5">
                            {isLoadingSlots ? (
                                <div className="py-8 text-center text-xs text-neutral-400">
                                    Checking real-time staff schedules and
                                    buffers...
                                </div>
                            ) : isLocationClosed ? (
                                <div className="space-y-3 py-6 text-center">
                                    <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                                        <AlertCircle className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-white">
                                            {selectedLocation?.name} is closed
                                            on {selectedDate}
                                        </p>
                                        <p className="mx-auto mt-1 max-w-sm text-xs text-neutral-400">
                                            {closedReason ??
                                                'This location is not accepting bookings on this date.'}{' '}
                                            Pick another date, or choose a
                                            different location above.
                                        </p>
                                    </div>
                                </div>
                            ) : isFullyBooked || slots.length === 0 ? (
                                <div className="space-y-3 py-6 text-center">
                                    <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                                        <AlertCircle className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-white">
                                            All Slots Booked for {selectedDate}
                                        </p>
                                        <p className="mx-auto mt-1 max-w-sm text-xs text-neutral-400">
                                            No open appointments available. Join
                                            our automated waitlist to be
                                            instantly notified via WhatsApp if a
                                            cancellation occurs.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setWaitlistModalOpen(true)
                                        }
                                        className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2.5 text-xs font-semibold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110"
                                    >
                                        <Sparkles className="h-3.5 w-3.5" />
                                        Join Priority Waitlist
                                    </button>
                                </div>
                            ) : (
                                <div>
                                    <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5">
                                        {slots.map((slot) => {
                                            const isSelected =
                                                selectedSlot?.start_at ===
                                                slot.start_at;
                                            return (
                                                <button
                                                    key={slot.start_at}
                                                    type="button"
                                                    onClick={() =>
                                                        setSelectedSlot(slot)
                                                    }
                                                    className={`rounded-xl border px-3 py-2 text-xs font-semibold transition ${
                                                        isSelected
                                                            ? 'scale-[1.02] border-cyan-500 bg-cyan-500 text-neutral-950 shadow-md shadow-cyan-500/30'
                                                            : 'border-neutral-800 bg-neutral-900/60 text-neutral-300 hover:border-neutral-700 hover:text-white'
                                                    }`}
                                                >
                                                    {slot.time}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </section>
                </div>

                {/* Right side: Client details & Booking Summary (4 cols) */}
                <div className="space-y-6 lg:col-span-5 xl:col-span-4">
                    <div className="sticky top-20 rounded-2xl border border-neutral-800 bg-neutral-900/70 p-6 shadow-2xl backdrop-blur-md">
                        <h2 className="mb-4 text-base font-bold text-white">
                            Reservation Summary
                        </h2>

                        {/* Selected summary badges */}
                        <div className="space-y-3 border-b border-neutral-800 pb-4 text-xs">
                            <div className="flex items-center justify-between text-neutral-400">
                                <span>Service</span>
                                <span className="font-semibold text-white">
                                    {selectedService?.name}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-neutral-400">
                                <span>Duration</span>
                                <span>
                                    {selectedService?.duration_minutes} minutes
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-neutral-400">
                                <span>Date</span>
                                <span className="font-medium text-cyan-400">
                                    {selectedDate}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-neutral-400">
                                <span>Time Slot</span>
                                <span className="font-medium text-cyan-400">
                                    {selectedSlot?.time ?? 'Select a slot'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-neutral-400">
                                <span>Location</span>
                                <span className="max-w-[160px] truncate text-neutral-300">
                                    {selectedLocation?.name}
                                    {selectedLocation?.city
                                        ? ` · ${selectedLocation.city}`
                                        : ''}
                                </span>
                            </div>
                        </div>

                        {/* Pricing */}
                        <div className="space-y-2 border-b border-neutral-800 py-4 text-xs">
                            <div className="flex justify-between text-neutral-400">
                                <span>Service Price</span>
                                <span>
                                    ${selectedService?.price.toFixed(2)}
                                </span>
                            </div>
                            <div className="flex justify-between text-neutral-400">
                                <span>Booking Fee</span>
                                <span>
                                    $
                                    {(
                                        selectedService?.booking_fee ?? 0
                                    ).toFixed(2)}
                                </span>
                            </div>
                            <div className="flex justify-between pt-1 text-sm font-bold text-white">
                                <span>Total at Checkout</span>
                                <span className="text-cyan-400">
                                    $
                                    {(
                                        (selectedService?.price ?? 0) +
                                        (selectedService?.booking_fee ?? 0)
                                    ).toFixed(2)}
                                </span>
                            </div>
                        </div>

                        {/* Client details form */}
                        <div className="space-y-3 pt-4">
                            <h3 className="text-xs font-semibold tracking-wider text-neutral-300 uppercase">
                                {user ? 'Your Information' : 'Sign in to Book'}
                            </h3>

                            {errorMessage && (
                                <div className="flex items-start gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                    <span>{errorMessage}</span>
                                </div>
                            )}

                            {user ? (
                                <>
                                    <div className="space-y-2 rounded-xl border border-neutral-800 bg-neutral-900/60 p-3 text-xs">
                                        <div className="flex items-center gap-2 text-neutral-200">
                                            <User className="h-4 w-4 shrink-0 text-neutral-500" />
                                            <span className="font-medium">
                                                {clientName}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-neutral-400">
                                            <Mail className="h-4 w-4 shrink-0 text-neutral-500" />
                                            <span>{clientEmail}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-1 block text-[11px] font-medium text-neutral-400">
                                            WhatsApp Phone Number
                                        </label>
                                        <div className="flex items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900/90 px-3 py-2 text-xs text-neutral-200 focus-within:border-cyan-500">
                                            <Phone className="h-4 w-4 shrink-0 text-emerald-400" />
                                            <input
                                                type="tel"
                                                required
                                                value={clientPhone}
                                                onChange={(e) =>
                                                    setClientPhone(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="+351 912 345 678"
                                                className="w-full bg-transparent outline-none"
                                            />
                                        </div>
                                        <p className="mt-1 flex items-center gap-1 text-[10px] text-neutral-500">
                                            <MessageSquare className="h-3 w-3 text-emerald-400" />
                                            We send 48h/24h WhatsApp
                                            confirmation with 1-tap reschedule.
                                        </p>
                                    </div>

                                    <div>
                                        <label className="mb-1 block text-[11px] font-medium text-neutral-400">
                                            Optional Notes
                                        </label>
                                        <textarea
                                            value={clientNote}
                                            onChange={(e) =>
                                                setClientNote(e.target.value)
                                            }
                                            placeholder="Any specific requests or preferences..."
                                            rows={2}
                                            className="w-full resize-none rounded-xl border border-neutral-800 bg-neutral-900/90 p-2.5 text-xs text-neutral-200 outline-none focus:border-cyan-500"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        disabled={isSubmitting || !selectedSlot}
                                        onClick={() =>
                                            handleBookingSubmit(false)
                                        }
                                        className="mt-2 w-full rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 py-3 text-xs font-bold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40"
                                    >
                                        {isSubmitting
                                            ? 'Confirming Appointment...'
                                            : 'Confirm Appointment'}
                                    </button>
                                </>
                            ) : (
                                <>
                                    <div className="rounded-xl border border-neutral-800 bg-neutral-900/60 p-3 text-xs leading-relaxed text-neutral-400">
                                        Bookings are linked to your SlotSaver
                                        account so you can track visits, spend
                                        and reviews in one place. Sign in (or
                                        create an account) to continue.
                                    </div>

                                    <button
                                        type="button"
                                        disabled={isSubmitting}
                                        onClick={() => router.get(login().url)}
                                        className="mt-2 w-full rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 py-3 text-xs font-bold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40"
                                    >
                                        Sign in to Book
                                    </button>
                                </>
                            )}

                            <p className="text-center text-[10px] text-neutral-500">
                                By booking you agree to{' '}
                                {business.cancellation_notice}
                            </p>
                        </div>
                    </div>
                </div>
            </main>

            {/* Deposit Prompt Modal */}
            {depositInfo && (
                <DepositPromptModal
                    isOpen={depositModalOpen}
                    onClose={() => setDepositModalOpen(false)}
                    onConfirmDeposit={() => handleBookingSubmit(true)}
                    depositAmount={depositInfo.amount}
                    servicePrice={selectedService?.price ?? 0}
                    serviceName={selectedService?.name ?? 'Selected Service'}
                    riskScore={depositInfo.riskScore}
                    riskReason={depositInfo.reason}
                />
            )}

            {/* Waitlist Join Modal */}
            {selectedService && (
                <WaitlistJoinModal
                    isOpen={waitlistModalOpen}
                    onClose={() => setWaitlistModalOpen(false)}
                    businessId={business.id}
                    serviceId={selectedService.id}
                    serviceName={selectedService.name}
                    preferredDate={selectedDate}
                    employeeId={selectedEmployeeId}
                />
            )}
        </div>
    );
}
