import { cn } from '@/lib/utils';

const dotStyles = {
    teal: 'bg-[#2f9e8b]',
    red: 'bg-[#c25a4f]',
    green: 'bg-[#2f9e6b]',
};

export default function StatPill({ label, value, tone = 'teal' }: { label: string; value: number; tone?: keyof typeof dotStyles }) {
    return (
        <div className="flex items-center gap-2.5 rounded-lg bg-[#f1f4f3] px-4 py-2.5">
            <span className={cn('size-1.5 shrink-0 rounded-full', dotStyles[tone])} aria-hidden="true" />
            <span className="text-[13px] font-semibold text-[#5c6f6b]">{label}</span>
            <span className="text-lg font-semibold tracking-tight text-[#102b28] tabular-nums">{value}</span>
        </div>
    );
}
