import { Head, useForm } from '@inertiajs/react';
import { Check, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { normalizeEmail, passwordIssuesMessage, passwordRequirements } from '@/lib/validation';

interface ResetPasswordProps {
    token: string;
    email: string;
}

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset, transform } = useForm<ResetPasswordForm>({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const [clientError, setClientError] = useState<string>();

    transform((formData) => ({
        ...formData,
        email: normalizeEmail(formData.email),
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (processing) {
            return;
        }

        // Friendly client-side checks; the server validates the same rules.
        if (!data.password) {
            setClientError('Enter a new password.');
            return;
        }

        const issues = passwordIssuesMessage(data.password);

        if (issues) {
            setClientError(issues);
            return;
        }

        if (data.password !== data.password_confirmation) {
            setClientError('The password confirmation does not match.');
            return;
        }

        setClientError(undefined);

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Reset password" description="Please enter your new password below">
            <Head title="Reset password" />

            <form onSubmit={submit} noValidate>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={data.email}
                            className="mt-1 block w-full"
                            readOnly
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="new-password"
                            value={data.password}
                            className="mt-1 block w-full"
                            autoFocus
                            onChange={(e) => {
                                setData('password', e.target.value);
                                setClientError(undefined);
                            }}
                            placeholder="Password"
                        />
                        <InputError message={clientError ?? errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            className="mt-1 block w-full"
                            onChange={(e) => {
                                setData('password_confirmation', e.target.value);
                                setClientError(undefined);
                            }}
                            placeholder="Confirm password"
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                    </div>

                    <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
                        <p className="font-medium">Your password must include:</p>
                        <ul className="mt-2 grid gap-1">
                            {passwordRequirements.map((requirement) => {
                                const met = requirement.met(data.password);

                                return (
                                    <li key={requirement.label} className={cn('flex items-center gap-2', met ? 'text-emerald-700' : 'text-muted-foreground')}>
                                        {met ? (
                                            <Check className="size-4 shrink-0" aria-hidden="true" />
                                        ) : (
                                            <span className="size-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
                                        )}
                                        {requirement.label}
                                    </li>
                                );
                            })}
                        </ul>
                    </div>

                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Reset password
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
