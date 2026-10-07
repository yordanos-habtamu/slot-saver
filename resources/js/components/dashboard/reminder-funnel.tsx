import React from 'react';
import {
    MessageSquare,
    CheckCheck,
    Eye,
    ThumbsUp,
    CalendarClock,
    UserX,
    AlertCircle,
    ShieldAlert,
} from 'lucide-react';

interface ReminderFunnelProps {
    funnel: {
        sent: number;
        delivered: number;
        read: number;
        confirmed: number;
        rescheduled: number;
        cancelled: number;
        no_show: number;
    };
    riskDistribution: {
        low: number;
        medium: number;
        high: number;
    };
}

export function ReminderFunnel({
    funnel,
    riskDistribution,
}: ReminderFunnelProps) {
    const pctOf = (value: number) =>
        funnel.sent > 0 ? Math.round((value / funnel.sent) * 100) : 0;
    const reach = pctOf(funnel.delivered);
    const riskTotal = Math.max(
        1,
        riskDistribution.low + riskDistribution.medium + riskDistribution.high,
    );
    const riskPct = {
        low: Math.round((riskDistribution.low / riskTotal) * 100),
        medium: Math.round((riskDistribution.medium / riskTotal) * 100),
        high: Math.round((riskDistribution.high / riskTotal) * 100),
    };

    const stages = [
        {
            key: 'sent',
            label: 'Sent',
            count: funnel.sent,
            pct: 100,
            color: 'from-blue-500 to-cyan-500',
            icon: MessageSquare,
            sub: 'Dispatched via WhatsApp API',
        },
        {
            key: 'delivered',
            label: 'Delivered',
            count: funnel.delivered,
            pct: pctOf(funnel.delivered),
            color: 'from-cyan-500 to-teal-500',
            icon: CheckCheck,
            sub: 'Handset delivery receipt verified',
        },
        {
            key: 'read',
            label: 'Read',
            count: funnel.read,
            pct: pctOf(funnel.read),
            color: 'from-teal-500 to-emerald-500',
            icon: Eye,
            sub: 'WhatsApp read receipt',
        },
        {
            key: 'confirmed',
            label: 'Confirmed',
            count: funnel.confirmed,
            pct: pctOf(funnel.confirmed),
            color: 'from-emerald-500 to-green-500',
            icon: ThumbsUp,
            sub: '1-tap interactive button clicked',
        },
        {
            key: 'rescheduled',
            label: 'Rescheduled',
            count: funnel.rescheduled,
            pct: pctOf(funnel.rescheduled),
            color: 'from-amber-500 to-yellow-500',
            icon: CalendarClock,
            sub: 'Slot shifted ahead of time',
        },
        {
            key: 'cancelled',
            label: 'Cancelled (Refilled)',
            count: funnel.cancelled,
            pct: pctOf(funnel.cancelled),
            color: 'from-indigo-500 to-purple-500',
            icon: AlertCircle,
            sub: 'Freed slot offered to waitlist',
        },
        {
            key: 'no_show',
            label: 'No-Show',
            count: funnel.no_show,
            pct: pctOf(funnel.no_show),
            color: 'from-rose-500 to-red-600',
            icon: UserX,
            sub: 'Deposit forfeited',
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {/* Reminder Funnel (8 cols) */}
            <div className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6 backdrop-blur-md lg:col-span-8">
                <div className="flex items-center justify-between border-b border-neutral-800/80 pb-4">
                    <div>
                        <h3 className="flex items-center gap-2 text-base font-bold tracking-tight text-white">
                            <MessageSquare className="h-4 w-4 text-emerald-400" />
                            WhatsApp Reminder & Confirmation Funnel
                        </h3>
                        <p className="mt-0.5 text-xs text-neutral-400">
                            Conversion through 48h and 24h multi-channel
                            touchpoints
                        </p>
                    </div>
                    <span className="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-400">
                        {reach}% Reach
                    </span>
                </div>

                <div className="mt-5 space-y-3.5">
                    {stages.map((stage) => {
                        const Icon = stage.icon;
                        return (
                            <div key={stage.key} className="space-y-1.5">
                                <div className="flex items-center justify-between text-xs">
                                    <div className="flex items-center gap-2">
                                        <Icon className="h-3.5 w-3.5 text-neutral-400" />
                                        <span className="font-semibold text-white">
                                            {stage.label}
                                        </span>
                                        <span className="hidden text-[11px] text-neutral-500 sm:inline">
                                            ({stage.sub})
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono font-bold text-neutral-300">
                                            {stage.count}
                                        </span>
                                        <span className="w-10 text-right text-[11px] text-neutral-500">
                                            {stage.pct}%
                                        </span>
                                    </div>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-neutral-800/80">
                                    <div
                                        className={`h-full rounded-full bg-gradient-to-r ${stage.color} transition-all duration-500`}
                                        style={{
                                            width: `${Math.max(4, stage.pct)}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Attendance Risk Cohort Distribution (4 cols) */}
            <div className="flex flex-col justify-between rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6 backdrop-blur-md lg:col-span-4">
                <div>
                    <div className="flex items-center justify-between border-b border-neutral-800/80 pb-4">
                        <div>
                            <h3 className="flex items-center gap-2 text-base font-bold tracking-tight text-white">
                                <ShieldAlert className="h-4 w-4 text-cyan-400" />
                                Risk Distribution
                            </h3>
                            <p className="mt-0.5 text-xs text-neutral-400">
                                ML Logistic Regression scores
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 space-y-4">
                        {/* Low Risk */}
                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-950/40 p-3.5">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-emerald-400">
                                    Low Risk (&lt; 0.35)
                                </span>
                                <span className="font-bold text-white">
                                    {riskPct.low}%{' '}
                                    <span className="font-normal text-neutral-500">
                                        ({riskDistribution.low})
                                    </span>
                                </span>
                            </div>
                            <p className="mt-1 text-[11px] text-neutral-400">
                                Standard booking with WhatsApp reminders only.
                            </p>
                            <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-800">
                                <div
                                    className="h-full rounded-full bg-emerald-500"
                                    style={{ width: `${riskPct.low}%` }}
                                />
                            </div>
                        </div>

                        {/* Medium Risk */}
                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-950/40 p-3.5">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-amber-400">
                                    Medium Risk (0.35 - 0.65)
                                </span>
                                <span className="font-bold text-white">
                                    {riskPct.medium}%{' '}
                                    <span className="font-normal text-neutral-500">
                                        ({riskDistribution.medium})
                                    </span>
                                </span>
                            </div>
                            <p className="mt-1 text-[11px] text-neutral-400">
                                Accelerated 48h/24h reminder cadence.
                            </p>
                            <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-800">
                                <div
                                    className="h-full rounded-full bg-amber-500"
                                    style={{ width: `${riskPct.medium}%` }}
                                />
                            </div>
                        </div>

                        {/* High Risk */}
                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-950/40 p-3.5">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-rose-400">
                                    High Risk (&ge; 0.65)
                                </span>
                                <span className="font-bold text-white">
                                    {riskPct.high}%{' '}
                                    <span className="font-normal text-neutral-500">
                                        ({riskDistribution.high})
                                    </span>
                                </span>
                            </div>
                            <p className="mt-1 text-[11px] text-neutral-400">
                                Dynamic refundable hold deposit enforced.
                            </p>
                            <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-800">
                                <div
                                    className="h-full rounded-full bg-rose-500"
                                    style={{ width: `${riskPct.high}%` }}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="border-t border-neutral-800/60 pt-4 text-center text-[11px] text-neutral-500">
                    VIP clients (5+ visits, 0 no-shows) automatically exempted
                    from deposits.
                </div>
            </div>
        </div>
    );
}
