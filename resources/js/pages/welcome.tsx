import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Sparkles,
    Calendar,
    Clock,
    ArrowRight,
    CheckCircle2,
    MessageSquare,
    Zap,
    Wifi,
    MapPin,
    Star,
    Calculator,
    Check,
    Scissors,
    Shield,
    Store,
} from 'lucide-react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    // ROI Calculator state
    const [monthlyBookings, setMonthlyBookings] = useState(300);
    const [avgTicketPrice, setAvgTicketPrice] = useState(45);

    // Calculated metrics
    const baselineNoShows = Math.round(monthlyBookings * 0.22);
    const baselineLostRevenue = Math.round(baselineNoShows * avgTicketPrice);
    const slotSaverNoShows = Math.round(monthlyBookings * 0.048);
    const refilledCancellations = Math.round(
        (baselineNoShows - slotSaverNoShows) * 0.75,
    );
    const recoveredRevenue = Math.round(refilledCancellations * avgTicketPrice);
    const netSavings = Math.round(
        recoveredRevenue +
            (baselineNoShows - slotSaverNoShows - refilledCancellations) *
                (avgTicketPrice * 0.5),
    );

    return (
        <div className="min-h-screen overflow-x-hidden bg-neutral-950 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950">
            <Head title="SlotSaver — Eliminate No-Shows. Fill Every Chair." />

            {/* Glowing Ambient Backdrop Lights */}
            <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden">
                <div className="absolute top-0 left-1/4 h-[500px] w-[500px] rounded-full bg-cyan-500/10 blur-[140px]" />
                <div className="absolute top-1/3 right-1/4 h-[600px] w-[600px] rounded-full bg-indigo-500/10 blur-[160px]" />
                <div className="absolute bottom-10 left-1/3 h-[500px] w-[500px] rounded-full bg-emerald-500/10 blur-[150px]" />
            </div>

            {/* Navigation Bar */}
            <header className="relative sticky top-0 z-30 border-b border-neutral-800/80 bg-neutral-950/80 backdrop-blur-md">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    {/* Brand */}
                    <div className="flex items-center gap-3">
                        <Link
                            href="/"
                            className="group flex items-center gap-2.5"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 font-bold text-neutral-950 shadow-md shadow-cyan-500/20 transition-shadow group-hover:shadow-cyan-500/40">
                                SS
                            </div>
                            <div>
                                <div className="flex items-center gap-1.5">
                                    <span className="text-lg font-bold tracking-tight text-white">
                                        SlotSaver
                                    </span>
                                    <span className="py-0.2 inline-flex items-center gap-0.5 rounded-full border border-cyan-500/20 bg-cyan-500/10 px-1.5 text-[10px] font-semibold text-cyan-400">
                                        v2.0
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    {/* Nav Links (Desktop) */}
                    <nav className="hidden items-center gap-8 text-sm font-medium text-neutral-300 md:flex">
                        <a
                            href="#how-it-works"
                            className="transition-colors hover:text-cyan-400"
                        >
                            How It Works
                        </a>
                        <a
                            href="#features"
                            className="transition-colors hover:text-cyan-400"
                        >
                            Features
                        </a>
                        <a
                            href="#calculator"
                            className="transition-colors hover:text-cyan-400"
                        >
                            ROI Calculator
                        </a>
                        <a
                            href="#demo-businesses"
                            className="transition-colors hover:text-cyan-400"
                        >
                            Live Demo Salons
                        </a>
                    </nav>

                    {/* Actions */}
                    <div className="flex items-center gap-3">
                        <Link
                            href="/book"
                            className="hidden items-center gap-1.5 rounded-lg border border-neutral-800 bg-neutral-900/60 px-3 py-2 text-xs font-semibold text-neutral-300 transition-colors hover:border-neutral-700 hover:text-white sm:inline-flex"
                        >
                            <Calendar className="h-3.5 w-3.5 text-cyan-400" />
                            Client Booking
                        </Link>

                        {auth?.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-500 px-4 py-2 text-xs font-semibold text-neutral-950 shadow-md shadow-cyan-500/20 transition-all hover:from-cyan-400 hover:to-indigo-400"
                            >
                                Dashboard
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link
                                    href={login()}
                                    className="rounded-lg px-3 py-2 text-xs font-semibold text-neutral-300 transition-colors hover:bg-neutral-900 hover:text-white"
                                >
                                    Log In
                                </Link>
                                <Link
                                    href="/register?role=owner"
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-500 px-4 py-2 text-xs font-semibold text-neutral-950 shadow-md shadow-cyan-500/20 transition-all hover:from-cyan-400 hover:to-indigo-400"
                                >
                                    Start Free Trial
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* Hero Section */}
            <section className="relative z-10 pt-16 pb-20 md:pt-24 md:pb-28">
                <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                    {/* Live Metric Pill */}
                    <div className="mb-8 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-950/30 px-4 py-1.5 text-xs font-medium text-emerald-400 backdrop-blur-md">
                        <span className="flex h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                        <span>
                            Proven -79% No-Show Reduction Across European Salons
                            & Clinics
                        </span>
                    </div>

                    {/* Headline */}
                    <h1 className="mx-auto max-w-4xl text-4xl leading-[1.1] font-extrabold tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Eliminate No-Shows.{' '}
                        <span className="bg-gradient-to-r from-cyan-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">
                            Fill Every Chair.
                        </span>
                    </h1>

                    {/* Subtitle */}
                    <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-neutral-400 sm:text-xl">
                        The intelligent appointment operating system with 2-way
                        WhatsApp automation, instant 15-minute waitlist refills,
                        explainable ML risk deposits, and offline tablet PWA.
                    </p>

                    {/* Primary CTAs */}
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href="/book/crown-and-blade-barbershop"
                            className="inline-flex scale-100 items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-6 py-3.5 text-sm font-semibold text-neutral-950 shadow-lg shadow-cyan-500/25 transition-all hover:scale-[1.02] hover:from-cyan-400 hover:to-indigo-500 hover:shadow-cyan-500/40"
                        >
                            <Calendar className="h-4 w-4" />
                            Test Live Booking Flow
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                        <Link
                            href="/register?role=owner"
                            className="inline-flex items-center gap-2 rounded-xl border border-neutral-700 bg-neutral-900/80 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:border-neutral-600 hover:bg-neutral-800/80"
                        >
                            <Zap className="h-4 w-4 text-cyan-400" />
                            Register Your Business (14-Day Free)
                        </Link>
                        <Link
                            href={login()}
                            className="inline-flex items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-950/60 px-5 py-3.5 text-sm font-medium text-neutral-400 transition-all hover:border-neutral-700 hover:text-white"
                        >
                            <Shield className="h-4 w-4 text-indigo-400" />
                            Explore Demo Personas
                        </Link>
                    </div>

                    {/* Live Production Proof Ticker */}
                    <div className="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 backdrop-blur-md sm:p-5">
                            <div className="text-2xl font-extrabold tracking-tight text-cyan-400 sm:text-3xl">
                                -79%
                            </div>
                            <div className="mt-1 text-xs text-neutral-400">
                                No-Show Drop (22% &rarr; 4.8%)
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 backdrop-blur-md sm:p-5">
                            <div className="text-2xl font-extrabold tracking-tight text-emerald-400 sm:text-3xl">
                                15 Min
                            </div>
                            <div className="mt-1 text-xs text-neutral-400">
                                Waitlist Auto-Refill Window
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 backdrop-blur-md sm:p-5">
                            <div className="text-2xl font-extrabold tracking-tight text-indigo-400 sm:text-3xl">
                                &euro;4,850
                            </div>
                            <div className="mt-1 text-xs text-neutral-400">
                                Avg. Monthly Recovered Revenue
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 backdrop-blur-md sm:p-5">
                            <div className="text-2xl font-extrabold tracking-tight text-teal-400 sm:text-3xl">
                                98.2%
                            </div>
                            <div className="mt-1 text-xs text-neutral-400">
                                WhatsApp Read & Confirmation
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Core Feature Pillars */}
            <section
                id="features"
                className="relative z-10 border-t border-neutral-800/80 bg-neutral-950/60 py-20"
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto mb-16 max-w-3xl text-center">
                        <h2 className="mb-2 text-xs font-semibold tracking-wider text-cyan-400 uppercase">
                            Engineered For Maximum Chair Occupancy
                        </h2>
                        <p className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Why Modern Salons Switch from Legacy Schedulers
                        </p>
                        <p className="mt-3 text-base text-neutral-400">
                            Generic calendar apps just send email confirmations
                            people ignore. SlotSaver combines AI risk
                            predictions with proactive conversational recovery.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {/* Pillar 1 */}
                        <div className="group rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-cyan-500/40">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/10 text-cyan-400 transition-transform group-hover:scale-110">
                                <MessageSquare className="h-6 w-6" />
                            </div>
                            <h3 className="mb-2 text-lg font-bold text-white">
                                2-Way WhatsApp Automation
                            </h3>
                            <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                Interactive 24h &amp; 2h WhatsApp reminders with
                                1-tap [Confirm], [Reschedule], and [Cancel]
                                buttons. Zero SMS carrier fees, 98% open rates.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs font-medium text-cyan-400">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Real-time webhook sync
                            </div>
                        </div>

                        {/* Pillar 2 */}
                        <div className="group rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-emerald-500/40">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 transition-transform group-hover:scale-110">
                                <Clock className="h-6 w-6" />
                            </div>
                            <h3 className="mb-2 text-lg font-bold text-white">
                                15-Minute Waitlist Auto-Refill
                            </h3>
                            <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                When a customer cancels, our matching engine
                                immediately broadcasts 15-minute countdown claim
                                links to waiting clients. Empty chairs get
                                filled within minutes.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs font-medium text-emerald-400">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Ranked priority matching
                            </div>
                        </div>

                        {/* Pillar 3 */}
                        <div className="group rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-indigo-500/40">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-indigo-400 transition-transform group-hover:scale-110">
                                <Zap className="h-6 w-6" />
                            </div>
                            <h3 className="mb-2 text-lg font-bold text-white">
                                Transparent ML Risk Scoring
                            </h3>
                            <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                Calibrated machine learning calculates no-show
                                probabilities using attendance history, lead
                                time, and day patterns. Micro-deposits only
                                triggered for high-risk slots.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs font-medium text-indigo-400">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Explainable SHAP factor weights
                            </div>
                        </div>

                        {/* Pillar 4 */}
                        <div className="group rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-teal-500/40">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-teal-500/20 bg-teal-500/10 text-teal-400 transition-transform group-hover:scale-110">
                                <Wifi className="h-6 w-6" />
                            </div>
                            <h3 className="mb-2 text-lg font-bold text-white">
                                Offline Tablet Reception PWA
                            </h3>
                            <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                Never lose desk operations when salon broadband
                                disconnects. IndexedDB stores check-ins and new
                                bookings offline, syncing seamlessly upon
                                reconnection.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs font-medium text-teal-400">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Conflict-free vector clock sync
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Interactive ROI Calculator */}
            <section
                id="calculator"
                className="relative z-10 border-t border-neutral-800/80 py-20"
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto mb-14 max-w-3xl text-center">
                        <div className="mb-3 inline-flex items-center gap-1.5 rounded-full border border-indigo-500/30 bg-indigo-950/30 px-3.5 py-1 text-xs font-semibold text-indigo-400">
                            <Calculator className="h-3.5 w-3.5" />
                            Interactive Revenue Recovery Calculator
                        </div>
                        <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Calculate How Much Revenue You Are Losing
                        </h2>
                        <p className="mt-2 text-sm text-neutral-400">
                            European salons lose an average of 22% of bookings
                            to no-shows and late cancellations.
                        </p>
                    </div>

                    <div className="mx-auto grid max-w-5xl grid-cols-1 items-center gap-8 lg:grid-cols-12">
                        {/* Sliders Control (6 cols) */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl sm:p-8 lg:col-span-6">
                            <h3 className="mb-6 text-base font-bold text-white">
                                Your Salon's Current Metrics
                            </h3>

                            {/* Monthly Bookings Slider */}
                            <div className="mb-6">
                                <div className="mb-2 flex items-center justify-between">
                                    <label className="text-xs font-medium text-neutral-300">
                                        Monthly Total Appointments
                                    </label>
                                    <span className="text-sm font-bold text-cyan-400">
                                        {monthlyBookings} appts
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min="50"
                                    max="1200"
                                    step="25"
                                    value={monthlyBookings}
                                    onChange={(e) =>
                                        setMonthlyBookings(
                                            Number(e.target.value),
                                        )
                                    }
                                    className="w-full cursor-pointer accent-cyan-500"
                                />
                                <div className="mt-1 flex justify-between text-[10px] text-neutral-400">
                                    <span>50</span>
                                    <span>300 (Avg Salon)</span>
                                    <span>600</span>
                                    <span>1,200</span>
                                </div>
                            </div>

                            {/* Ticket Price Slider */}
                            <div className="mb-6">
                                <div className="mb-2 flex items-center justify-between">
                                    <label className="text-xs font-medium text-neutral-300">
                                        Average Service Ticket Price
                                    </label>
                                    <span className="text-sm font-bold text-indigo-400">
                                        &euro;{avgTicketPrice}
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min="20"
                                    max="200"
                                    step="5"
                                    value={avgTicketPrice}
                                    onChange={(e) =>
                                        setAvgTicketPrice(
                                            Number(e.target.value),
                                        )
                                    }
                                    className="w-full cursor-pointer accent-indigo-500"
                                />
                                <div className="mt-1 flex justify-between text-[10px] text-neutral-400">
                                    <span>&euro;20</span>
                                    <span>&euro;45 (Barbershop)</span>
                                    <span>&euro;85 (Hair Studio)</span>
                                    <span>&euro;200</span>
                                </div>
                            </div>

                            {/* Quick Presets */}
                            <div className="border-t border-neutral-800 pt-4">
                                <span className="mb-2 block text-xs text-neutral-400">
                                    Quick Presets:
                                </span>
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(180);
                                            setAvgTicketPrice(35);
                                        }}
                                        className="rounded-md bg-neutral-800 px-2.5 py-1 text-xs text-neutral-300 transition-colors hover:bg-neutral-700"
                                    >
                                        Barbershop (180 @ &euro;35)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(350);
                                            setAvgTicketPrice(55);
                                        }}
                                        className="rounded-md bg-neutral-800 px-2.5 py-1 text-xs text-neutral-300 transition-colors hover:bg-neutral-700"
                                    >
                                        Hair Studio (350 @ &euro;55)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(450);
                                            setAvgTicketPrice(110);
                                        }}
                                        className="rounded-md bg-neutral-800 px-2.5 py-1 text-xs text-neutral-300 transition-colors hover:bg-neutral-700"
                                    >
                                        Aesthetic Clinic (450 @ &euro;110)
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Results Comparison (6 cols) */}
                        <div className="space-y-4 lg:col-span-6">
                            {/* Before Card */}
                            <div className="rounded-2xl border border-red-500/20 bg-red-950/15 p-5 backdrop-blur-md">
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="text-xs font-bold tracking-wider text-red-400 uppercase">
                                        Without SlotSaver (Industry Baseline)
                                    </span>
                                    <span className="rounded bg-red-500/20 px-2 py-0.5 text-xs font-semibold text-red-300">
                                        22.0% No-Shows
                                    </span>
                                </div>
                                <div className="mb-1 flex items-baseline gap-2">
                                    <span className="text-3xl font-extrabold text-red-400">
                                        -&euro;
                                        {baselineLostRevenue.toLocaleString()}
                                    </span>
                                    <span className="text-xs text-neutral-400">
                                        / month lost
                                    </span>
                                </div>
                                <p className="text-xs text-neutral-400">
                                    Approx.{' '}
                                    <strong className="text-white">
                                        {baselineNoShows} empty chairs
                                    </strong>{' '}
                                    every month, plus ~18 hours wasted dialing
                                    manual phone reminders.
                                </p>
                            </div>

                            {/* After Card */}
                            <div className="relative overflow-hidden rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-6 backdrop-blur-md">
                                <div className="pointer-events-none absolute top-0 right-0 h-32 w-32 rounded-full bg-emerald-500/10 blur-2xl" />
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="flex items-center gap-1.5 text-xs font-bold tracking-wider text-emerald-400 uppercase">
                                        <Sparkles className="h-3.5 w-3.5" />
                                        With SlotSaver Operating System
                                    </span>
                                    <span className="rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-300">
                                        4.8% No-Shows
                                    </span>
                                </div>
                                <div className="mb-2 flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-emerald-300">
                                        +&euro;{netSavings.toLocaleString()}
                                    </span>
                                    <span className="text-xs font-medium text-emerald-400">
                                        recovered revenue / month
                                    </span>
                                </div>
                                <ul className="mt-3 space-y-1.5 border-t border-emerald-500/20 pt-3 text-xs text-neutral-300">
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 shrink-0 text-emerald-400" />
                                        <span>
                                            <strong>
                                                {refilledCancellations}
                                            </strong>{' '}
                                            waitlist slots auto-refilled in
                                            under 15 minutes
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 shrink-0 text-emerald-400" />
                                        <span>
                                            98.2% automated WhatsApp
                                            confirmation rate
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 shrink-0 text-emerald-400" />
                                        <span>
                                            Micro-deposits collected
                                            automatically on risky reservations
                                        </span>
                                    </li>
                                </ul>

                                <div className="mt-5">
                                    <Link
                                        href="/register?role=owner"
                                        className="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-4 py-2.5 text-xs font-bold text-neutral-950 shadow-md transition-all hover:from-emerald-400 hover:to-cyan-400"
                                    >
                                        Claim Your 14-Day Free Salon Trial
                                        <ArrowRight className="ml-1.5 h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Live Demo Businesses Showcase */}
            <section
                id="demo-businesses"
                className="relative z-10 border-t border-neutral-800/80 bg-neutral-950/70 py-20"
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto mb-14 max-w-3xl text-center">
                        <div className="mb-3 inline-flex items-center gap-1.5 rounded-full border border-cyan-500/30 bg-cyan-950/30 px-3.5 py-1 text-xs font-semibold text-cyan-400">
                            <Store className="h-3.5 w-3.5" />
                            Live Test Environments
                        </div>
                        <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Experience the Client Booking Experience
                        </h2>
                        <p className="mt-2 text-sm text-neutral-400">
                            Try booking real appointments or joining waitlists
                            on our pre-seeded demonstration businesses right
                            now.
                        </p>
                    </div>

                    <div className="mx-auto grid max-w-5xl grid-cols-1 gap-8 md:grid-cols-2">
                        {/* Demo Business 1: Crown & Blade */}
                        <div className="flex flex-col justify-between rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-cyan-500/40 sm:p-8">
                            <div>
                                <div className="mb-4 flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 font-bold text-amber-400">
                                            <Scissors className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-white">
                                                Crown &amp; Blade Barbershop
                                            </h3>
                                            <p className="mt-0.5 flex items-center gap-1 text-xs text-neutral-400">
                                                <MapPin className="h-3 w-3 text-cyan-400" />
                                                Lisbon, Portugal
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1 rounded-full border border-amber-400/20 bg-amber-400/10 px-2.5 py-1 text-xs font-semibold text-amber-400">
                                        <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                                        4.9 (184 reviews)
                                    </div>
                                </div>

                                <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                    Lisbon's premier grooming lounge.
                                    High-demand weekend slots powered by dynamic
                                    risk-weighted deposits and instant WhatsApp
                                    refill alerts.
                                </p>

                                <div className="mb-6 space-y-2">
                                    <span className="text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                                        Popular Services
                                    </span>
                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Signature Fade</span>
                                            <span className="font-semibold text-cyan-400">
                                                &euro;35
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Hot Towel Shave</span>
                                            <span className="font-semibold text-cyan-400">
                                                &euro;30
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Executive Beard</span>
                                            <span className="font-semibold text-cyan-400">
                                                &euro;25
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Father &amp; Son Cut</span>
                                            <span className="font-semibold text-cyan-400">
                                                &euro;55
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/book/crown-and-blade-barbershop"
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-4 py-3 text-xs font-bold text-neutral-950 shadow-md shadow-cyan-500/20 transition-all hover:from-cyan-400 hover:to-indigo-500"
                            >
                                Book at Crown &amp; Blade
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        {/* Demo Business 2: Aurora Studio */}
                        <div className="flex flex-col justify-between rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl transition-all hover:border-indigo-500/40 sm:p-8">
                            <div>
                                <div className="mb-4 flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 font-bold text-rose-400">
                                            <Sparkles className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-white">
                                                Aurora Hair &amp; Aesthetic
                                                Studio
                                            </h3>
                                            <p className="mt-0.5 flex items-center gap-1 text-xs text-neutral-400">
                                                <MapPin className="h-3 w-3 text-indigo-400" />
                                                Lisbon, Portugal
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1 rounded-full border border-rose-400/20 bg-rose-400/10 px-2.5 py-1 text-xs font-semibold text-rose-400">
                                        <Star className="h-3 w-3 fill-rose-400 text-rose-400" />
                                        4.95 (240 reviews)
                                    </div>
                                </div>

                                <p className="mb-4 text-xs leading-relaxed text-neutral-400">
                                    Luxury hair artistry &amp; aesthetics. Uses
                                    multi-chair scheduling with automated
                                    waitlist broadcasts to eliminate multi-hour
                                    gap losses.
                                </p>

                                <div className="mb-6 space-y-2">
                                    <span className="text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                                        Popular Services
                                    </span>
                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Balayage &amp; Gloss</span>
                                            <span className="font-semibold text-indigo-400">
                                                &euro;120
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Keratin Treatment</span>
                                            <span className="font-semibold text-indigo-400">
                                                &euro;85
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>
                                                Precision Cut &amp; Blow
                                            </span>
                                            <span className="font-semibold text-indigo-400">
                                                &euro;45
                                            </span>
                                        </div>
                                        <div className="flex justify-between rounded-lg border border-neutral-800 bg-neutral-950/60 p-2 text-neutral-300">
                                            <span>Facial Rejuvenation</span>
                                            <span className="font-semibold text-indigo-400">
                                                &euro;75
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/book/aurora-hair-and-aesthetic-studio"
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-rose-500 px-4 py-3 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all hover:from-indigo-400 hover:to-rose-400"
                            >
                                Book at Aurora Studio
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Test Drive Personas Bar */}
            <section className="relative z-10 border-t border-neutral-800/80 bg-neutral-950 py-16">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-2xl border border-neutral-800 bg-neutral-900/40 p-6 backdrop-blur-xl sm:p-8">
                        <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <h3 className="flex items-center gap-2 text-base font-bold text-white">
                                    <Sparkles className="h-4 w-4 text-cyan-400" />
                                    Instant 1-Click Evaluation Personas
                                </h3>
                                <p className="mt-0.5 text-xs text-neutral-400">
                                    Reviewing the system? Jump straight into
                                    pre-configured accounts via 1-click
                                    credentials on the sign-in page.
                                </p>
                            </div>
                            <Link
                                href={login()}
                                className="inline-flex items-center gap-1.5 text-xs font-bold text-cyan-400 underline underline-offset-4 hover:text-cyan-300"
                            >
                                Open Sign In Screen &rarr;
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-xl border border-neutral-800 bg-neutral-950/70 p-3">
                                <span className="mb-0.5 block text-[10px] font-bold text-amber-400 uppercase">
                                    Salon Owner
                                </span>
                                <div className="text-xs font-semibold text-white">
                                    Mateo Silva
                                </div>
                                <div className="text-[11px] text-neutral-400">
                                    mateo@crownblade.test
                                </div>
                            </div>
                            <div className="rounded-xl border border-neutral-800 bg-neutral-950/70 p-3">
                                <span className="mb-0.5 block text-[10px] font-bold text-cyan-400 uppercase">
                                    Specialist Staff
                                </span>
                                <div className="text-xs font-semibold text-white">
                                    André Rocha
                                </div>
                                <div className="text-[11px] text-neutral-400">
                                    andre@crownblade.test
                                </div>
                            </div>
                            <div className="rounded-xl border border-neutral-800 bg-neutral-950/70 p-3">
                                <span className="mb-0.5 block text-[10px] font-bold text-emerald-400 uppercase">
                                    Client Customer
                                </span>
                                <div className="text-xs font-semibold text-white">
                                    Carlos Gomes
                                </div>
                                <div className="text-[11px] text-neutral-400">
                                    client1@example.com
                                </div>
                            </div>
                            <div className="rounded-xl border border-neutral-800 bg-neutral-950/70 p-3">
                                <span className="mb-0.5 block text-[10px] font-bold text-indigo-400 uppercase">
                                    Platform Admin
                                </span>
                                <div className="text-xs font-semibold text-white">
                                    Superadmin
                                </div>
                                <div className="text-[11px] text-neutral-400">
                                    admin@slotsaver.test
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="relative z-10 border-t border-neutral-800/80 bg-neutral-950 py-12 text-xs text-neutral-400">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 sm:px-6 md:flex-row lg:px-8">
                    <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-cyan-500 to-indigo-600 text-xs font-bold text-neutral-950">
                            SS
                        </div>
                        <div>
                            <span className="text-sm font-bold text-white">
                                SlotSaver
                            </span>
                            <span className="ml-2 text-neutral-400">
                                &mdash; The European Appointment Operating
                                System
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-6">
                        <Link
                            href="/book"
                            className="transition-colors hover:text-white"
                        >
                            Client Booking
                        </Link>
                        <Link
                            href={login()}
                            className="transition-colors hover:text-white"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register?role=owner"
                            className="transition-colors hover:text-white"
                        >
                            Register Salon
                        </Link>
                        <Link
                            href={dashboard()}
                            className="transition-colors hover:text-white"
                        >
                            Owner Dashboard
                        </Link>
                    </div>

                    <p className="text-neutral-400">
                        &copy; {new Date().getFullYear()} SlotSaver. Built with
                        Laravel 12, Inertia &amp; React.
                    </p>
                </div>
            </footer>
        </div>
    );
}
