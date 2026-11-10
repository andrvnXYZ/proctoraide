import BrandMark from '@/components/auth/brand-mark';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface ForgotPasswordProps {
    status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        identifier: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (processing) {
            return;
        }

        post(route('password.email'));
    };

    const fieldError = errors.identifier;

    return (
        <AuthLayout showHeader={false}>
            <Head title="Forgot password" />

            <div className="text-center">
                <p className="text-[10px] font-semibold tracking-[0.34em] text-[#3d8075] uppercase">Welcome to ProctorAide</p>
                <BrandMark linked={false} className="mt-4 justify-center" wordmarkClassName="text-[1.65rem]" />
                <p className="mt-2 text-sm text-[#657a76]">Account recovery</p>

                <div className="mx-auto mt-5 flex w-full max-w-[21rem] items-center gap-2" aria-hidden="true">
                    <span className="h-[3px] flex-1 rounded-full bg-[#164f48]" />
                    <span className="h-px flex-1 bg-[#dbe5e2]" />
                    <span className="h-px flex-1 bg-[#dbe5e2]" />
                </div>

                <span className="mt-5 inline-flex rounded-full bg-[#f4e9c7] px-3.5 py-1 text-[11px] font-semibold text-[#6b5426]">
                    Student account
                </span>

                <h1 className="mt-4 text-[25px] leading-tight font-semibold tracking-tight text-[#103f39]">Forgot your password?</h1>
                <p className="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-[#657a76]">
                    Enter your student ID number or the email linked to your account. We&apos;ll send a secure reset link to your registered email.
                </p>
            </div>

            {status && (
                <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-sm text-emerald-700" role="status">
                    {status}
                </div>
            )}

            <form className="mt-6 space-y-4" onSubmit={submit} noValidate>
                <div className="space-y-1.5">
                    <Label htmlFor="identifier" className="text-[13px] font-medium text-[#294b46]">
                        Student ID number or email
                    </Label>
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
                        aria-invalid={Boolean(fieldError)}
                        aria-describedby={fieldError ? 'identifier-error' : undefined}
                        placeholder="e.g. 2024-00001 or name@gmail.com"
                        className="h-11 rounded-lg border-[#d7e2de] bg-white text-sm text-[#123c37] caret-[#164f48] shadow-none placeholder:text-[#9aaba7] focus-visible:border-[#2d8a7b] focus-visible:ring-[#2d8a7b]/20 focus-visible:ring-offset-white"
                    />
                    <InputError message={fieldError} id="identifier-error" role="alert" />
                </div>

                <div className="rounded-lg border border-[#efe4c6] bg-[#fdf8ea] px-3.5 py-3 text-[13px] leading-relaxed text-[#6b5a35]">
                    If an account matches, a reset link is sent to its registered email. Reset links expire after 15 minutes.
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="group h-11 w-full rounded-lg bg-[#164f48] text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(6,47,44,0.85)] transition-colors hover:bg-[#103f39] focus-visible:ring-[#2d8a7b] focus-visible:ring-offset-2 disabled:opacity-70"
                >
                    {processing ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : null}
                    {processing ? 'Sending...' : 'Send reset link'}
                    {!processing && <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />}
                </Button>
            </form>

            <div className="mt-6 text-center">
                <TextLink
                    href={route('login')}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-[#2d7a6e] no-underline transition-colors hover:text-[#155e53] hover:underline"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Back to login
                </TextLink>
            </div>
        </AuthLayout>
    );
}
