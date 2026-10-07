import React from 'react';
import {
    TrendingDown,
    DollarSign,
    RefreshCw,
    Clock,
    ArrowDownRight,
    ArrowUpRight,
    ShieldCheck,
    Zap,
} from 'lucide-react';

interface KpiCardsProps {
    kpis: {
        no_show_rate: number;
        baseline_no_show_rate: number;
        reduction_percentage: number;
        recovered_revenue: number;
        refilled_slots_count: number;
        admin_hours_saved: number;
    };
}

export function KpiCards({ kpis }: KpiCardsProps) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {/* 1. No-Show Rate */}
            <div className="relative overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900/60 p-5 shadow-lg backdrop-blur-md">
                <div className="pointer-events-none absolute -top-12 -right-12 h-24 w-24 rounded-full bg-emerald-500/10 blur-2xl" />
                <div className="flex items-center justify-between pb-3">
                    <span className="text-xs font-medium tracking-wider text-neutral-400 uppercase">
                        No-Show Rate
                    </span>
                    <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
                        <TrendingDown className="h-5 w-5" />
                    </div>
                </div>
                <div className="flex items-baseline gap-2">
                    <span className="text-3xl font-extrabold tracking-tight text-white">
                        {kpis.no_show_rate}%
                    </span>
                    <span className="text-xs text-neutral-500 line-through">
                        from {kpis.baseline_no_show_rate}%
                    </span>
                </div>
                <div className="mt-3 flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                    {kpis.reduction_percentage >= 0 ? (
                        <span className="flex items-center gap-0.5 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 text-[11px]">
                            <ArrowDownRight className="h-3 w-3" />
                            {kpis.reduction_percentage}% drop
                        </span>
                    ) : (
                        <span className="flex items-center gap-0.5 rounded-md border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 text-[11px] text-rose-400">
                            <ArrowUpRight className="h-3 w-3" />
                            {Math.abs(kpis.reduction_percentage)}% rise
                        </span>
                    )}
                    <span className="text-[11px] font-normal text-neutral-400">
                        relative to baseline
                    </span>
                </div>
            </div>

            {/* 2. Recovered Revenue */}
            <div className="relative overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900/60 p-5 shadow-lg backdrop-blur-md">
                <div className="pointer-events-none absolute -top-12 -right-12 h-24 w-24 rounded-full bg-cyan-500/10 blur-2xl" />
                <div className="flex items-center justify-between pb-3">
                    <span className="text-xs font-medium tracking-wider text-neutral-400 uppercase">
                        Recovered Revenue
                    </span>
                    <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/10 text-cyan-400">
                        <DollarSign className="h-5 w-5" />
                    </div>
                </div>
                <div className="flex items-baseline gap-2">
                    <span className="text-3xl font-extrabold tracking-tight text-white">
                        $
                        {kpis.recovered_revenue.toLocaleString('en-US', {
                            minimumFractionDigits: 0,
                        })}
                    </span>
                    <span className="text-xs text-neutral-400">recovered</span>
                </div>
                <div className="mt-3 flex items-center gap-1.5 text-xs font-semibold text-cyan-400">
                    <span className="flex items-center gap-0.5 rounded-md border border-cyan-500/20 bg-cyan-500/10 px-1.5 py-0.5 text-[11px]">
                        <ArrowUpRight className="h-3 w-3" /> +
                        {kpis.refilled_slots_count} refills
                    </span>
                    <span className="text-[11px] font-normal text-neutral-400">
                        from waitlist auto-fill
                    </span>
                </div>
            </div>

            {/* 3. Refilled Slots */}
            <div className="relative overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900/60 p-5 shadow-lg backdrop-blur-md">
                <div className="pointer-events-none absolute -top-12 -right-12 h-24 w-24 rounded-full bg-indigo-500/10 blur-2xl" />
                <div className="flex items-center justify-between pb-3">
                    <span className="text-xs font-medium tracking-wider text-neutral-400 uppercase">
                        Slots Refilled
                    </span>
                    <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
                        <RefreshCw className="h-4 w-4" />
                    </div>
                </div>
                <div className="flex items-baseline gap-2">
                    <span className="text-3xl font-extrabold tracking-tight text-white">
                        {kpis.refilled_slots_count}
                    </span>
                    <span className="text-xs text-neutral-400">
                        cancelled slots saved
                    </span>
                </div>
                <div className="mt-3 flex items-center gap-1.5 text-xs font-semibold text-indigo-400">
                    <span className="flex items-center gap-0.5 rounded-md border border-indigo-500/20 bg-indigo-500/10 px-1.5 py-0.5 text-[11px]">
                        <Zap className="h-3 w-3" /> Auto-fill
                    </span>
                    <span className="text-[11px] font-normal text-neutral-400">
                        15-minute claim window
                    </span>
                </div>
            </div>

            {/* 4. Admin Time Saved */}
            <div className="relative overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900/60 p-5 shadow-lg backdrop-blur-md">
                <div className="pointer-events-none absolute -top-12 -right-12 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl" />
                <div className="flex items-center justify-between pb-3">
                    <span className="text-xs font-medium tracking-wider text-neutral-400 uppercase">
                        Staff Time Saved
                    </span>
                    <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-400">
                        <Clock className="h-5 w-5" />
                    </div>
                </div>
                <div className="flex items-baseline gap-2">
                    <span className="text-3xl font-extrabold tracking-tight text-white">
                        {kpis.admin_hours_saved}
                    </span>
                    <span className="text-xs text-neutral-400">hrs / mo</span>
                </div>
                <div className="mt-3 flex items-center gap-1.5 text-xs font-semibold text-amber-400">
                    <span className="flex items-center gap-0.5 rounded-md border border-amber-500/20 bg-amber-500/10 px-1.5 py-0.5 text-[11px]">
                        <ShieldCheck className="h-3 w-3" /> Automated
                    </span>
                    <span className="text-[11px] font-normal text-neutral-400">
                        replaces phone calling
                    </span>
                </div>
            </div>
        </div>
    );
}
