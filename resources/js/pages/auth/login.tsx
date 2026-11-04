import BrandMark from '@/components/auth/brand-mark';
import RoleSelector, { type AuthRole } from '@/components/auth/role-selector';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowRight, Eye, EyeOff, LoaderCircle, LockKeyhole, UserRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type LoginForm = {
    identifier: string;
    password: string;
    role: AuthRole;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        identifier: '',
        password: '',
        role: 'student',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const authError = errors.identifier && /credential|too many|attempt/i.test(errors.identifier) ? errors.identifier : undefined;
    const identifierError = authError ? undefined : errors.identifier;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (processing) {
            return;
        }

        post(route('login'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthLayout showHeader={false} variant="plain">
            <Head title="Log in" />

            <div className="mb-7 text-center">
                <p className="text-[10px] font-semibold tracking-[0.34em] text-[#3d8075] uppercase">Welcome to ProctorAide</p>
                <BrandMark linked={false} className="mt-4 justify-center" wordmarkClassName="text-xl" />
                <h1 className="sr-only">Log in to ProctorAide</h1>
                <p className="mt-2 text-sm text-[#657a76]">Enter your details to continue.</p>
            </div>

            {status && (
                <div className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-sm text-emerald-700" role="status">
                    {status}
                </div>
            )}

            {authError && (
                <div
                    role="alert"
                    className="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700"
                >
                    <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <p>{authError}</p>
                </div>
            )}

            <form className="space-y-5" onSubmit={submit} noValidate>
                <div>
                    <span className="mb-2 block text-[13px] font-semibold text-[#294b46]">Select your role</span>
                    <RoleSelector value={data.role} onChange={(role) => setData('role', role)} disabled={processing} />
                    <InputError message={errors.role} id="login-role-error" className="mt-1.5" role="alert" />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="identifier" className="text-[13px] font-medium text-[#294b46]">
                        Email or student ID number
                    </Label>
                    <div className="relative">
                        <UserRound
                            className="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-[#6c807c]"
                            aria-hidden="true"
                        />
                        <Input
                            id="identifier"
                            name="identifier"
                            type="text"
                            required
                            autoFocus
                            autoComplete="username"
                            value={data.identifier}
                            onChange={(event) => setData('identifier', event.target.value)}
                            disabled={processing}
                            aria-invalid={Boolean(identifierError)}
                            aria-describedby={identifierError ? 'identifier-error' : undefined}
                            placeholder="you@university.edu or 2024-00001"
                            className="h-11 rounded-lg border-[#d7e2de] bg-white pl-10 text-sm text-[#123c37] caret-[#164f48] shadow-none placeholder:text-[#9aaba7] focus-visible:border-[#2d8a7b] focus-visible:ring-[#2d8a7b]/20 focus-visible:ring-offset-white"
                        />
                    </div>
                    <InputError message={identifierError} id="identifier-error" role="alert" />
                </div>

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between gap-3">
                        <Label htmlFor="password" className="text-[13px] font-medium text-[#294b46]">
                            Password
                        </Label>
                        {canResetPassword && (
                            <TextLink
                                href={route('password.request')}
                                className="text-xs font-medium text-[#2d7a6e] no-underline transition-colors hover:text-[#155e53] hover:underline"
                            >
                                Forgot password?
                            </TextLink>
                        )}
                    </div>
                    <div className="relative">
                        <LockKeyhole
                            className="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-[#6c807c]"
                            aria-hidden="true"
                        />
                        <Input
                            id="password"
                            name="password"
                            type={showPassword ? 'text' : 'password'}
                            required
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            disabled={processing}
                            aria-invalid={Boolean(errors.password)}
                            aria-describedby={errors.password ? 'password-error' : undefined}
                            placeholder="Enter your password"
                            className="h-11 rounded-lg border-[#d7e2de] bg-white pr-11 pl-10 text-sm text-[#123c37] caret-[#164f48] shadow-none placeholder:text-[#9aaba7] focus-visible:border-[#2d8a7b] focus-visible:ring-[#2d8a7b]/20 focus-visible:ring-offset-white"
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((visible) => !visible)}
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                            aria-pressed={showPassword}
                            className="absolute inset-y-0 right-0 z-10 flex w-11 items-center justify-center rounded-r-lg text-[#849995] transition-colors hover:text-[#1c6b5e] focus-visible:ring-2 focus-visible:ring-[#2d8a7b] focus-visible:outline-hidden"
                        >
                            {showPassword ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
                        </button>
                    </div>
                    <InputError message={errors.password} id="password-error" role="alert" />
                </div>

                <div className="flex items-center gap-2.5">
                    <Checkbox
                        id="remember"
                        name="remember"
                        checked={data.remember}
                        onCheckedChange={(checked) => setData('remember', checked === true)}
                        disabled={processing}
                        className="size-4 rounded border-[#b9cbc6] data-[state=checked]:border-[#164f48] data-[state=checked]:bg-[#164f48] data-[state=checked]:text-white"
                    />
                    <Label htmlFor="remember" className="text-[13px] font-normal text-[#4d6661]">
                        Keep me signed in
                    </Label>
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="group h-11 w-full rounded-lg bg-[#164f48] text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(6,47,44,0.85)] transition-colors hover:bg-[#103f39] focus-visible:ring-[#2d8a7b] focus-visible:ring-offset-2 disabled:opacity-70"
                >
                    {processing ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : null}
                    {processing ? 'Signing in...' : 'Login'}
                    {!processing && <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />}
                </Button>

                <p className="text-center text-[11px] leading-relaxed text-[#849691]">
                    By continuing, you confirm that you are authorized to access this system, and you agree to our Terms of Service and Privacy
                    Policy.
                </p>
            </form>

            <p className="mt-7 text-center text-sm text-[#6c807c]">
                Don't have an account?{' '}
                <TextLink
                    href={route('register')}
                    className="font-semibold text-[#1c6b5e] no-underline transition-colors hover:text-[#124f46] hover:underline"
                >
                    Sign up
                </TextLink>
            </p>
        </AuthLayout>
    );
}
