import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Calendar,
    Clock,
    MapPin,
    User,
    Copy,
    Check,
    MessageSquare,
    ExternalLink,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';

interface BookingConfirmationProps {
    booking: {
        id: number;
        reference_code: string;
        status: string;
        start_at: string;
        end_at: string;
        duration_minutes: number;
        total_amount: number;
        deposit_amount: number;
        deposit_status: string;
        client_note: string | null;
        business: {
            name: string;
            phone: string | null;
            timezone: string;
            cancellation_notice: string | null;
        };
        service: {
            name: string;
            price: number;
        };
        location: {
            name: string;
            address: string;
        };
        employee: {
            name: string;
        } | null;
        reminders: Array<{
            type: string;
            channel: string;
            scheduled_for: string;
            delivery_status: string;
        }>;
    };
}

export default function BookingConfirmation({ booking }: BookingConfirmationProps) {
    const [copied, setCopied] = useState(false);

    const startDate = new Date(booking.start_at);
    const endDate = new Date(booking.end_at);

    const formattedDate = startDate.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    const formattedTime = `${startDate.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })} - ${endDate.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })}`;

    const handleCopy = () => {
        navigator.clipboard.writeText(booking.reference_code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    // Google Calendar URL generator
    const googleCalUrl = (() => {
        const isoStart = startDate.toISOString().replace(/-|:|\.\d\d\d/g, '');
        const isoEnd = endDate.toISOString().replace(/-|:|\.\d\d\d/g, '');
        const text = encodeURIComponent(`${booking.service.name} at ${booking.business.name}`);
        const details = encodeURIComponent(
            `Appointment reference: ${booking.reference_code}\nStaff: ${booking.employee?.name || 'Assigned Staff'}\nLocation: ${booking.location.address}`
        );
        const location = encodeURIComponent(booking.location.address);
        return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${text}&dates=${isoStart}/${isoEnd}&details=${details}&location=${location}`;
    })();

    // iCal data uri generator
    const downloadIcs = () => {
        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SlotSaver//Appointment//EN',
            'BEGIN:VEVENT',
            `SUMMARY:${booking.service.name} - ${booking.business.name}`,
            `DESCRIPTION:Booking Ref: ${booking.reference_code}`,
            `LOCATION:${booking.location.address}`,
            `DTSTART:${startDate.toISOString().replace(/-|:|\.\d\d\d/g, '')}`,
            `DTEND:${endDate.toISOString().replace(/-|:|\.\d\d\d/g, '')}`,
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ].join('\r\n');

        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `appointment-${booking.reference_code}.ics`;
        a.click();
    };

    return (
        <div className="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-cyan-500 selection:text-neutral-950">
            <Head title={`Confirmed: ${booking.reference_code} — ${booking.business.name}`} />

            <div className="fixed inset-0 pointer-events-none overflow-hidden">
                <div className="absolute top-10 left-1/2 -translate-x-1/2 h-80 w-80 rounded-full bg-emerald-500/15 blur-[120px]" />
            </div>

            <main className="relative max-w-2xl mx-auto px-4 py-12">
                {/* Confirmation Box */}
                <div className="rounded-3xl border border-neutral-800 bg-neutral-900/60 backdrop-blur-xl p-8 shadow-2xl">
                    {/* Header */}
                    <div className="text-center space-y-3 pb-6 border-b border-neutral-800/80">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500/20 to-teal-500/20 border border-emerald-500/40 text-emerald-400 shadow-lg shadow-emerald-500/20">
                            <CheckCircle2 className="h-9 w-9" />
                        </div>
                        <div>
                            <span className="inline-block rounded-full bg-emerald-500/10 border border-emerald-500/30 px-3 py-0.5 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2">
                                Booking Confirmed
                            </span>
                            <h1 className="text-2xl font-bold text-white tracking-tight">You're All Set!</h1>
                            <p className="text-xs text-neutral-400 mt-1">
                                An appointment has been reserved at <span className="text-neutral-200 font-medium">{booking.business.name}</span>.
                            </p>
                        </div>

                        {/* Reference code pill */}
                        <div className="inline-flex items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-950/80 px-4 py-2 mt-2">
                            <span className="text-xs text-neutral-400">Reference:</span>
                            <span className="font-mono text-sm font-bold text-cyan-400">{booking.reference_code}</span>
                            <button
                                onClick={handleCopy}
                                className="rounded p-1 text-neutral-400 hover:text-white transition"
                                title="Copy reference code"
                            >
                                {copied ? <Check className="h-4 w-4 text-emerald-400" /> : <Copy className="h-4 w-4" />}
                            </button>
                        </div>
                    </div>

                    {/* Appointment Details Card */}
                    <div className="py-6 space-y-4 border-b border-neutral-800/80 text-xs">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="flex items-start gap-3 rounded-2xl border border-neutral-800/60 bg-neutral-950/40 p-4">
                                <Calendar className="h-5 w-5 text-cyan-400 shrink-0 mt-0.5" />
                                <div>
                                    <p className="text-[11px] text-neutral-400 font-medium uppercase">Date & Time</p>
                                    <p className="font-semibold text-white mt-0.5">{formattedDate}</p>
                                    <p className="text-neutral-300 font-medium">{formattedTime}</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 rounded-2xl border border-neutral-800/60 bg-neutral-950/40 p-4">
                                <MapPin className="h-5 w-5 text-indigo-400 shrink-0 mt-0.5" />
                                <div>
                                    <p className="text-[11px] text-neutral-400 font-medium uppercase">Location</p>
                                    <p className="font-semibold text-white mt-0.5">{booking.location.name}</p>
                                    <p className="text-neutral-400">{booking.location.address}</p>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="flex items-start gap-3 rounded-2xl border border-neutral-800/60 bg-neutral-950/40 p-4">
                                <User className="h-5 w-5 text-amber-400 shrink-0 mt-0.5" />
                                <div>
                                    <p className="text-[11px] text-neutral-400 font-medium uppercase">Specialist</p>
                                    <p className="font-semibold text-white mt-0.5">{booking.employee?.name || 'First Available Specialist'}</p>
                                    <p className="text-neutral-400">{booking.service.name} ({booking.duration_minutes} min)</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 rounded-2xl border border-neutral-800/60 bg-neutral-950/40 p-4">
                                <ShieldCheck className="h-5 w-5 text-emerald-400 shrink-0 mt-0.5" />
                                <div>
                                    <p className="text-[11px] text-neutral-400 font-medium uppercase">Payment Status</p>
                                    {booking.deposit_amount > 0 ? (
                                        <>
                                            <p className="font-semibold text-emerald-400 mt-0.5">
                                                Hold Deposit Paid: ${booking.deposit_amount.toFixed(2)}
                                            </p>
                                            <p className="text-neutral-400">
                                                Balance due at visit: ${(booking.total_amount - booking.deposit_amount).toFixed(2)}
                                            </p>
                                        </>
                                    ) : (
                                        <>
                                            <p className="font-semibold text-white mt-0.5">Pay at Appointment</p>
                                            <p className="text-neutral-400">Total: ${booking.total_amount.toFixed(2)}</p>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* WhatsApp Automated Reminders Timeline */}
                    <div className="py-6 border-b border-neutral-800/80">
                        <div className="flex items-center gap-2 mb-3">
                            <MessageSquare className="h-4 w-4 text-emerald-400" />
                            <h3 className="text-xs font-bold text-white uppercase tracking-wider">
                                SlotSaver Intelligent WhatsApp Pipeline
                            </h3>
                        </div>
                        <p className="text-xs text-neutral-400 mb-4">
                            We will send real-time reminders with one-tap confirm and reschedule buttons directly to your WhatsApp.
                        </p>

                        <div className="space-y-3">
                            {[
                                {
                                    stage: '48h Checkpoint',
                                    desc: 'Gentle reminder sent via WhatsApp to secure calendar alignment.',
                                    icon: '48h',
                                },
                                {
                                    stage: '24h Interactive Checkpoint',
                                    desc: 'Interactive buttons: [Confirm Appointment] or [Reschedule / Cancel] with 0 friction.',
                                    icon: '24h',
                                },
                                {
                                    stage: '2h Directions & Arrival',
                                    desc: 'Final ping with Google Maps pin & specialist preparation notes.',
                                    icon: '2h',
                                },
                            ].map((step, idx) => (
                                <div key={idx} className="flex items-center gap-3 text-xs">
                                    <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-800 text-[10px] font-bold text-cyan-400 border border-neutral-700 shrink-0">
                                        {step.icon}
                                    </div>
                                    <div className="text-neutral-300">
                                        <span className="font-medium text-white">{step.stage}: </span>
                                        <span className="text-neutral-400">{step.desc}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Actions: Add to Calendar */}
                    <div className="pt-6 space-y-3">
                        <div className="flex flex-col sm:flex-row gap-3">
                            <a
                                href={googleCalUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-500/10 border border-cyan-500/30 py-2.5 px-4 text-xs font-semibold text-cyan-300 hover:bg-cyan-500/20 transition"
                            >
                                <ExternalLink className="h-3.5 w-3.5" />
                                Add to Google Calendar
                            </a>
                            <button
                                onClick={downloadIcs}
                                className="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-neutral-800 border border-neutral-700 py-2.5 px-4 text-xs font-semibold text-neutral-200 hover:bg-neutral-700 transition"
                            >
                                <Calendar className="h-3.5 w-3.5" />
                                Download iCal / Apple (.ics)
                            </button>
                        </div>

                        <div className="text-center pt-2">
                            <Link
                                href={`/book/${booking.business.name.toLowerCase().replace(/\s+/g, '-')}`}
                                className="text-xs text-neutral-400 hover:text-white transition"
                            >
                                ← Book another appointment
                            </Link>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
