import React, { useState } from 'react';
import { ShieldCheck, Lock, AlertCircle, CreditCard, Sparkles, Check } from 'lucide-react';

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
    riskScore,
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-in fade-in duration-200">
            <div className="relative w-full max-w-lg overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-neutral-100 shadow-2xl shadow-indigo-950/40">
                {/* Glowing subtle header gradient */}
                <div className="absolute -top-24 -left-24 h-48 w-48 rounded-full bg-indigo-500/10 blur-3xl" />
                <div className="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl" />

                {/* Header */}
                <div className="flex items-start justify-between pb-4 border-b border-neutral-800">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400">
                            <ShieldCheck className="h-6 w-6" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold tracking-tight text-white">Refundable Slot Hold Deposit</h3>
                            <p className="text-xs text-neutral-400">Securing high-demand appointment</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-800 hover:text-white transition"
                    >
                        ✕
                    </button>
                </div>

                {/* Explanation Banner */}
                <div className="mt-4 rounded-xl border border-neutral-800/80 bg-neutral-900/60 p-4">
                    <div className="flex items-start gap-2.5">
                        <Sparkles className="h-4 w-4 text-amber-400 shrink-0 mt-0.5" />
                        <div className="text-xs text-neutral-300 space-y-1">
                            <p className="font-medium text-white">Why is a deposit required for this slot?</p>
                            <p className="text-neutral-400 leading-relaxed">
                                {riskReason ||
                                    'To protect our specialists from last-minute vacancies during peak periods, this reservation requires a small refundable deposit.'}
                            </p>
                            <p className="text-emerald-400 font-medium pt-1 flex items-center gap-1.5">
                                <Check className="h-3.5 w-3.5" />
                                100% credited towards your total at checkout & fully refundable if cancelled 24h prior.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Pricing Breakdown */}
                <div className="mt-4 rounded-xl border border-neutral-800 bg-neutral-900/30 p-3.5 space-y-2 text-xs">
                    <div className="flex justify-between text-neutral-400">
                        <span>Service: {serviceName}</span>
                        <span className="text-neutral-200 font-medium">${servicePrice.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between items-center text-neutral-300">
                        <span className="font-semibold text-white">Hold Deposit Due Today</span>
                        <span className="text-base font-bold text-emerald-400">${depositAmount.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-[11px] text-neutral-500 pt-1 border-t border-neutral-800/60">
                        <span>Balance due at appointment</span>
                        <span>${Math.max(0, servicePrice - depositAmount).toFixed(2)}</span>
                    </div>
                </div>

                {/* Simulated Payment Card Form */}
                <form onSubmit={handlePay} className="mt-5 space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-neutral-400 mb-1">Card Details</label>
                        <div className="relative rounded-lg border border-neutral-800 bg-neutral-900/90 px-3 py-2.5 text-xs text-neutral-200 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                            <div className="flex items-center gap-2">
                                <CreditCard className="h-4 w-4 text-neutral-400 shrink-0" />
                                <input
                                    type="text"
                                    value={cardNumber}
                                    onChange={(e) => setCardNumber(e.target.value)}
                                    className="w-full bg-transparent outline-none font-mono text-neutral-200"
                                    placeholder="4242 •••• •••• 4242"
                                    required
                                />
                                <input
                                    type="text"
                                    value={cardExpiry}
                                    onChange={(e) => setCardExpiry(e.target.value)}
                                    className="w-14 text-center bg-transparent outline-none font-mono text-neutral-300 border-l border-neutral-800 pl-2"
                                    placeholder="MM/YY"
                                    required
                                />
                                <input
                                    type="text"
                                    value={cardCvc}
                                    onChange={(e) => setCardCvc(e.target.value)}
                                    className="w-10 text-center bg-transparent outline-none font-mono text-neutral-300 border-l border-neutral-800 pl-2"
                                    placeholder="CVC"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 text-[11px] text-neutral-400">
                        <Lock className="h-3.5 w-3.5 text-emerald-400 shrink-0" />
                        <span>Encrypted 256-bit Stripe sandbox. Card will only be charged ${depositAmount.toFixed(2)}.</span>
                    </div>

                    <div className="pt-2 flex gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-xl border border-neutral-800 bg-neutral-900 py-2.5 text-xs font-medium text-neutral-300 hover:bg-neutral-800 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={isProcessing}
                            className="flex-1 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 py-2.5 text-xs font-semibold text-neutral-950 hover:brightness-110 active:scale-[0.98] transition shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                        >
                            {isProcessing ? 'Authorizing Hold...' : `Pay $${depositAmount.toFixed(2)} & Confirm`}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
