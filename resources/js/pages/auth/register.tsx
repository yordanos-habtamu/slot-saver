import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import {
    User,
    Building2,
    Sparkles,
    CheckCircle2,
    ArrowRight,
    ShieldCheck,
} from 'lucide-react';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules: _passwordRules }: Props) {
    // Detect role from URL query param if present
    const [selectedRole, setSelectedRole] = useState<'client' | 'owner'>(
        'client',
    );

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const roleParam = params.get('role');
        if (roleParam === 'owner' || roleParam === 'business') {
            setSelectedRole('owner');
        } else if (roleParam === 'client') {
            setSelectedRole('client');
        }
    }, []);

    const { data, setData, post, processing, errors, reset } = useForm({
        role: selectedRole,
        name: '',
        business_name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    // Keep data.role in sync with selectedRole
    const handleRoleChange = (newRole: 'client' | 'owner') => {
        setSelectedRole(newRole);
        setData('role', newRole);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head
                title={
                    selectedRole === 'owner'
                        ? 'Register Business — SlotSaver'
                        : 'Create Client Account — SlotSaver'
                }
            />

            {/* Role Segmented Selector */}
            <div className="mb-6">
                <Label className="mb-2.5 block text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                    Choose Your Account Type
                </Label>
                <div className="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        onClick={() => handleRoleChange('client')}
                        className={`flex flex-col items-center rounded-xl border p-3.5 text-left transition-all sm:items-start ${
                            selectedRole === 'client'
                                ? 'border-cyan-500 bg-cyan-950/30 shadow-md ring-1 shadow-cyan-500/10 ring-cyan-500/30'
                                : 'border-neutral-800 bg-neutral-900/60 opacity-70 hover:border-neutral-700 hover:bg-neutral-800/40 hover:opacity-100'
                        }`}
                    >
                        <div className="mb-1 flex items-center gap-2">
                            <div
                                className={`flex h-7 w-7 items-center justify-center rounded-lg ${
                                    selectedRole === 'client'
                                        ? 'bg-cyan-500 text-neutral-950'
                                        : 'bg-neutral-800 text-neutral-300'
                                }`}
                            >
                                <User className="h-4 w-4" />
                            </div>
                            <span className="text-sm font-semibold text-white">
                                Client / Guest
                            </span>
                        </div>
                        <p className="hidden text-[11px] leading-snug text-neutral-400 sm:block">
                            Book appointments, get WhatsApp reminders, &
                            auto-refills.
                        </p>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleRoleChange('owner')}
                        className={`flex flex-col items-center rounded-xl border p-3.5 text-left transition-all sm:items-start ${
                            selectedRole === 'owner'
                                ? 'border-indigo-500 bg-indigo-950/30 shadow-md ring-1 shadow-indigo-500/10 ring-indigo-500/30'
                                : 'border-neutral-800 bg-neutral-900/60 opacity-70 hover:border-neutral-700 hover:bg-neutral-800/40 hover:opacity-100'
                        }`}
                    >
                        <div className="mb-1 flex items-center gap-2">
                            <div
                                className={`flex h-7 w-7 items-center justify-center rounded-lg ${
                                    selectedRole === 'owner'
                                        ? 'bg-indigo-500 text-white'
                                        : 'bg-neutral-800 text-neutral-300'
                                }`}
                            >
                                <Building2 className="h-4 w-4" />
                            </div>
                            <span className="text-sm font-semibold text-white">
                                Salon / Clinic
                            </span>
                        </div>
                        <p className="hidden text-[11px] leading-snug text-neutral-400 sm:block">
                            Eliminate no-shows, automate waitlists, & manage
                            chairs.
                        </p>
                    </button>
                </div>
            </div>

            {/* Role Benefit Banner */}
            {selectedRole === 'client' ? (
                <div className="mb-6 rounded-xl border border-cyan-500/20 bg-cyan-950/20 p-3.5 text-xs text-neutral-300">
                    <div className="mb-1 flex items-center gap-1.5 font-medium text-cyan-400">
                        <Sparkles className="h-3.5 w-3.5" />
                        Client Benefits
                    </div>
                    <ul className="grid grid-cols-1 gap-1.5 text-[11px] text-neutral-400 sm:grid-cols-2">
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-cyan-400" />
                            1-tap WhatsApp confirmation
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-cyan-400" />
                            Instant calendar sync
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-cyan-400" />
                            Priority waitlist alerts
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-cyan-400" />
                            Zero spam, end-to-end secure
                        </li>
                    </ul>
                </div>
            ) : (
                <div className="mb-6 rounded-xl border border-indigo-500/20 bg-indigo-950/20 p-3.5 text-xs text-neutral-300">
                    <div className="mb-1 flex items-center gap-1.5 font-medium text-indigo-400">
                        <ShieldCheck className="h-3.5 w-3.5" />
                        Salon Owner 14-Day Free Trial
                    </div>
                    <ul className="grid grid-cols-1 gap-1.5 text-[11px] text-neutral-400 sm:grid-cols-2">
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-indigo-400" />
                            -79% average no-show drop
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-indigo-400" />
                            15-min waitlist auto-refill
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-indigo-400" />
                            Offline tablet PWA reception
                        </li>
                        <li className="flex items-center gap-1">
                            <CheckCircle2 className="h-3 w-3 shrink-0 text-indigo-400" />
                            No credit card required
                        </li>
                    </ul>
                </div>
            )}

            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                {/* Hidden role input */}
                <input type="hidden" name="role" value={selectedRole} />

                {/* Owner Specific: Business Name */}
                {selectedRole === 'owner' && (
                    <div className="grid gap-1.5">
                        <Label
                            htmlFor="business_name"
                            className="flex items-center justify-between text-xs font-medium text-neutral-300"
                        >
                            <span>Salon / Clinic / Business Name</span>
                            <span className="text-[10px] text-indigo-400">
                                Auto-configured
                            </span>
                        </Label>
                        <Input
                            id="business_name"
                            type="text"
                            required
                            tabIndex={1}
                            placeholder="e.g. Luxe Fade Barbershop, Glow Studio"
                            value={data.business_name}
                            onChange={(e) =>
                                setData('business_name', e.target.value)
                            }
                            className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-indigo-500 focus:ring-indigo-500/20"
                        />
                        <InputError message={errors.business_name} />
                    </div>
                )}

                {/* Name */}
                <div className="grid gap-1.5">
                    <Label
                        htmlFor="name"
                        className="text-xs font-medium text-neutral-300"
                    >
                        {selectedRole === 'owner'
                            ? 'Owner Full Name'
                            : 'Full Name'}
                    </Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autoFocus
                        tabIndex={2}
                        autoComplete="name"
                        placeholder={
                            selectedRole === 'owner'
                                ? 'Mateo Silva'
                                : 'Carlos Gomes'
                        }
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                    />
                    <InputError message={errors.name} />
                </div>

                {/* Phone / WhatsApp */}
                <div className="grid gap-1.5">
                    <Label
                        htmlFor="phone"
                        className="flex items-center justify-between text-xs font-medium text-neutral-300"
                    >
                        <span>WhatsApp / Mobile Number</span>
                        <span className="text-[10px] text-neutral-400">
                            For 2-way reminders
                        </span>
                    </Label>
                    <Input
                        id="phone"
                        type="tel"
                        tabIndex={3}
                        autoComplete="tel"
                        placeholder="+351 912 345 678"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                    />
                    <InputError message={errors.phone} />
                </div>

                {/* Email */}
                <div className="grid gap-1.5">
                    <Label
                        htmlFor="email"
                        className="text-xs font-medium text-neutral-300"
                    >
                        Email Address
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        tabIndex={4}
                        autoComplete="email"
                        placeholder="you@example.com"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                    />
                    <InputError message={errors.email} />
                </div>

                {/* Password Fields */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div className="grid gap-1.5">
                        <Label
                            htmlFor="password"
                            className="text-xs font-medium text-neutral-300"
                        >
                            Password
                        </Label>
                        <PasswordInput
                            id="password"
                            required
                            tabIndex={5}
                            autoComplete="new-password"
                            placeholder="••••••••"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label
                            htmlFor="password_confirmation"
                            className="text-xs font-medium text-neutral-300"
                        >
                            Confirm Password
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            required
                            tabIndex={6}
                            autoComplete="new-password"
                            placeholder="••••••••"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            className="border-neutral-800 bg-neutral-950/70 text-white placeholder:text-neutral-600 focus:border-cyan-500 focus:ring-cyan-500/20"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>
                </div>

                {/* Submit Button */}
                <Button
                    type="submit"
                    className={`mt-3 w-full font-semibold text-neutral-950 shadow-lg transition-all ${
                        selectedRole === 'owner'
                            ? 'bg-gradient-to-r from-indigo-500 to-cyan-400 shadow-indigo-500/20 hover:from-indigo-400 hover:to-cyan-300 hover:shadow-indigo-500/30'
                            : 'bg-gradient-to-r from-cyan-500 to-indigo-600 shadow-cyan-500/20 hover:from-cyan-400 hover:to-indigo-500 hover:shadow-cyan-500/30'
                    }`}
                    tabIndex={7}
                    disabled={processing}
                    data-test="register-user-button"
                >
                    {processing ? (
                        <Spinner className="text-neutral-950" />
                    ) : (
                        <>
                            <ArrowRight className="mr-1.5 h-4 w-4" />
                            {selectedRole === 'owner'
                                ? 'Create Salon & Start Free Trial'
                                : 'Complete Client Registration'}
                        </>
                    )}
                </Button>

                {/* Switcher to Login */}
                <div className="mt-2 border-t border-neutral-800/80 pt-4 text-center text-xs text-neutral-400">
                    Already have an account?{' '}
                    <TextLink
                        href={login()}
                        className="font-medium text-cyan-400 transition-colors hover:text-cyan-300"
                        tabIndex={8}
                    >
                        Sign in to your account
                    </TextLink>
                </div>
            </form>
        </>
    );
}

Register.layout = {
    title: 'Get Started with SlotSaver',
    description:
        'Select your role to configure your personalized appointment experience',
};
