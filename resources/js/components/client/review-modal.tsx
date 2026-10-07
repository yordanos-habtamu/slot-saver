import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { AlertCircle, Star, X } from 'lucide-react';
import { login } from '@/routes';

interface ReviewModalProps {
    booking: {
        id: number;
        reference_code: string;
        service_name: string;
        employee_name: string;
    };
    onClose: () => void;
    onRated: (bookingId: number, rating: number) => void;
}

export function ReviewModal({ booking, onClose, onRated }: ReviewModalProps) {
    const [rating, setRating] = useState(0);
    const [hovered, setHovered] = useState(0);
    const [body, setBody] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (rating < 1) {
            setErrorMessage('Pick a star rating first.');
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            const res = await fetch(`/api/bookings/${booking.id}/review`, {
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
                    rating,
                    body: body.trim() === '' ? null : body.trim(),
                }),
            });

            if (res.status === 401) {
                router.get(login().url);
                return;
            }

            if (res.status === 403) {
                setErrorMessage('This appointment can no longer be reviewed.');
                return;
            }

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                setErrorMessage(
                    data.message || 'Unable to submit your review.',
                );
                return;
            }

            onRated(booking.id, rating);
        } catch {
            setErrorMessage('Network error. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
            <div className="relative w-full max-w-md overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-neutral-100 shadow-2xl">
                <div className="absolute -top-20 -left-20 h-40 w-40 rounded-full bg-cyan-500/10 blur-3xl" />

                <button
                    type="button"
                    onClick={onClose}
                    className="absolute top-4 right-4 rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                    aria-label="Close"
                >
                    <X className="h-4 w-4" />
                </button>

                <div className="flex items-center gap-3 border-b border-neutral-800/80 pb-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                        <Star className="h-5 w-5" />
                    </div>
                    <div>
                        <h3 className="text-base font-semibold text-white">
                            Rate your visit
                        </h3>
                        <p className="text-xs text-neutral-400">
                            {booking.service_name} · {booking.reference_code}
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    <div>
                        <p className="mb-1.5 text-[11px] font-medium text-neutral-400">
                            How was your appointment?
                        </p>
                        <div className="flex items-center gap-1.5">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setRating(value)}
                                    onMouseEnter={() => setHovered(value)}
                                    onMouseLeave={() => setHovered(0)}
                                    className="transition-transform hover:scale-110"
                                    aria-label={`${value} star${value === 1 ? '' : 's'}`}
                                >
                                    <Star
                                        className={`h-7 w-7 ${
                                            value <= (hovered || rating)
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-neutral-700'
                                        }`}
                                    />
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="review-body"
                            className="mb-1 block text-[11px] font-medium text-neutral-400"
                        >
                            Comment{' '}
                            <span className="text-neutral-600">(optional)</span>
                        </label>
                        <textarea
                            id="review-body"
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            placeholder="Tell others how it went..."
                            rows={3}
                            maxLength={2000}
                            className="w-full resize-none rounded-xl border border-neutral-800 bg-neutral-900/80 p-2.5 text-xs text-neutral-200 outline-none focus:border-cyan-500"
                        />
                    </div>

                    {errorMessage && (
                        <div className="flex items-start gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                            <AlertCircle className="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>{errorMessage}</span>
                        </div>
                    )}

                    <div className="flex gap-2 pt-1">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-xl border border-neutral-800 bg-neutral-900/60 py-2.5 text-xs font-semibold text-neutral-300 transition hover:bg-neutral-800"
                        >
                            Later
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting || rating < 1}
                            className="flex-1 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 py-2.5 text-xs font-bold text-neutral-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110 disabled:pointer-events-none disabled:opacity-40"
                        >
                            {isSubmitting ? 'Submitting...' : 'Submit Review'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
