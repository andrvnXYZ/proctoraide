import { cn } from '@/lib/utils';
import { type ExaminationStatus } from '@/types/student';

const statusStyles: Record<ExaminationStatus, { label: string; className: string; dotClassName: string; pulsing?: boolean }> = {
    scheduled: {
        label: 'Scheduled',
        className: 'border-[#efe0b6] bg-[#fbf4e1] text-[#8a6a1f]',
        dotClassName: 'bg-[#c9a03f]',
    },
    ongoing: {
        label: 'Live now',
        className: 'border-[#c4e6d5] bg-[#e8f5ee] text-[#1b7a52]',
        dotClassName: 'bg-[#2f9e6b]',
        pulsing: true,
    },
    completed: {
        label: 'Completed',
        className: 'border-[#dbe7e3] bg-[#eef3f1] text-[#4b625d]',
        dotClassName: 'bg-[#8aa39d]',
    },
    missed: {
        label: 'Missed',
        className: 'border-[#f4d1cc] bg-[#fdecea] text-[#a44037]',
        dotClassName: 'bg-[#c25a4f]',
    },
};

export default function ExaminationStatusBadge({ status, className }: { status: ExaminationStatus; className?: string }) {
    const style = statusStyles[status] ?? statusStyles.scheduled;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap',
                style.className,
                className,
            )}
        >
            <span className={cn('size-1.5 rounded-full', style.dotClassName, style.pulsing && 'animate-pulse')} aria-hidden="true" />
            {style.label}
        </span>
    );
}
