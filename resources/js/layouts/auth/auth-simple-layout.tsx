import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import { Sparkles } from 'lucide-react';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative min-h-screen bg-neutral-950 text-neutral-100 flex flex-col justify-center items-center p-4 sm:p-6 selection:bg-cyan-500 selection:text-neutral-950 overflow-x-hidden">
            {/* Glowing aesthetic ambient lights */}
            <div className="fixed inset-0 pointer-events-none overflow-hidden">
                <div className="absolute top-1/6 left-1/4 h-80 w-80 rounded-full bg-cyan-500/10 blur-[120px]" />
                <div className="absolute bottom-1/6 right-1/4 h-80 w-80 rounded-full bg-indigo-500/10 blur-[140px]" />
            </div>

            <div className="w-full max-w-lg relative z-10 my-8">
                {/* Brand header */}
                <div className="flex flex-col items-center gap-3 mb-6 text-center">
                    <Link
                        href={home()}
                        className="group inline-flex items-center gap-3 transition-transform hover:scale-105"
                    >
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 font-bold text-neutral-950 shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition-shadow">
                            SS
                        </div>
                        <div className="text-left">
                            <div className="flex items-center gap-2">
                                <span className="text-xl font-bold tracking-tight text-white">
                                    SlotSaver
                                </span>
                                <span className="inline-flex items-center gap-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 px-2 py-0.5 text-[10px] font-semibold text-cyan-400">
                                    <Sparkles className="h-2.5 w-2.5" />
                                    v2.0
                                </span>
                            </div>
                            <p className="text-xs text-neutral-400">
                                Automated Appointment Operating System
                            </p>
                        </div>
                    </Link>

                    {title && (
                        <div className="mt-2 space-y-1">
                            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-sm text-neutral-400 max-w-md mx-auto">
                                    {description}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {/* Glassmorphic card container */}
                <div className="rounded-2xl border border-neutral-800/80 bg-neutral-900/70 p-6 sm:p-8 backdrop-blur-xl shadow-2xl">
                    {children}
                </div>

                {/* Footer copyright */}
                <p className="mt-6 text-center text-xs text-neutral-400">
                    &copy; {new Date().getFullYear()} SlotSaver. Built for modern European salons & clinics.
                </p>
            </div>
        </div>
    );
}
