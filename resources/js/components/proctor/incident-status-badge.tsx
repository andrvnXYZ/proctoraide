import { cn } from '@/lib/utils';
import { type IncidentStatus } from '@/types/proctor';

const statusStyles: Record<IncidentStatus, { label: string; className: string }> = {
    pending: { label: 'Pending', className: 'border-[#efe0b6] bg-[#fbf4e1] text-[#8a6a1f]' },
    reviewed: { label: 'Reviewed', className: 'border-[#c4e6d5] bg-[#e8f5ee] text-[#1b7a52]' },
    dismissed: { label: 'Dismissed', className: 'border-[#dbe7e3] bg-[#eef3f1] text-[#4b625d]' },
};

export default function IncidentStatusBadge({ status, className }: { status: IncidentStatus; className?: string }) {
    const style = statusStyles[status] ?? statusStyles.pending;

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap',
                style.className,
                className,
            )}
        >
            {style.label}
        </span>
    );
}
