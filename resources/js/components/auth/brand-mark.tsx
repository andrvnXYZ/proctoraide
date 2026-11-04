import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface BrandMarkProps {
    className?: string;
    iconClassName?: string;
    wordmarkClassName?: string;
    variant?: 'default' | 'light';
    linked?: boolean;
    showWordmark?: boolean;
}

export default function BrandMark({
    className,
    iconClassName,
    wordmarkClassName,
    variant = 'default',
    linked = true,
    showWordmark = true,
}: BrandMarkProps) {
    const isLight = variant === 'light';

    const content = (
        <>
            <span
                className={cn(
                    'flex size-9 shrink-0 items-center justify-center rounded-xl',
                    isLight ? 'bg-white/10 ring-1 ring-white/20 backdrop-blur-sm' : 'bg-[#e6f2ef] ring-1 ring-[#cfe6e0]',
                    iconClassName,
                )}
            >
                <AppLogoIcon className={cn('size-5', isLight ? 'fill-white' : 'fill-[#146b5e]')} />
            </span>
            {showWordmark && (
                <span className={cn('text-lg font-semibold tracking-tight', isLight ? 'text-white' : 'text-[#103f39]', wordmarkClassName)}>
                    Proctor<span className={isLight ? 'text-[#8fd6c7]' : 'text-[#2a8d7b]'}>Aide</span>
                </span>
            )}
        </>
    );

    const classes = cn('inline-flex items-center gap-2.5', className);

    if (!linked) {
        return <div className={classes}>{content}</div>;
    }

    return (
        <Link
            href={route('home')}
            className={cn(
                classes,
                'rounded-lg transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-[#2d8a7b] focus-visible:ring-offset-2 focus-visible:outline-hidden',
            )}
            aria-label="Go to the ProctorAide home page"
        >
            {content}
        </Link>
    );
}
