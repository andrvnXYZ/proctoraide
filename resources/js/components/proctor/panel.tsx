import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

export function Panel({
    id,
    title,
    description,
    action,
    children,
    className,
    bodyClassName,
}: {
    id?: string;
    title?: string;
    description?: string;
    action?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    bodyClassName?: string;
}) {
    return (
        <section
            id={id}
            className={cn('overflow-hidden rounded-xl border border-[#e2ebe7] bg-white shadow-[0_16px_36px_-32px_rgba(9,48,45,0.55)]', className)}
        >
            {(title || action) && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#eef3f1] px-5 py-4 sm:px-6">
                    <div className="min-w-0">
                        {title && <h2 className="text-[15px] font-semibold tracking-tight text-[#102b28]">{title}</h2>}
                        {description && <p className="mt-0.5 text-xs text-[#93a5a1]">{description}</p>}
                    </div>
                    {action}
                </div>
            )}
            <div className={bodyClassName}>{children}</div>
        </section>
    );
}

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: React.ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <span className="flex size-12 items-center justify-center rounded-full bg-[#f1f7f5] text-[#8aa39d]">
                <Icon className="size-5" aria-hidden="true" />
            </span>
            <p className="mt-4 text-sm font-semibold text-[#2f4a45]">{title}</p>
            <p className="mt-1 max-w-sm text-xs leading-relaxed text-[#93a5a1]">{description}</p>
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
