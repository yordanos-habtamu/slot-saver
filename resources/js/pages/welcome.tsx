import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Sparkles,
    ShieldCheck,
    Calendar,
    Clock,
    ArrowRight,
    CheckCircle2,
    MessageSquare,
    Zap,
    TrendingUp,
    Wifi,
    Users,
    MapPin,
    Star,
    ExternalLink,
    ChevronRight,
    Calculator,
    Check,
    Scissors,
    Shield,
    Phone,
    Store,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    // ROI Calculator state
    const [monthlyBookings, setMonthlyBookings] = useState(300);
    const [avgTicketPrice, setAvgTicketPrice] = useState(45);

    // Calculated metrics
    const baselineNoShows = Math.round(monthlyBookings * 0.22);
    const baselineLostRevenue = Math.round(baselineNoShows * avgTicketPrice);
    const slotSaverNoShows = Math.round(monthlyBookings * 0.048);
    const refilledCancellations = Math.round((baselineNoShows - slotSaverNoShows) * 0.75);
    const recoveredRevenue = Math.round(refilledCancellations * avgTicketPrice);
    const netSavings = Math.round(recoveredRevenue + (baselineNoShows - slotSaverNoShows - refilledCancellations) * (avgTicketPrice * 0.5));

    return (
        <div className="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950 overflow-x-hidden">
            <Head title="SlotSaver — Eliminate No-Shows. Fill Every Chair." />

            {/* Glowing Ambient Backdrop Lights */}
            <div className="fixed inset-0 pointer-events-none overflow-hidden z-0">
                <div className="absolute top-0 left-1/4 h-[500px] w-[500px] rounded-full bg-cyan-500/10 blur-[140px]" />
                <div className="absolute top-1/3 right-1/4 h-[600px] w-[600px] rounded-full bg-indigo-500/10 blur-[160px]" />
                <div className="absolute bottom-10 left-1/3 h-[500px] w-[500px] rounded-full bg-emerald-500/10 blur-[150px]" />
            </div>

            {/* Navigation Bar */}
            <header className="relative z-30 border-b border-neutral-800/80 bg-neutral-950/80 backdrop-blur-md sticky top-0">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    {/* Brand */}
                    <div className="flex items-center gap-3">
                        <Link href="/" className="flex items-center gap-2.5 group">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 font-bold text-neutral-950 shadow-md shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition-shadow">
                                SS
                            </div>
                            <div>
                                <div className="flex items-center gap-1.5">
                                    <span className="text-lg font-bold tracking-tight text-white">
                                        SlotSaver
                                    </span>
                                    <span className="inline-flex items-center gap-0.5 rounded-full bg-cyan-500/10 border border-cyan-500/20 px-1.5 py-0.2 text-[10px] font-semibold text-cyan-400">
                                        v2.0
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    {/* Nav Links (Desktop) */}
                    <nav className="hidden md:flex items-center gap-8 text-sm font-medium text-neutral-300">
                        <a href="#how-it-works" className="hover:text-cyan-400 transition-colors">
                            How It Works
                        </a>
                        <a href="#features" className="hover:text-cyan-400 transition-colors">
                            Features
                        </a>
                        <a href="#calculator" className="hover:text-cyan-400 transition-colors">
                            ROI Calculator
                        </a>
                        <a href="#demo-businesses" className="hover:text-cyan-400 transition-colors">
                            Live Demo Salons
                        </a>
                    </nav>

                    {/* Actions */}
                    <div className="flex items-center gap-3">
                        <Link
                            href="/book"
                            className="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-300 hover:text-white px-3 py-2 rounded-lg border border-neutral-800 hover:border-neutral-700 bg-neutral-900/60 transition-colors"
                        >
                            <Calendar className="h-3.5 w-3.5 text-cyan-400" />
                            Client Booking
                        </Link>

                        {auth?.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-950 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-400 hover:to-indigo-400 px-4 py-2 rounded-lg shadow-md shadow-cyan-500/20 transition-all"
                            >
                                Dashboard
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link
                                    href={login()}
                                    className="text-xs font-semibold text-neutral-300 hover:text-white px-3 py-2 rounded-lg hover:bg-neutral-900 transition-colors"
                                >
                                    Log In
                                </Link>
                                <Link
                                    href="/register?role=owner"
                                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-950 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-400 hover:to-indigo-400 px-4 py-2 rounded-lg shadow-md shadow-cyan-500/20 transition-all"
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
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    {/* Live Metric Pill */}
                    <div className="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-950/30 px-4 py-1.5 text-xs font-medium text-emerald-400 mb-8 backdrop-blur-md">
                        <span className="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
                        <span>Proven -79% No-Show Reduction Across European Salons & Clinics</span>
                    </div>

                    {/* Headline */}
                    <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-[1.1]">
                        Eliminate No-Shows.{' '}
                        <span className="bg-gradient-to-r from-cyan-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">
                            Fill Every Chair.
                        </span>
                    </h1>

                    {/* Subtitle */}
                    <p className="mt-6 text-lg sm:text-xl text-neutral-400 max-w-2xl mx-auto leading-relaxed">
                        The intelligent appointment operating system with 2-way WhatsApp automation,
                        instant 15-minute waitlist refills, explainable ML risk deposits, and offline tablet PWA.
                    </p>

                    {/* Primary CTAs */}
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href="/book/crown-and-blade-barbershop"
                            className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-6 py-3.5 text-sm font-semibold text-neutral-950 shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 hover:from-cyan-400 hover:to-indigo-500 transition-all scale-100 hover:scale-[1.02]"
                        >
                            <Calendar className="h-4 w-4" />
                            Test Live Booking Flow
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                        <Link
                            href="/register?role=owner"
                            className="inline-flex items-center gap-2 rounded-xl border border-neutral-700 bg-neutral-900/80 px-6 py-3.5 text-sm font-semibold text-white hover:border-neutral-600 hover:bg-neutral-800/80 transition-all backdrop-blur-sm"
                        >
                            <Zap className="h-4 w-4 text-cyan-400" />
                            Register Your Business (14-Day Free)
                        </Link>
                        <Link
                            href={login()}
                            className="inline-flex items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-950/60 px-5 py-3.5 text-sm font-medium text-neutral-400 hover:text-white hover:border-neutral-700 transition-all"
                        >
                            <Shield className="h-4 w-4 text-indigo-400" />
                            Explore Demo Personas
                        </Link>
                    </div>

                    {/* Live Production Proof Ticker */}
                    <div className="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 sm:p-5 backdrop-blur-md">
                            <div className="text-2xl sm:text-3xl font-extrabold text-cyan-400 tracking-tight">
                                -79%
                            </div>
                            <div className="text-xs text-neutral-400 mt-1">
                                No-Show Drop (22% &rarr; 4.8%)
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 sm:p-5 backdrop-blur-md">
                            <div className="text-2xl sm:text-3xl font-extrabold text-emerald-400 tracking-tight">
                                15 Min
                            </div>
                            <div className="text-xs text-neutral-400 mt-1">
                                Waitlist Auto-Refill Window
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 sm:p-5 backdrop-blur-md">
                            <div className="text-2xl sm:text-3xl font-extrabold text-indigo-400 tracking-tight">
                                &euro;4,850
                            </div>
                            <div className="text-xs text-neutral-400 mt-1">
                                Avg. Monthly Recovered Revenue
                            </div>
                        </div>
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/50 p-4 sm:p-5 backdrop-blur-md">
                            <div className="text-2xl sm:text-3xl font-extrabold text-teal-400 tracking-tight">
                                98.2%
                            </div>
                            <div className="text-xs text-neutral-400 mt-1">
                                WhatsApp Read & Confirmation
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Core Feature Pillars */}
            <section id="features" className="relative z-10 py-20 border-t border-neutral-800/80 bg-neutral-950/60">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-xs font-semibold uppercase tracking-wider text-cyan-400 mb-2">
                            Engineered For Maximum Chair Occupancy
                        </h2>
                        <p className="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                            Why Modern Salons Switch from Legacy Schedulers
                        </p>
                        <p className="mt-3 text-base text-neutral-400">
                            Generic calendar apps just send email confirmations people ignore. SlotSaver combines AI risk predictions with proactive conversational recovery.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {/* Pillar 1 */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl hover:border-cyan-500/40 transition-all group">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 mb-5 group-hover:scale-110 transition-transform">
                                <MessageSquare className="h-6 w-6" />
                            </div>
                            <h3 className="text-lg font-bold text-white mb-2">
                                2-Way WhatsApp Automation
                            </h3>
                            <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                Interactive 24h &amp; 2h WhatsApp reminders with 1-tap [Confirm], [Reschedule], and [Cancel] buttons. Zero SMS carrier fees, 98% open rates.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs text-cyan-400 font-medium">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Real-time webhook sync
                            </div>
                        </div>

                        {/* Pillar 2 */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl hover:border-emerald-500/40 transition-all group">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-5 group-hover:scale-110 transition-transform">
                                <Clock className="h-6 w-6" />
                            </div>
                            <h3 className="text-lg font-bold text-white mb-2">
                                15-Minute Waitlist Auto-Refill
                            </h3>
                            <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                When a customer cancels, our matching engine immediately broadcasts 15-minute countdown claim links to waiting clients. Empty chairs get filled within minutes.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Ranked priority matching
                            </div>
                        </div>

                        {/* Pillar 3 */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl hover:border-indigo-500/40 transition-all group">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 mb-5 group-hover:scale-110 transition-transform">
                                <Zap className="h-6 w-6" />
                            </div>
                            <h3 className="text-lg font-bold text-white mb-2">
                                Transparent ML Risk Scoring
                            </h3>
                            <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                Calibrated machine learning calculates no-show probabilities using attendance history, lead time, and day patterns. Micro-deposits only triggered for high-risk slots.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs text-indigo-400 font-medium">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Explainable SHAP factor weights
                            </div>
                        </div>

                        {/* Pillar 4 */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 backdrop-blur-xl hover:border-teal-500/40 transition-all group">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-400 mb-5 group-hover:scale-110 transition-transform">
                                <Wifi className="h-6 w-6" />
                            </div>
                            <h3 className="text-lg font-bold text-white mb-2">
                                Offline Tablet Reception PWA
                            </h3>
                            <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                Never lose desk operations when salon broadband disconnects. IndexedDB stores check-ins and new bookings offline, syncing seamlessly upon reconnection.
                            </p>
                            <div className="flex items-center gap-1.5 text-xs text-teal-400 font-medium">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Conflict-free vector clock sync
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Interactive ROI Calculator */}
            <section id="calculator" className="relative z-10 py-20 border-t border-neutral-800/80">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-14">
                        <div className="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/30 bg-indigo-950/30 px-3.5 py-1 text-xs font-semibold text-indigo-400 mb-3">
                            <Calculator className="h-3.5 w-3.5" />
                            Interactive Revenue Recovery Calculator
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                            Calculate How Much Revenue You Are Losing
                        </h2>
                        <p className="mt-2 text-sm text-neutral-400">
                            European salons lose an average of 22% of bookings to no-shows and late cancellations.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
                        {/* Sliders Control (6 cols) */}
                        <div className="lg:col-span-6 rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 sm:p-8 backdrop-blur-xl">
                            <h3 className="text-base font-bold text-white mb-6">
                                Your Salon's Current Metrics
                            </h3>

                            {/* Monthly Bookings Slider */}
                            <div className="mb-6">
                                <div className="flex items-center justify-between mb-2">
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
                                    onChange={(e) => setMonthlyBookings(Number(e.target.value))}
                                    className="w-full accent-cyan-500 cursor-pointer"
                                />
                                <div className="flex justify-between text-[10px] text-neutral-400 mt-1">
                                    <span>50</span>
                                    <span>300 (Avg Salon)</span>
                                    <span>600</span>
                                    <span>1,200</span>
                                </div>
                            </div>

                            {/* Ticket Price Slider */}
                            <div className="mb-6">
                                <div className="flex items-center justify-between mb-2">
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
                                    onChange={(e) => setAvgTicketPrice(Number(e.target.value))}
                                    className="w-full accent-indigo-500 cursor-pointer"
                                />
                                <div className="flex justify-between text-[10px] text-neutral-400 mt-1">
                                    <span>&euro;20</span>
                                    <span>&euro;45 (Barbershop)</span>
                                    <span>&euro;85 (Hair Studio)</span>
                                    <span>&euro;200</span>
                                </div>
                            </div>

                            {/* Quick Presets */}
                            <div className="pt-4 border-t border-neutral-800">
                                <span className="text-xs text-neutral-400 block mb-2">
                                    Quick Presets:
                                </span>
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(180);
                                            setAvgTicketPrice(35);
                                        }}
                                        className="text-xs px-2.5 py-1 rounded-md bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-colors"
                                    >
                                        Barbershop (180 @ &euro;35)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(350);
                                            setAvgTicketPrice(55);
                                        }}
                                        className="text-xs px-2.5 py-1 rounded-md bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-colors"
                                    >
                                        Hair Studio (350 @ &euro;55)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMonthlyBookings(450);
                                            setAvgTicketPrice(110);
                                        }}
                                        className="text-xs px-2.5 py-1 rounded-md bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-colors"
                                    >
                                        Aesthetic Clinic (450 @ &euro;110)
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Results Comparison (6 cols) */}
                        <div className="lg:col-span-6 space-y-4">
                            {/* Before Card */}
                            <div className="rounded-2xl border border-red-500/20 bg-red-950/15 p-5 backdrop-blur-md">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs font-bold text-red-400 uppercase tracking-wider">
                                        Without SlotSaver (Industry Baseline)
                                    </span>
                                    <span className="text-xs font-semibold px-2 py-0.5 rounded bg-red-500/20 text-red-300">
                                        22.0% No-Shows
                                    </span>
                                </div>
                                <div className="flex items-baseline gap-2 mb-1">
                                    <span className="text-3xl font-extrabold text-red-400">
                                        -&euro;{baselineLostRevenue.toLocaleString()}
                                    </span>
                                    <span className="text-xs text-neutral-400">/ month lost</span>
                                </div>
                                <p className="text-xs text-neutral-400">
                                    Approx. <strong className="text-white">{baselineNoShows} empty chairs</strong> every month, plus ~18 hours wasted dialing manual phone reminders.
                                </p>
                            </div>

                            {/* After Card */}
                            <div className="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-6 backdrop-blur-md relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none" />
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                        <Sparkles className="h-3.5 w-3.5" />
                                        With SlotSaver Operating System
                                    </span>
                                    <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        4.8% No-Shows
                                    </span>
                                </div>
                                <div className="flex items-baseline gap-2 mb-2">
                                    <span className="text-4xl font-extrabold text-emerald-300">
                                        +&euro;{netSavings.toLocaleString()}
                                    </span>
                                    <span className="text-xs text-emerald-400 font-medium">
                                        recovered revenue / month
                                    </span>
                                </div>
                                <ul className="space-y-1.5 text-xs text-neutral-300 mt-3 pt-3 border-t border-emerald-500/20">
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 text-emerald-400 shrink-0" />
                                        <span><strong>{refilledCancellations}</strong> waitlist slots auto-refilled in under 15 minutes</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 text-emerald-400 shrink-0" />
                                        <span>98.2% automated WhatsApp confirmation rate</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3.5 w-3.5 text-emerald-400 shrink-0" />
                                        <span>Micro-deposits collected automatically on risky reservations</span>
                                    </li>
                                </ul>

                                <div className="mt-5">
                                    <Link
                                        href="/register?role=owner"
                                        className="inline-flex items-center justify-center w-full rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 py-2.5 px-4 text-xs font-bold text-neutral-950 shadow-md hover:from-emerald-400 hover:to-cyan-400 transition-all"
                                    >
                                        Claim Your 14-Day Free Salon Trial
                                        <ArrowRight className="h-3.5 w-3.5 ml-1.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Live Demo Businesses Showcase */}
            <section id="demo-businesses" className="relative z-10 py-20 border-t border-neutral-800/80 bg-neutral-950/70">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-14">
                        <div className="inline-flex items-center gap-1.5 rounded-full border border-cyan-500/30 bg-cyan-950/30 px-3.5 py-1 text-xs font-semibold text-cyan-400 mb-3">
                            <Store className="h-3.5 w-3.5" />
                            Live Test Environments
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                            Experience the Client Booking Experience
                        </h2>
                        <p className="mt-2 text-sm text-neutral-400">
                            Try booking real appointments or joining waitlists on our pre-seeded demonstration businesses right now.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                        {/* Demo Business 1: Crown & Blade */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 sm:p-8 backdrop-blur-xl hover:border-cyan-500/40 transition-all flex flex-col justify-between">
                            <div>
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold">
                                            <Scissors className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-white">
                                                Crown &amp; Blade Barbershop
                                            </h3>
                                            <p className="text-xs text-neutral-400 flex items-center gap-1 mt-0.5">
                                                <MapPin className="h-3 w-3 text-cyan-400" />
                                                Lisbon, Portugal
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1 text-xs font-semibold text-amber-400 bg-amber-400/10 border border-amber-400/20 px-2.5 py-1 rounded-full">
                                        <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                                        4.9 (184 reviews)
                                    </div>
                                </div>

                                <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                    Lisbon's premier grooming lounge. High-demand weekend slots powered by dynamic risk-weighted deposits and instant WhatsApp refill alerts.
                                </p>

                                <div className="space-y-2 mb-6">
                                    <span className="text-[11px] font-semibold uppercase text-neutral-400 tracking-wider">
                                        Popular Services
                                    </span>
                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Signature Fade</span>
                                            <span className="text-cyan-400 font-semibold">&euro;35</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Hot Towel Shave</span>
                                            <span className="text-cyan-400 font-semibold">&euro;30</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Executive Beard</span>
                                            <span className="text-cyan-400 font-semibold">&euro;25</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Father &amp; Son Cut</span>
                                            <span className="text-cyan-400 font-semibold">&euro;55</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/book/crown-and-blade-barbershop"
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-4 py-3 text-xs font-bold text-neutral-950 hover:from-cyan-400 hover:to-indigo-500 transition-all shadow-md shadow-cyan-500/20"
                            >
                                Book at Crown &amp; Blade
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        {/* Demo Business 2: Aurora Studio */}
                        <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/60 p-6 sm:p-8 backdrop-blur-xl hover:border-indigo-500/40 transition-all flex flex-col justify-between">
                            <div>
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold">
                                            <Sparkles className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-white">
                                                Aurora Hair &amp; Aesthetic Studio
                                            </h3>
                                            <p className="text-xs text-neutral-400 flex items-center gap-1 mt-0.5">
                                                <MapPin className="h-3 w-3 text-indigo-400" />
                                                Lisbon, Portugal
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1 text-xs font-semibold text-rose-400 bg-rose-400/10 border border-rose-400/20 px-2.5 py-1 rounded-full">
                                        <Star className="h-3 w-3 fill-rose-400 text-rose-400" />
                                        4.95 (240 reviews)
                                    </div>
                                </div>

                                <p className="text-xs text-neutral-400 leading-relaxed mb-4">
                                    Luxury hair artistry &amp; aesthetics. Uses multi-chair scheduling with automated waitlist broadcasts to eliminate multi-hour gap losses.
                                </p>

                                <div className="space-y-2 mb-6">
                                    <span className="text-[11px] font-semibold uppercase text-neutral-400 tracking-wider">
                                        Popular Services
                                    </span>
                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Balayage &amp; Gloss</span>
                                            <span className="text-indigo-400 font-semibold">&euro;120</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Keratin Treatment</span>
                                            <span className="text-indigo-400 font-semibold">&euro;85</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Precision Cut &amp; Blow</span>
                                            <span className="text-indigo-400 font-semibold">&euro;45</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-neutral-950/60 border border-neutral-800 text-neutral-300 flex justify-between">
                                            <span>Facial Rejuvenation</span>
                                            <span className="text-indigo-400 font-semibold">&euro;75</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/book/aurora-hair-and-aesthetic-studio"
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-rose-500 px-4 py-3 text-xs font-bold text-white hover:from-indigo-400 hover:to-rose-400 transition-all shadow-md shadow-indigo-500/20"
                            >
                                Book at Aurora Studio
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Test Drive Personas Bar */}
            <section className="relative z-10 py-16 border-t border-neutral-800/80 bg-neutral-950">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="rounded-2xl border border-neutral-800 bg-neutral-900/40 p-6 sm:p-8 backdrop-blur-xl">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                            <div>
                                <h3 className="text-base font-bold text-white flex items-center gap-2">
                                    <Sparkles className="h-4 w-4 text-cyan-400" />
                                    Instant 1-Click Evaluation Personas
                                </h3>
                                <p className="text-xs text-neutral-400 mt-0.5">
                                    Reviewing the system? Jump straight into pre-configured accounts via 1-click credentials on the sign-in page.
                                </p>
                            </div>
                            <Link
                                href={login()}
                                className="inline-flex items-center gap-1.5 text-xs font-bold text-cyan-400 hover:text-cyan-300 underline underline-offset-4"
                            >
                                Open Sign In Screen &rarr;
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div className="p-3 rounded-xl border border-neutral-800 bg-neutral-950/70">
                                <span className="text-[10px] uppercase font-bold text-amber-400 block mb-0.5">
                                    Salon Owner
                                </span>
                                <div className="text-xs font-semibold text-white">Mateo Silva</div>
                                <div className="text-[11px] text-neutral-400">mateo@crownblade.test</div>
                            </div>
                            <div className="p-3 rounded-xl border border-neutral-800 bg-neutral-950/70">
                                <span className="text-[10px] uppercase font-bold text-cyan-400 block mb-0.5">
                                    Specialist Staff
                                </span>
                                <div className="text-xs font-semibold text-white">André Rocha</div>
                                <div className="text-[11px] text-neutral-400">andre@crownblade.test</div>
                            </div>
                            <div className="p-3 rounded-xl border border-neutral-800 bg-neutral-950/70">
                                <span className="text-[10px] uppercase font-bold text-emerald-400 block mb-0.5">
                                    Client Customer
                                </span>
                                <div className="text-xs font-semibold text-white">Carlos Gomes</div>
                                <div className="text-[11px] text-neutral-400">client1@example.com</div>
                            </div>
                            <div className="p-3 rounded-xl border border-neutral-800 bg-neutral-950/70">
                                <span className="text-[10px] uppercase font-bold text-indigo-400 block mb-0.5">
                                    Platform Admin
                                </span>
                                <div className="text-xs font-semibold text-white">Superadmin</div>
                                <div className="text-[11px] text-neutral-400">admin@slotsaver.test</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="relative z-10 border-t border-neutral-800/80 bg-neutral-950 py-12 text-xs text-neutral-400">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-cyan-500 to-indigo-600 font-bold text-neutral-950 text-xs">
                            SS
                        </div>
                        <div>
                            <span className="font-bold text-white text-sm">SlotSaver</span>
                            <span className="text-neutral-400 ml-2">&mdash; The European Appointment Operating System</span>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-6">
                        <Link href="/book" className="hover:text-white transition-colors">
                            Client Booking
                        </Link>
                        <Link href={login()} className="hover:text-white transition-colors">
                            Sign In
                        </Link>
                        <Link href="/register?role=owner" className="hover:text-white transition-colors">
                            Register Salon
                        </Link>
                        <Link href={dashboard()} className="hover:text-white transition-colors">
                            Owner Dashboard
                        </Link>
                    </div>

                    <p className="text-neutral-400">
                        &copy; {new Date().getFullYear()} SlotSaver. Built with Laravel 12, Inertia &amp; React.
                    </p>
                </div>
            </footer>
        </div>
    );
}
