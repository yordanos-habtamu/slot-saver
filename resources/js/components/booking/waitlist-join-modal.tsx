import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { BellRing, CheckCircle2, AlertCircle, LogIn } from 'lucide-react';
import { login } from '@/routes';

interface WaitlistJoinModalProps {
    isOpen: boolean;
    onClose: () => void;
    businessId: number;
    serviceId: number;
    serviceName: string;
    preferredDate: string;
    employeeId?: number | null;
}

export function WaitlistJoinModal({
    isOpen,
    onClose,
    businessId,
    serviceId,
    serviceName,
    preferredDate,
    employeeId,
}: WaitlistJoinModalProps) {
    const { auth } = usePage().props;
    const user = auth?.user ?? null;

    const [timeWindow, setTimeWindow] = useState<
        'any' | 'morning' | 'afternoon'
    >('any');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSuccess, setIsSuccess] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    if (!isOpen) return null;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrorMessage(null);

        let timeFrom = '09:00';
        let timeTo = '18:00';
        if (timeWindow === 'morning') {
            timeFrom = '09:00';
            timeTo = '13:00';
        } else if (timeWindow === 'afternoon') {
            timeFrom = '13:00';
            timeTo = '18:00';
        }

        try {
            const response = await fetch('/api/waitlist/join', {
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
                    business_id: businessId,
                    service_id: serviceId,
                    preferred_date: preferredDate,
                    preferred_time_from: timeFrom,
                    preferred_time_to: timeTo,
                    preferred_employee_id: employeeId || null,
                }),
            });

            if (response.status === 401) {
                router.get(login().url);
                return;
            }

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Unable to join waitlist.');
            }

            setIsSuccess(true);
        } catch (err) {
            setErrorMessage(
                err instanceof Error ? err.message : 'Unable to join waitlist.',
            );
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex animate-in items-center justify-center bg-black/80 p-4 backdrop-blur-sm duration-200 fade-in">
            <div className="relative w-full max-w-md overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-neutral-100 shadow-2xl">
                <div className="absolute -top-20 -left-20 h-40 w-40 rounded-full bg-cyan-500/10 blur-3xl" />

                {/* Close */}
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                >
                    ✕
                </button>

                {isSuccess ? (
                    <div className="space-y-3 py-4 text-center">
                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                            <CheckCircle2 className="h-8 w-8" />
                        </div>
                        <h3 className="text-lg font-bold text-white">
                            You're on the Priority Waitlist!
                        </h3>
                        <p className="mx-auto max-w-xs text-xs leading-relaxed text-neutral-400">
                            If an appointment opens up on{' '}
                            <span className="font-medium text-white">
                                {preferredDate}
                            </span>
                            , we will immediately send you a WhatsApp alert with
                            a 15-minute reservation window.
                        </p>
                        <div className="pt-3">
                            <button
                                onClick={onClose}
                                className="w-full rounded-xl bg-neutral-800 py-2.5 text-xs font-semibold text-white transition hover:bg-neutral-700"
                            >
                                Done
                            </button>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="flex items-center gap-3 border-b border-neutral-800/80 pb-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-500/30 bg-cyan-500/10 text-cyan-400">
                                <BellRing className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="text-base font-semibold text-white">
                                    Join Priority Waitlist
                                </h3>
                                <p className="text-xs text-neutral-400">
                                    Get notified the instant a slot opens
                                </p>
                            </div>
                        </div>

                        <div className="mt-3.5 flex items-center justify-between rounded-xl border border-neutral-800/60 bg-neutral-900/40 p-3 text-xs text-neutral-300">
                            <div>
                                <p className="text-[11px] text-neutral-400">
                                    Selected Service
                                </p>
                                <p className="font-medium text-white">
                                    {serviceName}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-[11px] text-neutral-400">
                                    Target Date
                                </p>
                                <p className="font-medium text-cyan-400">
                                    {preferredDate}
                                </p>
                            </div>
                        </div>

                        {!user ? (
                            <div className="mt-4 space-y-3">
                                <div className="rounded-xl border border-neutral-800 bg-neutral-900/60 p-3 text-xs leading-relaxed text-neutral-400">
                                    The waitlist is tied to your account so
                                    offers can be reserved in your name. Sign in
                                    to continue.
                                </div>
                                <button
                                    type="button"
                                    onClick={() => router.get(login().url)}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 py-2.5 text-xs font-semibold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110"
                                >
                                    <LogIn className="h-3.5 w-3.5" />
                                    Sign in to Join
                                </button>
                            </div>
                        ) : (
                            <form
                                onSubmit={handleSubmit}
                                className="mt-4 space-y-3"
                            >
                                <div>
                                    <label className="mb-1.5 block text-[11px] font-medium text-neutral-400">
                                        Preferred Time Window
                                    </label>
                                    <div className="grid grid-cols-3 gap-2">
                                        {[
                                            { id: 'any', label: 'Anytime' },
                                            {
                                                id: 'morning',
                                                label: 'Morning (9-1)',
                                            },
                                            {
                                                id: 'afternoon',
                                                label: 'Afternoon (1-6)',
                                            },
                                        ].map((opt) => (
                                            <button
                                                key={opt.id}
                                                type="button"
                                                onClick={() =>
                                                    setTimeWindow(opt.id as any)
                                                }
                                                className={`rounded-lg border px-2 py-1.5 text-[11px] font-medium transition ${
                                                    timeWindow === opt.id
                                                        ? 'border-cyan-500 bg-cyan-500/10 text-cyan-300'
                                                        : 'border-neutral-800 bg-neutral-900/40 text-neutral-400 hover:border-neutral-700'
                                                }`}
                                            >
                                                {opt.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {errorMessage && (
                                    <div className="flex items-start gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                                        <AlertCircle className="mt-px h-3.5 w-3.5 shrink-0" />
                                        <span>{errorMessage}</span>
                                    </div>
                                )}

                                <div className="pt-2">
                                    <button
                                        type="submit"
                                        disabled={isSubmitting}
                                        className="w-full rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 py-2.5 text-xs font-semibold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110 active:scale-[0.98] disabled:opacity-50"
                                    >
                                        {isSubmitting
                                            ? 'Joining Waitlist...'
                                            : 'Notify Me When Slot Opens'}
                                    </button>
                                </div>
                            </form>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
