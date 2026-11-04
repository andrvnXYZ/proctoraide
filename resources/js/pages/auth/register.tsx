import BrandMark from '@/components/auth/brand-mark';
import RoleSelector, { type AuthRole } from '@/components/auth/role-selector';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { Head, useForm } from '@inertiajs/react';
import { ArrowRight, ChevronDown, Eye, EyeOff, LoaderCircle, LockKeyhole, Mail } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate'];

const inputClasses =
    'h-10 rounded-lg border-[#d7e2de] bg-white text-sm text-[#123c37] caret-[#164f48] shadow-none placeholder:text-[#9aaba7] focus-visible:border-[#2d8a7b] focus-visible:ring-[#2d8a7b]/20 focus-visible:ring-offset-white disabled:bg-[#f6f8f7]';

type RegisterForm = {
    first_name: string;
    middle_name: string;
    last_name: string;
    birthday: string;
    role: AuthRole;
    student_id_number: string;
    college: string;
    department: string;
    year_level: string;
    email: string;
    password: string;
    password_confirmation: string;
    terms_accepted: boolean;
};

interface FieldProps {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    optional?: boolean;
    className?: string;
    children: React.ReactNode;
}

function Field({ id, label, error, required = false, optional = false, className, children }: FieldProps) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label htmlFor={id} className="text-[13px] font-medium text-[#294b46]">
                {label}
                {optional && <span className="ml-1 font-normal text-[#93a5a1]">(optional)</span>}
                {required && (
                    <span className="ml-0.5 text-[#b4544a]" aria-hidden="true">
                        *
                    </span>
                )}
            </Label>
            {children}
            <InputError message={error} id={`${id}-error`} role="alert" />
        </div>
    );
}

function SectionHeading({ step, children }: { step: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2">
            <span className="flex size-5 items-center justify-center rounded-full bg-[#e7f2ef] text-[10px] font-bold text-[#1c6b5e]">{step}</span>
            <h2 className="text-[13px] font-semibold text-[#264b45]">{children}</h2>
        </div>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors, transform, clearErrors } = useForm<RegisterForm>({
        first_name: '',
        middle_name: '',
        last_name: '',
        birthday: '',
        role: 'student',
        student_id_number: '',
        college: '',
        department: '',
        year_level: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const today = new Date().toISOString().slice(0, 10);

    const passwordError = errors.password && !/confirmation|match/i.test(errors.password) ? errors.password : undefined;
    const passwordConfirmationError =
        errors.password_confirmation || (errors.password && /confirmation|match/i.test(errors.password) ? errors.password : undefined);
    const termsError = errors.terms_accepted ?? (errors as Partial<Record<string, string>>).terms_accepted_at;

    const isStudent = data.role === 'student';

    transform((formData) => ({
        ...formData,
        middle_name: formData.middle_name.trim() || null,
        student_id_number: formData.role === 'student' ? formData.student_id_number : null,
        year_level: formData.role === 'student' ? formData.year_level : null,
    }));

    const handleRoleChange = (role: AuthRole) => {
        setData('role', role);

        if (role === 'proctor') {
            clearErrors('student_id_number', 'year_level');
        }
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (processing) {
            return;
        }

        post(route('register'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthLayout showHeader={false} variant="card" width="wide">
            <Head title="Create account" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <BrandMark linked={false} />
                <span className="hidden rounded-full bg-[#eef7f4] px-3 py-1 text-[10px] font-semibold tracking-[0.16em] text-[#2a7b6d] uppercase sm:inline-flex">
                    Student & proctor access
                </span>
            </div>

            <div>
                <h1 className="text-xl font-semibold tracking-tight text-[#103f39]">Create your account to get started.</h1>
                <p className="mt-1.5 text-sm text-[#657a76]">Join ProctorAide to help make every exam calmer.</p>
            </div>

            <form className="mt-6 space-y-7" onSubmit={submit} noValidate>
                <section className="space-y-4">
                    <SectionHeading step="A">Personal Information</SectionHeading>

                    <div>
                        <span className="mb-2 block text-[13px] font-medium text-[#294b46]">Role</span>
                        <RoleSelector value={data.role} onChange={handleRoleChange} compact disabled={processing} />
                        <InputError message={errors.role} className="mt-1.5" role="alert" />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field id="first_name" label="First Name" error={errors.first_name} required>
                            <Input
                                id="first_name"
                                name="first_name"
                                type="text"
                                required
                                autoFocus
                                autoComplete="given-name"
                                value={data.first_name}
                                onChange={(event) => setData('first_name', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.first_name)}
                                aria-describedby={errors.first_name ? 'first_name-error' : undefined}
                                placeholder="First name"
                                className={inputClasses}
                            />
                        </Field>

                        <Field id="middle_name" label="Middle Name" error={errors.middle_name} optional>
                            <Input
                                id="middle_name"
                                name="middle_name"
                                type="text"
                                autoComplete="additional-name"
                                value={data.middle_name}
                                onChange={(event) => setData('middle_name', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.middle_name)}
                                aria-describedby={errors.middle_name ? 'middle_name-error' : undefined}
                                placeholder="Middle name"
                                className={inputClasses}
                            />
                        </Field>

                        <Field id="last_name" label="Last Name" error={errors.last_name} required>
                            <Input
                                id="last_name"
                                name="last_name"
                                type="text"
                                required
                                autoComplete="family-name"
                                value={data.last_name}
                                onChange={(event) => setData('last_name', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.last_name)}
                                aria-describedby={errors.last_name ? 'last_name-error' : undefined}
                                placeholder="Last name"
                                className={inputClasses}
                            />
                        </Field>
                    </div>

                    <div className={cn('grid gap-4', isStudent ? 'sm:grid-cols-2' : 'sm:grid-cols-1')}>
                        <Field id="birthday" label="Birthday" error={errors.birthday} required>
                            <Input
                                id="birthday"
                                name="birthday"
                                type="date"
                                required
                                max={today}
                                autoComplete="bday"
                                value={data.birthday}
                                onChange={(event) => setData('birthday', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.birthday)}
                                aria-describedby={errors.birthday ? 'birthday-error' : undefined}
                                className={inputClasses}
                            />
                        </Field>

                        {isStudent && (
                            <Field id="student_id_number" label="Student ID Number" error={errors.student_id_number} required>
                                <Input
                                    id="student_id_number"
                                    name="student_id_number"
                                    type="text"
                                    required
                                    autoComplete="off"
                                    value={data.student_id_number}
                                    onChange={(event) => setData('student_id_number', event.target.value)}
                                    disabled={processing}
                                    aria-invalid={Boolean(errors.student_id_number)}
                                    aria-describedby={errors.student_id_number ? 'student_id_number-error' : undefined}
                                    placeholder="e.g. 2024-00001"
                                    className={inputClasses}
                                />
                            </Field>
                        )}
                    </div>
                </section>

                <section className="space-y-4 border-t border-[#edf2f0] pt-6">
                    <SectionHeading step="B">Academic Information</SectionHeading>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field id="college" label="College" error={errors.college} required>
                            <Input
                                id="college"
                                name="college"
                                type="text"
                                required
                                value={data.college}
                                onChange={(event) => setData('college', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.college)}
                                aria-describedby={errors.college ? 'college-error' : undefined}
                                placeholder="e.g. College of Computer Studies"
                                className={inputClasses}
                            />
                        </Field>

                        <Field id="department" label="Department" error={errors.department} required>
                            <Input
                                id="department"
                                name="department"
                                type="text"
                                required
                                value={data.department}
                                onChange={(event) => setData('department', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.department)}
                                aria-describedby={errors.department ? 'department-error' : undefined}
                                placeholder="e.g. Computer Science"
                                className={inputClasses}
                            />
                        </Field>

                        {isStudent && (
                            <Field id="year_level" label="Year Level" error={errors.year_level} required>
                                <div className="relative">
                                    <select
                                        id="year_level"
                                        name="year_level"
                                        required
                                        value={data.year_level}
                                        onChange={(event) => setData('year_level', event.target.value)}
                                        disabled={processing}
                                        aria-invalid={Boolean(errors.year_level)}
                                        aria-describedby={errors.year_level ? 'year_level-error' : undefined}
                                        className={cn(inputClasses, 'w-full appearance-none border px-3 pr-9')}
                                    >
                                        <option value="" disabled>
                                            Select year level
                                        </option>
                                        {yearLevels.map((level) => (
                                            <option key={level} value={level}>
                                                {level}
                                            </option>
                                        ))}
                                    </select>
                                    <ChevronDown
                                        className="pointer-events-none absolute top-1/2 right-3 z-10 size-4 -translate-y-1/2 text-[#6c807c]"
                                        aria-hidden="true"
                                    />
                                </div>
                            </Field>
                        )}
                    </div>
                </section>

                <section className="space-y-4 border-t border-[#edf2f0] pt-6">
                    <SectionHeading step="C">Account Information</SectionHeading>

                    <Field id="email" label="Email Address" error={errors.email} required>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-[#6c807c]" aria-hidden="true" />
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autoComplete="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                disabled={processing}
                                aria-invalid={Boolean(errors.email)}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                placeholder="you@university.edu"
                                className={cn(inputClasses, 'pl-10')}
                            />
                        </div>
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field id="password" label="Password" error={passwordError} required>
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
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                    disabled={processing}
                                    aria-invalid={Boolean(passwordError)}
                                    aria-describedby={passwordError ? 'password-error' : undefined}
                                    placeholder="Create a password"
                                    className={cn(inputClasses, 'pr-11 pl-10')}
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
                        </Field>

                        <Field id="password_confirmation" label="Confirm Password" error={passwordConfirmationError} required>
                            <div className="relative">
                                <LockKeyhole
                                    className="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-[#6c807c]"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type={showPasswordConfirmation ? 'text' : 'password'}
                                    required
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                    disabled={processing}
                                    aria-invalid={Boolean(passwordConfirmationError)}
                                    aria-describedby={passwordConfirmationError ? 'password_confirmation-error' : undefined}
                                    placeholder="Re-enter password"
                                    className={cn(inputClasses, 'pr-11 pl-10')}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPasswordConfirmation((visible) => !visible)}
                                    aria-label={showPasswordConfirmation ? 'Hide password confirmation' : 'Show password confirmation'}
                                    aria-pressed={showPasswordConfirmation}
                                    className="absolute inset-y-0 right-0 z-10 flex w-11 items-center justify-center rounded-r-lg text-[#849995] transition-colors hover:text-[#1c6b5e] focus-visible:ring-2 focus-visible:ring-[#2d8a7b] focus-visible:outline-hidden"
                                >
                                    {showPasswordConfirmation ? (
                                        <EyeOff className="size-4" aria-hidden="true" />
                                    ) : (
                                        <Eye className="size-4" aria-hidden="true" />
                                    )}
                                </button>
                            </div>
                        </Field>
                    </div>
                </section>

                <div className="space-y-1.5">
                    <div className="flex items-start gap-2.5">
                        <Checkbox
                            id="terms_accepted"
                            name="terms_accepted"
                            checked={data.terms_accepted}
                            onCheckedChange={(checked) => setData('terms_accepted', checked === true)}
                            disabled={processing}
                            aria-invalid={Boolean(termsError)}
                            aria-describedby={termsError ? 'terms_accepted-error' : undefined}
                            className="mt-0.5 size-[18px] rounded border-[#b9cbc6] data-[state=checked]:border-[#164f48] data-[state=checked]:bg-[#164f48] data-[state=checked]:text-white"
                        />
                        <Label htmlFor="terms_accepted" className="text-[13px] leading-relaxed font-normal text-[#526b66]">
                            I agree to the <span className="font-semibold text-[#1c6b5e]">Terms of Service</span> and{' '}
                            <span className="font-semibold text-[#1c6b5e]">Privacy Policy</span>.
                        </Label>
                    </div>
                    <InputError message={termsError} id="terms_accepted-error" role="alert" />
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="group h-11 w-full rounded-lg bg-[#164f48] text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(6,47,44,0.85)] transition-colors hover:bg-[#103f39] focus-visible:ring-[#2d8a7b] focus-visible:ring-offset-2 disabled:opacity-70"
                >
                    {processing ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : null}
                    {processing ? 'Creating account...' : 'Sign Up'}
                    {!processing && <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />}
                </Button>
            </form>

            <div className="mt-7 border-t border-[#edf2f0] pt-5 text-center text-sm text-[#6c807c]">
                Already have an account?{' '}
                <TextLink
                    href={route('login')}
                    className="font-semibold text-[#1c6b5e] no-underline transition-colors hover:text-[#124f46] hover:underline"
                >
                    Login
                </TextLink>
            </div>
        </AuthLayout>
    );
}
