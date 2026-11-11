import { cn } from '@/lib/utils';
import { type ProctorExaminationStatus } from '@/types/proctor';

const statusStyles: Record<ProctorExaminationStatus, { label: string; className: string; dotClassName: string }> = {
    scheduled: {
        label: 'Upcoming',
        className: 'text-[#a3791f]',
        dotClassName: 'bg-[#c9a03f]',
    },
    ongoing: {
        label: 'Ongoing',
        className: 'text-[#2a6f64]',
        dotClassName: 'bg-[#2f9e6b]',
    },
    completed: {
        label: 'Completed',
        className: 'text-[#7b8f8a]',
        dotClassName: 'bg-[#8aa39d]',
    },
};

export default function ExaminationStatusBadge({ status, className }: { status: ProctorExaminationStatus; className?: string }) {
    const style = statusStyles[status] ?? statusStyles.scheduled;

    return (
        <span className={cn('inline-flex items-center gap-1.5 text-[12px] font-medium whitespace-nowrap', style.className, className)}>
            <span className={cn('size-1.5 rounded-full', style.dotClassName)} aria-hidden="true" />
            {style.label}
        </span>
    );
}
