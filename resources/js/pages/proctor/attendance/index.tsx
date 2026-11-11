import ExaminationStatusBadge from '@/components/proctor/examination-status-badge';
import FlashMessages from '@/components/proctor/flash-messages';
import { EmptyState, Panel } from '@/components/proctor/panel';
import { Button } from '@/components/ui/button';
import ProctorLayout from '@/layouts/proctor-layout';
import { cn } from '@/lib/utils';
import { type AttendanceStatus, type AttendanceSummaryData, type ProctorExamination } from '@/types/proctor';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarCheck2, Check, LoaderCircle } from 'lucide-react';
import { useEffect, useMemo } from 'react';

interface AttendanceProps {
    examinations: ProctorExamination[];
    selectedExamination: ProctorExamination | null;
    attendance: AttendanceSummaryData | null;
    statusOptions: Array<{ value: AttendanceStatus; label: string }>;
}

const statusStyles: Record<AttendanceStatus, { active: string; dot: string }> = {
    present: { active: 'border-[#1b7a52] bg-[#1b7a52] text-white', dot: 'bg-[#2f9e6b]' },
    late: { active: 'border-[#a3791f] bg-[#a3791f] text-white', dot: 'bg-[#c9a03f]' },
    absent: { active: 'border-[#a44037] bg-[#a44037] text-white', dot: 'bg-[#c25a4f]' },
    excused: { active: 'border-[#3f5f8a] bg-[#3f5f8a] text-white', dot: 'bg-[#5b7fae]' },
    not_checked: { active: 'border-[#8aa39d] bg-[#eef3f1] text-[#3c534f]', dot: 'bg-[#b7c6c2]' },
};

const summaryStyles = [
    { key: 'present' as const, label: 'Present', className: 'border-[#c4e6d5] bg-[#f0f9f4] text-[#1b7a52]' },
    { key: 'late' as const, label: 'Late', className: 'border-[#efe0b6] bg-[#fdf8ea] text-[#8a6a1f]' },
    { key: 'absent' as const, label: 'Absent', className: 'border-[#f4d1cc] bg-[#fdf3f2] text-[#a44037]' },
    { key: 'excused' as const, label: 'Excused', className: 'border-[#d7e2f0] bg-[#f3f7fc] text-[#3f5f8a]' },
    { key: 'not_checked' as const, label: 'Not yet checked', className: 'border-[#e2ebe7] bg-[#f7faf9] text-[#6b7f7b]' },
];

export default function AttendanceIndex({ examinations, selectedExamination, attendance, statusOptions }: AttendanceProps) {
    const { data, setData, put, processing, errors, clearErrors } = useForm<{ statuses: Record<string, AttendanceStatus> }>({
        statuses: {},
    });

    useEffect(() => {
        const next: Record<string, AttendanceStatus> = {};

        attendance?.rows.forEach((row) => {
            next[row.enrollment_id] = row.status;
        });

        setData('statuses', next);
        clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [attendance]);

    const counts = useMemo(() => {
        const totals = { present: 0, late: 0, absent: 0, excused: 0, not_checked: 0 };

        attendance?.rows.forEach((row) => {
            const status = data.statuses[row.enrollment_id] ?? 'not_checked';
            totals[status]++;
        });

        return totals;
    }, [attendance, data.statuses]);

    const changed = useMemo(() => {
        if (!attendance) {
            return false;
        }

        return attendance.rows.some((row) => (data.statuses[row.enrollment_id] ?? 'not_checked') !== row.status);
    }, [attendance, data.statuses]);

    const selectExamination = (id: string) => {
        router.get(route('proctor.attendance.index'), { examination: id }, { preserveScroll: false, preserveState: false });
    };

    const save = () => {
        if (!selectedExamination) {
            return;
        }

        put(route('proctor.attendance.update', selectedExamination.id), {
            preserveScroll: true,
        });
    };

    return (
        <ProctorLayout title="Attendance">
            <Head title="Attendance" />

            <div>
                <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">Attendance</h2>
                <p className="mt-1.5 text-sm text-[#6b7f7b]">
                    Confirm physical attendance for each student. Unchecked students are never saved as absent.
                </p>
            </div>

            <div className="mt-5 space-y-4">
                <FlashMessages />

                {examinations.length === 0 ? (
                    <Panel title="Attendance">
                        <EmptyState
                            icon={CalendarCheck2}
                            title="No examination sessions yet."
                            description="Create an examination session first, then come back here to record attendance."
                        />
                    </Panel>
                ) : (
                    <>
                        <Panel
                            title="Examination Session"
                            action={
                                <select
                                    value={selectedExamination?.id ?? ''}
                                    onChange={(event) => selectExamination(event.target.value)}
                                    aria-label="Select examination session"
                                    className="h-9 w-full rounded-md border border-[#dbe7e3] bg-white px-2.5 text-xs font-medium text-[#3c534f] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20 sm:w-72"
                                >
                                    {examinations.map((examination) => (
                                        <option key={examination.id} value={examination.id}>
                                            {examination.title} · {examination.section} · {examination.date_label}
                                        </option>
                                    ))}
                                </select>
                            }
                        >
                            {selectedExamination && (
                                <div className="flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4 sm:px-6">
                                    <div>
                                        <p className="text-sm font-semibold text-[#1e3834]">{selectedExamination.subject}</p>
                                        <p className="mt-0.5 text-xs text-[#93a5a1]">
                                            {selectedExamination.section} · {selectedExamination.schedule}
                                        </p>
                                    </div>
                                    <ExaminationStatusBadge status={selectedExamination.status} />
                                    <span className="ml-auto inline-flex items-center gap-1.5 rounded-md border border-[#dbe7e3] bg-[#f6faf8] px-2.5 py-1 font-mono text-[11px] font-semibold tracking-[0.14em] text-[#1f5a51]">
                                        {selectedExamination.access_code}
                                    </span>
                                </div>
                            )}
                        </Panel>

                        <Panel title="Class List" description="Select a status for each student, then save the attendance record.">
                            {!attendance || attendance.total === 0 ? (
                                <EmptyState
                                    icon={CalendarCheck2}
                                    title="No students enrolled in this section."
                                    description="Once students are enrolled in this section, they will appear here for attendance."
                                />
                            ) : (
                                <>
                                    <div className="flex flex-wrap gap-2 border-b border-[#eef3f1] px-5 py-4 sm:px-6">
                                        {summaryStyles.map((summary) => (
                                            <span
                                                key={summary.key}
                                                className={cn(
                                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold',
                                                    summary.className,
                                                )}
                                            >
                                                {summary.label}
                                                <span className="tabular-nums">{counts[summary.key]}</span>
                                            </span>
                                        ))}
                                    </div>

                                    <div className="hidden overflow-x-auto lg:block">
                                        <table className="w-full min-w-[860px] text-left">
                                            <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                                <tr>
                                                    <th scope="col" className="px-6 py-3.5 font-semibold">
                                                        Student
                                                    </th>
                                                    <th scope="col" className="px-6 py-3.5 font-semibold">
                                                        Student ID
                                                    </th>
                                                    <th scope="col" className="px-6 py-3.5 font-semibold">
                                                        Attendance Status
                                                    </th>
                                                    <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                                        Saved
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-[#eef3f1]">
                                                {attendance.rows.map((row) => {
                                                    const current = data.statuses[row.enrollment_id] ?? 'not_checked';

                                                    return (
                                                        <tr key={row.enrollment_id} className="transition-colors hover:bg-[#fafcfb]">
                                                            <td className="px-6 py-3">
                                                                <p className="text-sm font-semibold text-[#1e3834]">{row.student_name}</p>
                                                                <p className="mt-0.5 text-[11px] text-[#9baba7]">{row.year_level ?? '—'}</p>
                                                            </td>
                                                            <td className="px-6 py-3 font-mono text-[12px] text-[#5c6f6b]">
                                                                {row.student_id_number ?? '—'}
                                                            </td>
                                                            <td className="px-6 py-3">
                                                                <div className="flex flex-wrap gap-1.5">
                                                                    {statusOptions.map((option) => (
                                                                        <button
                                                                            key={option.value}
                                                                            type="button"
                                                                            aria-pressed={current === option.value}
                                                                            onClick={() =>
                                                                                setData('statuses', {
                                                                                    ...data.statuses,
                                                                                    [row.enrollment_id]: option.value,
                                                                                })
                                                                            }
                                                                            className={cn(
                                                                                'rounded-md border px-2.5 py-1 text-[11px] font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-[#2d8a7b]/40 focus-visible:outline-hidden',
                                                                                current === option.value
                                                                                    ? statusStyles[option.value].active
                                                                                    : 'border-[#e2ebe7] bg-white text-[#7b8f8a] hover:border-[#cfe0db] hover:text-[#3c534f]',
                                                                            )}
                                                                        >
                                                                            {option.label}
                                                                        </button>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-3 text-right text-[11px] font-medium text-[#93a5a1]">
                                                                {row.status === 'not_checked' ? 'Not yet checked' : row.status_label}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>

                                    <ul className="divide-y divide-[#eef3f1] lg:hidden">
                                        {attendance.rows.map((row) => {
                                            const current = data.statuses[row.enrollment_id] ?? 'not_checked';

                                            return (
                                                <li key={row.enrollment_id} className="space-y-2.5 px-5 py-4">
                                                    <div>
                                                        <p className="text-sm font-semibold text-[#1e3834]">{row.student_name}</p>
                                                        <p className="mt-0.5 font-mono text-[11px] text-[#7b8f8a]">{row.student_id_number ?? '—'}</p>
                                                    </div>

                                                    <div className="flex flex-wrap gap-1.5">
                                                        {statusOptions.map((option) => (
                                                            <button
                                                                key={option.value}
                                                                type="button"
                                                                aria-pressed={current === option.value}
                                                                onClick={() =>
                                                                    setData('statuses', { ...data.statuses, [row.enrollment_id]: option.value })
                                                                }
                                                                className={cn(
                                                                    'rounded-md border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                                                    current === option.value
                                                                        ? statusStyles[option.value].active
                                                                        : 'border-[#e2ebe7] bg-white text-[#7b8f8a]',
                                                                )}
                                                            >
                                                                {option.label}
                                                            </button>
                                                        ))}
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ul>

                                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#eef3f1] px-5 py-4 sm:px-6">
                                        <p className="text-xs text-[#93a5a1]">
                                            {counts.not_checked > 0
                                                ? `${counts.not_checked} student${counts.not_checked === 1 ? ' is' : 's are'} still not yet checked.`
                                                : 'Every student has a saved status.'}
                                        </p>

                                        <Button
                                            type="button"
                                            onClick={save}
                                            disabled={processing || !changed}
                                            className="h-10 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white hover:bg-[#103f39] disabled:opacity-60"
                                        >
                                            {processing ? (
                                                <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />
                                            ) : (
                                                <Check className="size-4" aria-hidden="true" />
                                            )}
                                            Save Attendance
                                        </Button>
                                    </div>

                                    {errors.statuses && <p className="px-5 pb-4 text-xs text-red-600 sm:px-6">{errors.statuses}</p>}
                                </>
                            )}
                        </Panel>
                    </>
                )}
            </div>
        </ProctorLayout>
    );
}
