import BrandMark from '@/components/auth/brand-mark';
import { cn } from '@/lib/utils';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
    variant?: 'plain' | 'card';
    width?: 'narrow' | 'wide';
    showHeader?: boolean;
}

export default function AuthLayout({ children, title, description, variant = 'plain', width = 'narrow', showHeader = true }: AuthLayoutProps) {
    return (
        <div
            className={cn(
                'min-h-svh bg-[#f7f9f8] lg:grid lg:h-svh lg:overflow-hidden',
                variant === 'card' ? 'lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]' : 'lg:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]',
            )}
        >
            <aside className="relative isolate flex min-h-[15rem] flex-col overflow-hidden bg-[#0b3a36] px-6 py-7 text-white sm:px-10 lg:h-svh lg:min-h-0 lg:px-12 lg:py-11 xl:px-16">
                <img
                    src="/images/auth-classroom.jpg"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 bg-gradient-to-b from-[#062f2c]/70 via-[#07332f]/78 to-[#042825]/95"
                />

                <div className="relative z-10 flex min-h-0 flex-1 flex-col">
                    <BrandMark variant="light" />

                    <div className="mt-10 max-w-lg lg:my-auto">
                        <p className="text-[10px] font-semibold tracking-[0.32em] text-[#9ad7cb] uppercase">Examination management</p>
                        <h1 className="mt-4 text-3xl leading-[1.12] font-semibold tracking-tight text-balance text-white sm:text-4xl lg:text-[2.85rem] xl:text-5xl">
                            A calmer way to run every exam.
                        </h1>
                        <p className="mt-4 text-sm text-[#cfe6e1]/80 sm:text-base">Observe better. Manage smarter.</p>
                        <div className="mt-7 h-px w-14 bg-[#8ed3c6]/45" />
                    </div>

                    <p className="mt-8 hidden text-xs text-white/45 lg:block">Secure examination environment · ProctorAide</p>
                </div>
            </aside>

            <main
                className={cn(
                    'flex min-h-svh flex-col px-4 py-8 sm:px-6 lg:h-svh lg:min-h-0 lg:overflow-y-auto lg:px-10 lg:py-12',
                    variant === 'card' ? 'bg-[#eef3f1]' : 'bg-[#fbfcfb]',
                )}
            >
                <div className="flex flex-1 items-center justify-center">
                    <div
                        className={cn(
                            'w-full',
                            width === 'wide' ? 'max-w-3xl' : 'max-w-[27rem]',
                            variant === 'card' &&
                                'rounded-2xl border border-white/70 bg-white p-5 shadow-[0_24px_70px_-38px_rgba(6,47,44,0.45)] sm:p-7 lg:p-8',
                        )}
                    >
                        {showHeader && (
                            <div className="mb-8">
                                <BrandMark className="mb-6" />
                                {title && <h1 className="text-xl font-semibold tracking-tight text-[#103f39]">{title}</h1>}
                                {description && <p className="mt-2 text-sm leading-relaxed text-[#657a76]">{description}</p>}
                            </div>
                        )}

                        {children}
                    </div>
                </div>

                <p className="mt-8 text-center text-[11px] text-[#93a5a1]">© {new Date().getFullYear()} ProctorAide. Built for calmer exam days.</p>
            </main>
        </div>
    );
}
