import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Clock,
    Sparkles,
    CheckCircle2,
    AlertTriangle,
    ArrowRight,
} from 'lucide-react';

interface WaitlistClaimProps {
    offer: {
        token: string;
        status: string;
        is_claimable: boolean;
        remaining_seconds: number;
        expires_at: string | null;
        service: {
            name: string;
            price: number;
            duration: number;
        };
        business: {
            name: string;
            phone: string | null;
        };
        slot: {
            start_at: string;
            end_at: string;
            location_name?: string;
            employee_name?: string;
        } | null;
    };
}

export default function WaitlistClaim({ offer }: WaitlistClaimProps) {
    const [secondsLeft, setSecondsLeft] = useState(offer.remaining_seconds);
    const [isClaiming, setIsClaiming] = useState(false);
    const [claimSuccess, setClaimSuccess] = useState<{
        reference_code: string;
    } | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Live countdown timer ticking down every second
    useEffect(() => {
        if (!offer.is_claimable || secondsLeft <= 0) return;

        const interval = setInterval(() => {
            setSecondsLeft((prev) => {
                if (prev <= 1) {
                    clearInterval(interval);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [offer.is_claimable]);

    const formatCountdown = (secs: number) => {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    };

    const isExpired = secondsLeft <= 0 || !offer.is_claimable;

    const handleClaim = async () => {
        setIsClaiming(true);
        setErrorMessage(null);

        try {
            const res = await fetch(`/waitlist/claim/${offer.token}`, {
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
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                setClaimSuccess(data.booking);
            } else {
                setErrorMessage(
                    data.message ||
                        'Unable to claim slot. The offer may have just expired.',
                );
            }
        } catch {
            setErrorMessage('Network error while claiming slot.');
        } finally {
            setIsClaiming(false);
        }
    };

    const startDate = offer.slot ? new Date(offer.slot.start_at) : null;

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-neutral-950 px-4 py-12 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950">
            <Head title={`Claim Freed Slot — ${offer.business.name}`} />

            {/* Glowing backdrop */}
            <div className="pointer-events-none fixed inset-0 overflow-hidden">
                <div className="absolute top-1/4 left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-cyan-500/10 blur-[130px]" />
            </div>

            <div className="relative w-full max-w-lg rounded-3xl border border-neutral-800 bg-neutral-900/70 p-8 shadow-2xl backdrop-blur-xl">
                {claimSuccess ? (
                    /* Claimed Success View */
                    <div className="animate-in space-y-4 py-4 text-center duration-300 fade-in">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                            <CheckCircle2 className="h-10 w-10" />
                        </div>
                        <span className="inline-block rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-0.5 text-xs font-semibold text-emerald-400 uppercase">
                            Slot Confirmed
                        </span>
                        <h1 className="text-2xl font-bold tracking-tight text-white">
                            The Slot is Yours!
                        </h1>
                        <p className="mx-auto max-w-sm text-xs text-neutral-400">
                            Your appointment reference is{' '}
                            <span className="font-mono font-bold text-cyan-400">
                                {claimSuccess.reference_code}
                            </span>
                            . A confirmation message with 24h interactive
                            reminders has been sent to your WhatsApp.
                        </p>

                        <div className="pt-4">
                            <button
                                onClick={() =>
                                    router.visit(
                                        `/booking/confirmation/${claimSuccess.reference_code}`,
                                    )
                                }
                                className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-6 py-3 text-xs font-bold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110"
                            >
                                View Appointment Details
                                <ArrowRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                ) : isExpired ? (
                    /* Expired State View */
                    <div className="space-y-4 py-4 text-center">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                            <AlertTriangle className="h-9 w-9" />
                        </div>
                        <h1 className="text-xl font-bold text-white">
                            Offer Window Expired
                        </h1>
                        <p className="mx-auto max-w-sm text-xs leading-relaxed text-neutral-400">
                            This 15-minute priority claim window has lapsed. To
                            ensure fair access, the freed slot has been
                            automatically cascaded to the next waitlisted
                            client.
                        </p>
                        <div className="pt-2">
                            <a
                                href="/book"
                                className="inline-block rounded-xl border border-neutral-700 bg-neutral-800 px-5 py-2.5 text-xs font-medium text-white transition hover:bg-neutral-700"
                            >
                                Back to Booking Calendar
                            </a>
                        </div>
                    </div>
                ) : (
                    /* Active 15-minute countdown view */
                    <div className="space-y-6">
                        {/* Urgent Alert Banner */}
                        <div className="flex items-center justify-between border-b border-neutral-800 pb-4">
                            <div className="flex items-center gap-2.5">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-500/30 bg-cyan-500/10 text-cyan-400">
                                    <Sparkles className="h-5 w-5" />
                                </div>
                                <div>
                                    <h1 className="text-base font-bold text-white">
                                        A Slot Just Opened Up!
                                    </h1>
                                    <p className="text-xs text-neutral-400">
                                        {offer.business.name}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Countdown Timer Display */}
                        <div className="space-y-2 rounded-2xl border border-cyan-500/40 bg-cyan-950/20 p-5 text-center shadow-lg shadow-cyan-950/40">
                            <p className="flex items-center justify-center gap-1.5 text-[11px] font-semibold tracking-widest text-cyan-300 uppercase">
                                <Clock className="h-3.5 w-3.5" /> Exclusive Hold
                                Countdown
                            </p>
                            <div className="font-mono text-4xl font-black tracking-tight text-white">
                                {formatCountdown(secondsLeft)}
                            </div>
                            <p className="text-[11px] text-neutral-400">
                                Claim within 15 minutes before this slot
                                cascades to the next client in line.
                            </p>

                            {/* Countdown progress bar (900s = 15 mins) */}
                            <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-neutral-800/80">
                                <div
                                    className="h-1.5 bg-gradient-to-r from-cyan-400 to-indigo-500 transition-all duration-1000 ease-linear"
                                    style={{
                                        width: `${Math.min(100, (secondsLeft / 900) * 100)}%`,
                                    }}
                                />
                            </div>
                        </div>

                        {/* Slot Details */}
                        <div className="space-y-3 rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-4 text-xs">
                            <div className="flex items-center justify-between border-b border-neutral-800 pb-2 text-neutral-300">
                                <span className="text-neutral-400">
                                    Service
                                </span>
                                <span className="font-semibold text-white">
                                    {offer.service.name}
                                </span>
                            </div>

                            {startDate && (
                                <div className="flex items-center justify-between border-b border-neutral-800 pb-2 text-neutral-300">
                                    <span className="text-neutral-400">
                                        Date & Time
                                    </span>
                                    <span className="font-semibold text-cyan-400">
                                        {startDate.toLocaleDateString('en-US', {
                                            weekday: 'short',
                                            month: 'short',
                                            day: 'numeric',
                                        })}{' '}
                                        at{' '}
                                        {startDate.toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                        })}
                                    </span>
                                </div>
                            )}

                            <div className="flex items-center justify-between border-b border-neutral-800 pb-2 text-neutral-300">
                                <span className="text-neutral-400">
                                    Duration
                                </span>
                                <span>{offer.service.duration} mins</span>
                            </div>

                            {offer.slot?.employee_name && (
                                <div className="flex items-center justify-between border-b border-neutral-800 pb-2 text-neutral-300">
                                    <span className="text-neutral-400">
                                        Specialist
                                    </span>
                                    <span>{offer.slot.employee_name}</span>
                                </div>
                            )}

                            <div className="flex items-center justify-between pt-1 text-sm font-bold text-white">
                                <span>Price</span>
                                <span className="text-emerald-400">
                                    ${offer.service.price.toFixed(2)}
                                </span>
                            </div>
                        </div>

                        {errorMessage && (
                            <div className="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                                {errorMessage}
                            </div>
                        )}

                        {/* Claim CTA */}
                        <div>
                            <button
                                type="button"
                                disabled={isClaiming || isExpired}
                                onClick={handleClaim}
                                className="w-full rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3.5 text-xs font-bold text-neutral-950 shadow-xl shadow-emerald-500/25 transition hover:brightness-110 active:scale-[0.98] disabled:opacity-50"
                            >
                                {isClaiming
                                    ? 'Claiming Appointment...'
                                    : 'Claim My Appointment Now'}
                            </button>
                            <p className="mt-2 text-center text-[11px] text-neutral-500">
                                Instant confirmation • Free cancellation up to
                                24h before
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
