import { cn } from '@/lib/utils';
import { GraduationCap, ShieldCheck } from 'lucide-react';

export type AuthRole = 'student' | 'proctor';

const roles = [
    {
        value: 'student' as const,
        label: 'Student',
        description: 'Exam taker / learner',
        icon: GraduationCap,
    },
    {
        value: 'proctor' as const,
        label: 'Proctor',
        description: 'Instructor / staff',
        icon: ShieldCheck,
    },
];

interface RoleSelectorProps {
    value: AuthRole;
    onChange: (role: AuthRole) => void;
    compact?: boolean;
    disabled?: boolean;
}

export default function RoleSelector({ value, onChange, compact = false, disabled = false }: RoleSelectorProps) {
    return (
        <fieldset disabled={disabled}>
            <legend className="sr-only">Select your role</legend>
            <div className={cn('grid grid-cols-2', compact ? 'gap-2.5' : 'gap-3')}>
                {roles.map((role) => {
                    const Icon = role.icon;
                    const isSelected = value === role.value;

                    return (
                        <label
                            key={role.value}
                            className={cn(
                                'relative flex cursor-pointer items-start rounded-xl border bg-white transition-colors',
                                'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2d8a7b] has-[:focus-visible]:ring-offset-2',
                                disabled && 'cursor-not-allowed opacity-60',
                                compact ? 'gap-2 px-3 py-2.5' : 'gap-3 p-3.5',
                                isSelected
                                    ? 'border-[#2d8a7b] bg-[#f0f8f6] shadow-[inset_0_0_0_1px_#2d8a7b]'
                                    : 'border-[#d9e4e0] hover:border-[#a9c8c0] hover:bg-[#f8fbfa]',
                            )}
                        >
                            <input
                                type="radio"
                                name="role"
                                value={role.value}
                                checked={isSelected}
                                onChange={() => onChange(role.value)}
                                disabled={disabled}
                                className="sr-only"
                            />
                            <span
                                className={cn(
                                    'mt-0.5 flex shrink-0 items-center justify-center rounded-lg',
                                    compact ? 'size-7' : 'size-8',
                                    isSelected ? 'bg-[#dcefeb] text-[#146b5e]' : 'bg-[#f1f5f4] text-[#738783]',
                                )}
                            >
                                <Icon className={compact ? 'size-4' : 'size-[18px]'} aria-hidden="true" />
                            </span>
                            <span className="min-w-0">
                                <span className={cn('block font-semibold text-[#123c37]', compact ? 'text-[13px]' : 'text-sm')}>{role.label}</span>
                                {!compact && <span className="mt-0.5 block text-[11px] leading-snug text-[#6c807c]">{role.description}</span>}
                            </span>
                        </label>
                    );
                })}
            </div>
        </fieldset>
    );
}
