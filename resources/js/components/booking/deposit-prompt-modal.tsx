import React, { useState } from 'react';
import { ShieldCheck, Lock, CreditCard, Sparkles, Check } from 'lucide-react';

interface DepositPromptModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirmDeposit: () => void;
    depositAmount: number;
    servicePrice: number;
    serviceName: string;
    riskScore?: number;
    riskReason?: string;
}

export function DepositPromptModal({
    isOpen,
    onClose,
    onConfirmDeposit,
    depositAmount,
    servicePrice,
    serviceName,
    riskReason,
}: DepositPromptModalProps) {
    const [cardNumber, setCardNumber] = useState('4242 •••• •••• 4242');
    const [cardExpiry, setCardExpiry] = useState('12/28');
    const [cardCvc, setCardCvc] = useState('888');
    const [isProcessing, setIsProcessing] = useState(false);

    if (!isOpen) return null;

    const handlePay = (e: React.FormEvent) => {
        e.preventDefault();
        setIsProcessing(true);
        setTimeout(() => {
            setIsProcessing(false);
            onConfirmDeposit();
        }, 900);
    };

    return (
        <div className="fixed inset-0 z-50 flex animate-in items-center justify-center bg-black/75 p-4 backdrop-blur-sm duration-200 fade-in">
            <div className="relative w-full max-w-lg overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-neutral-100 shadow-2xl shadow-indigo-950/40">
                {/* Glowing subtle header gradient */}
                <div className="absolute -top-24 -left-24 h-48 w-48 rounded-full bg-indigo-500/10 blur-3xl" />
                <div className="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl" />

                {/* Header */}
                <div className="flex items-start justify-between border-b border-neutral-800 pb-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                            <ShieldCheck className="h-6 w-6" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold tracking-tight text-white">
                                Refundable Slot Hold Deposit
                            </h3>
                            <p className="text-xs text-neutral-400">
                                Securing high-demand appointment
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-neutral-400 transition hover:bg-neutral-800 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                {/* Explanation Banner */}
                <div className="mt-4 rounded-xl border border-neutral-800/80 bg-neutral-900/60 p-4">
                    <div className="flex items-start gap-2.5">
                        <Sparkles className="mt-0.5 h-4 w-4 shrink-0 text-amber-400" />
                        <div className="space-y-1 text-xs text-neutral-300">
                            <p className="font-medium text-white">
                                Why is a deposit required for this slot?
                            </p>
                            <p className="leading-relaxed text-neutral-400">
                                {riskReason ||
                                    'To protect our specialists from last-minute vacancies during peak periods, this reservation requires a small refundable deposit.'}
                            </p>
                            <p className="flex items-center gap-1.5 pt-1 font-medium text-emerald-400">
                                <Check className="h-3.5 w-3.5" />
                                100% credited towards your total at checkout &
                                fully refundable if cancelled 24h prior.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Pricing Breakdown */}
                <div className="mt-4 space-y-2 rounded-xl border border-neutral-800 bg-neutral-900/30 p-3.5 text-xs">
                    <div className="flex justify-between text-neutral-400">
                        <span>Service: {serviceName}</span>
                        <span className="font-medium text-neutral-200">
                            ${servicePrice.toFixed(2)}
                        </span>
                    </div>
                    <div className="flex items-center justify-between text-neutral-300">
                        <span className="font-semibold text-white">
                            Hold Deposit Due Today
                        </span>
                        <span className="text-base font-bold text-emerald-400">
                            ${depositAmount.toFixed(2)}
                        </span>
                    </div>
                    <div className="flex justify-between border-t border-neutral-800/60 pt-1 text-[11px] text-neutral-500">
                        <span>Balance due at appointment</span>
                        <span>
                            $
                            {Math.max(0, servicePrice - depositAmount).toFixed(
                                2,
                            )}
                        </span>
                    </div>
                </div>

                {/* Simulated Payment Card Form */}
                <form onSubmit={handlePay} className="mt-5 space-y-3">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-neutral-400">
                            Card Details
                        </label>
                        <div className="relative rounded-lg border border-neutral-800 bg-neutral-900/90 px-3 py-2.5 text-xs text-neutral-200 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                            <div className="flex items-center gap-2">
                                <CreditCard className="h-4 w-4 shrink-0 text-neutral-400" />
                                <input
                                    type="text"
                                    value={cardNumber}
                                    onChange={(e) =>
                                        setCardNumber(e.target.value)
                                    }
                                    className="w-full bg-transparent font-mono text-neutral-200 outline-none"
                                    placeholder="4242 •••• •••• 4242"
                                    required
                                />
                                <input
                                    type="text"
                                    value={cardExpiry}
                                    onChange={(e) =>
                                        setCardExpiry(e.target.value)
                                    }
                                    className="w-14 border-l border-neutral-800 bg-transparent pl-2 text-center font-mono text-neutral-300 outline-none"
                                    placeholder="MM/YY"
                                    required
                                />
                                <input
                                    type="text"
                                    value={cardCvc}
                                    onChange={(e) => setCardCvc(e.target.value)}
                                    className="w-10 border-l border-neutral-800 bg-transparent pl-2 text-center font-mono text-neutral-300 outline-none"
                                    placeholder="CVC"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 text-[11px] text-neutral-400">
                        <Lock className="h-3.5 w-3.5 shrink-0 text-emerald-400" />
                        <span>
                            Encrypted 256-bit Stripe sandbox. Card will only be
                            charged ${depositAmount.toFixed(2)}.
                        </span>
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-xl border border-neutral-800 bg-neutral-900 py-2.5 text-xs font-medium text-neutral-300 transition hover:bg-neutral-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={isProcessing}
                            className="flex-1 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 py-2.5 text-xs font-semibold text-neutral-950 shadow-lg shadow-emerald-500/20 transition hover:brightness-110 active:scale-[0.98] disabled:opacity-50"
                        >
                            {isProcessing
                                ? 'Authorizing Hold...'
                                : `Pay $${depositAmount.toFixed(2)} & Confirm`}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
