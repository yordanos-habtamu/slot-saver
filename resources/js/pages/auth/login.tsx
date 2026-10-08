import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */
import { store } from '@/routes/login';
import { request } from '@/routes/password';
/* @chisel-passkeys */
import PasskeyVerify from '@/components/passkey-verify';
/* @end-chisel-passkeys */
import {
    Sparkles,
    Shield,
    UserCheck,
    Scissors,
    ArrowRight,
} from 'lucide-react';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

const DEMO_PRESETS = [
    {
        role: 'Salon Owner',
        name: 'Mateo Silva',
        email: 'mateo@crownblade.test',
        icon: Shield,
        color: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        badge: 'Owner',
    },
    {
        role: 'Specialist',
        name: 'André Rocha',
        email: 'andre@crownblade.test',
        icon: Scissors,
        color: 'text-cyan-400 bg-cyan-400/10 border-cyan-400/30',
        badge: 'Staff',
    },
    {
        role: 'Client',
        name: 'Carlos Gomes',
        email: 'client1@example.com',
        icon: UserCheck,
        color: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30',
        badge: 'Customer',
    },
    {
        role: 'Admin',
        name: 'Platform Admin',
        email: 'admin@slotsaver.test',
        icon: Sparkles,
        color: 'text-indigo-400 bg-indigo-400/10 border-indigo-400/30',
        badge: 'Superadmin',
    },
];

export default function Login({ status, canResetPassword }: Props) {
    const [activePreset, setActivePreset] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(), {
            onFinish: () => reset('password'),
        });
    };

    const handleApplyPreset = (presetEmail: string) => {
        setActivePreset(presetEmail);
        setData((prev) => ({
            ...prev,
            email: presetEmail,
            password: 'password',
        }));
    };

    return (
        <>
            <Head title="Log In" />

            {/* @chisel-passkeys */}
            <PasskeyVerify />
            {/* @end-chisel-passkeys */}

            {status && (
                <div className="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-center text-sm font-medium text-emerald-400">
                    {status}
                </div>
            )}

            {/* Quick 1-Click Demo Login Bar */}
            <div className="mb-6 rounded-xl border border-neutral-800 bg-neutral-950/60 p-4">
                <div className="mb-2.5 flex items-center justify-between">
                    <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                        <Sparkles className="h-3.5 w-3.5 text-cyan-400" />
                        1-Click Demo Test Drives
                    </span>
                    <span className="text-[11px] text-neutral-400">
                        Pass: password
                    </span>
                </div>
                <div className="grid grid-cols-2 gap-2">
                    {DEMO_PRESETS.map((preset) => {
                        const isSelected = activePreset === preset.email;
                        return (
                            <button
                                key={preset.email}
                                type="button"
                                onClick={() => handleApplyPreset(preset.email)}
                                className={`group flex flex-col rounded-lg border p-2.5 text-left transition-all ${
                                    isSelected
                                        ? 'border-cyan-500 bg-cyan-950/30 shadow-md shadow-cyan-500/10'
                                        : 'border-neutral-800 bg-neutral-900/60 hover:border-neutral-700 hover:bg-neutral-800/50'
                                }`}
                            >
                                <div className="mb-1 flex w-full items-center justify-between">
                                    <span className="truncate text-xs font-medium text-white transition-colors group-hover:text-cyan-400">
                                        {preset.name}
                                    </span>
                                    <span
                                        className={`py-0.2 inline-flex items-center rounded border px-1.5 text-[10px] font-semibold ${preset.color}`}
                                    >
                                        {preset.badge}
                                    </span>
                                </div>
                                <span className="truncate text-[11px] text-neutral-400">
                                    {preset.email}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label
                        htmlFor="email"
                        className="text-xs font-medium text-neutral-300"
                    >
                        Email Address
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="email"
                        placeholder="you@example.com"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center justify-between">
                        <Label
                            htmlFor="password"
                            className="text-xs font-medium text-neutral-300"
                        >
                            Password
                        </Label>
                        {canResetPassword && (
                            <TextLink
                                href={request()}
                                className="text-xs text-neutral-400 transition-colors hover:text-cyan-400"
                                tabIndex={5}
                            >
                                Forgot password?
                            </TextLink>
                        )}
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        tabIndex={2}
                        autoComplete="current-password"
                        placeholder="••••••••"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="remember"
                        name="remember"
                        tabIndex={3}
                        checked={data.remember}
                        onCheckedChange={(checked) =>
                            setData('remember', checked === true)
                        }
                        className="border-neutral-700 data-[state=checked]:border-cyan-500 data-[state=checked]:bg-cyan-500"
                    />
                    <Label
                        htmlFor="remember"
                        className="cursor-pointer text-xs text-neutral-400 select-none"
                    >
                        Remember this device for 30 days
                    </Label>
                </div>

                <Button
                    type="submit"
                    className="mt-2 w-full bg-gradient-to-r from-cyan-500 to-indigo-600 font-semibold text-neutral-950 shadow-lg shadow-cyan-500/20 transition-all hover:from-cyan-400 hover:to-indigo-500 hover:shadow-cyan-500/30"
                    tabIndex={4}
                    disabled={processing}
                    data-test="login-button"
                >
                    {processing ? (
                        <Spinner className="text-neutral-950" />
                    ) : (
                        <ArrowRight className="mr-1.5 h-4 w-4" />
                    )}
                    Sign In to SlotSaver
                </Button>

                {/* @chisel-registration */}
                <div className="mt-2 space-y-2 border-t border-neutral-800/80 pt-4 text-center text-xs text-neutral-400">
                    <div>
                        Don't have an account yet?{' '}
                        <TextLink
                            href={register()}
                            className="font-medium text-cyan-400 transition-colors hover:text-cyan-300"
                            tabIndex={6}
                        >
                            Create an account
                        </TextLink>
                    </div>
                    <div className="flex items-center justify-center gap-3 text-[11px] text-neutral-400">
                        <a
                            href="/register?role=client"
                            className="underline hover:text-neutral-300"
                        >
                            Register as Client
                        </a>
                        <span>•</span>
                        <a
                            href="/register?role=owner"
                            className="underline hover:text-neutral-300"
                        >
                            Register as Salon Owner
                        </a>
                    </div>
                </div>
                {/* @end-chisel-registration */}
            </form>
        </>
    );
}

Login.layout = {
    title: 'Sign in to SlotSaver',
    description:
        'Access appointment operations, automated waitlists, and ML risk scoring',
};
