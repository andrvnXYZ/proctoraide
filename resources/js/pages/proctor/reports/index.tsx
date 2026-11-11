import ExaminationStatusBadge from '@/components/proctor/examination-status-badge';
import { EmptyState, Panel } from '@/components/proctor/panel';
import { Button } from '@/components/ui/button';
import ProctorLayout from '@/layouts/proctor-layout';
import { type AttendanceStatus, type AttendanceSummaryData, type ProctorExamination, type ReportRow } from '@/types/proctor';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChartColumnBig, Printer } from 'lucide-react';

interface ReportsProps {
    reports: ReportRow[];
    selectedExamination: ProctorExamination | null;
    selectedAttendance: AttendanceSummaryData | null;
}

const countColumns: Array<{ key: 'present' | 'late' | 'absent' | 'excused' | 'not_checked'; label: string }> = [
    { key: 'present', label: 'Present' },
    { key: 'late', label: 'Late' },
    { key: 'absent', label: 'Absent' },
    { key: 'excused', label: 'Excused' },
    { key: 'not_checked', label: 'Not checked' },
];

const statusChipStyles: Record<AttendanceStatus, string> = {
    present: 'border-[#c4e6d5] bg-[#f0f9f4] text-[#1b7a52]',
    late: 'border-[#efe0b6] bg-[#fdf8ea] text-[#8a6a1f]',
    absent: 'border-[#f4d1cc] bg-[#fdf3f2] text-[#a44037]',
    excused: 'border-[#d7e2f0] bg-[#f3f7fc] text-[#3f5f8a]',
    not_checked: 'border-[#e2ebe7] bg-[#f7faf9] text-[#6b7f7b]',
};

function formatRecordedAt(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export default function ReportsIndex({ reports, selectedExamination, selectedAttendance }: ReportsProps) {
    return (
        <ProctorLayout title="Reports">
            <Head title="Reports" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">Reports</h2>
                    <p className="mt-1.5 text-sm text-[#6b7f7b]">Attendance summaries built from the records you saved for each session.</p>
                </div>

                {selectedExamination && (
                    <div className="flex shrink-0 gap-2">
                        <Button
                            asChild
                            variant="outline"
                            className="h-10 gap-2 rounded-lg border-[#d7e4e0] bg-white text-[13px] font-semibold text-[#2a6f64]"
                        >
                            <Link href={route('proctor.reports.index')}>
                                <ArrowLeft className="size-4" aria-hidden="true" />
                                All reports
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            onClick={() => window.print()}
                            className="h-10 gap-2 rounded-lg bg-[#164f48] text-[13px] font-semibold text-white hover:bg-[#103f39]"
                        >
                            <Printer className="size-4" aria-hidden="true" />
                            Print
                        </Button>
                    </div>
                )}
            </div>

            <div className="mt-5 space-y-4">
                {selectedExamination && selectedAttendance ? (
                    <>
                        <Panel title="Examination Information">
                            <div className="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                                <div>
                                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Examination</p>
                                    <p className="mt-1 text-sm font-semibold text-[#1e3834]">{selectedExamination.title}</p>
                                    <div className="mt-1.5">
                                        <ExaminationStatusBadge status={selectedExamination.status} />
                                    </div>
                                </div>
                                <div>
                                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Course / Section</p>
                                    <p className="mt-1 text-sm font-semibold text-[#1e3834]">{selectedExamination.subject}</p>
                                    <p className="mt-0.5 text-xs text-[#7b8f8a]">{selectedExamination.section}</p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Schedule</p>
                                    <p className="mt-1 text-sm font-semibold text-[#1e3834]">{selectedExamination.date_label}</p>
                                    <p className="mt-0.5 text-xs text-[#7b8f8a]">{selectedExamination.time_range}</p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Access code</p>
                                    <p className="mt-1 font-mono text-sm font-semibold tracking-[0.14em] text-[#1f5a51]">
                                        {selectedExamination.access_code}
                                    </p>
                                    <p className="mt-0.5 text-xs text-[#7b8f8a]">
                                        {selectedAttendance.recorded} of {selectedAttendance.total} students checked
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2 border-t border-[#eef3f1] px-5 py-4 sm:px-6">
                                {countColumns.map((column) => (
                                    <span
                                        key={column.key}
                                        className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold ${statusChipStyles[column.key]}`}
                                    >
                                        {column.label}
                                        <span className="tabular-nums">{selectedAttendance.counts[column.key]}</span>
                                    </span>
                                ))}
                            </div>
                        </Panel>

                        <Panel title="Student Attendance" description="Status recorded by the proctor for this session.">
                            {selectedAttendance.rows.length === 0 ? (
                                <EmptyState
                                    icon={ChartColumnBig}
                                    title="No students in this section."
                                    description="Attendance can only be reported for students enrolled in the examination section."
                                />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[640px] text-left">
                                        <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                            <tr>
                                                <th scope="col" className="px-6 py-3.5 font-semibold">
                                                    #
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 font-semibold">
                                                    Student
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 font-semibold">
                                                    Student ID
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 font-semibold">
                                                    Status
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                                    Recorded
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[#eef3f1]">
                                            {selectedAttendance.rows.map((row, index) => (
                                                <tr key={row.enrollment_id}>
                                                    <td className="px-6 py-3.5 text-[12px] text-[#9baba7] tabular-nums">{index + 1}</td>
                                                    <td className="px-6 py-3.5 text-[13px] font-medium text-[#2f4a45]">{row.student_name}</td>
                                                    <td className="px-6 py-3.5 font-mono text-[12px] text-[#5c6f6b]">
                                                        {row.student_id_number ?? '—'}
                                                    </td>
                                                    <td className="px-6 py-3.5">
                                                        <span
                                                            className={`inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold ${statusChipStyles[row.status]}`}
                                                        >
                                                            {row.status_label}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-3.5 text-right text-[11px] text-[#93a5a1]">
                                                        {formatRecordedAt(row.recorded_at)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Panel>
                    </>
                ) : (
                    <Panel title="Attendance Reports" description="Select a session to open its detailed attendance report.">
                        {reports.length === 0 ? (
                            <EmptyState
                                icon={ChartColumnBig}
                                title="No reports yet."
                                description="Create an examination session and save attendance to generate your first report."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[900px] text-left">
                                    <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                        <tr>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Examination
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Section
                                            </th>
                                            {countColumns.map((column) => (
                                                <th key={column.key} scope="col" className="px-4 py-3.5 text-center font-semibold">
                                                    {column.label}
                                                </th>
                                            ))}
                                            <th scope="col" className="px-4 py-3.5 text-center font-semibold">
                                                Rate
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                                Report
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#eef3f1]">
                                        {reports.map((report) => (
                                            <tr key={report.id} className="transition-colors hover:bg-[#fafcfb]">
                                                <td className="px-6 py-4">
                                                    <p className="text-sm font-semibold text-[#1e3834]">{report.title}</p>
                                                    <p className="mt-0.5 text-[11px] text-[#9baba7]">
                                                        {report.subject} · {report.date_label}
                                                    </p>
                                                </td>
                                                <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{report.section}</td>
                                                {countColumns.map((column) => (
                                                    <td key={column.key} className="px-4 py-4 text-center text-[13px] text-[#3c534f] tabular-nums">
                                                        {report.counts[column.key]}
                                                    </td>
                                                ))}
                                                <td className="px-4 py-4">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <div className="h-1.5 w-16 overflow-hidden rounded-full bg-[#eef3f1]">
                                                            <div
                                                                className="h-full rounded-full bg-[#2d8a7b]"
                                                                style={{ width: `${report.attendance_rate}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-[11px] font-semibold text-[#5c6f6b] tabular-nums">
                                                            {report.attendance_rate}%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                        className="h-8 rounded-lg border-[#d7e4e0] bg-white px-3 text-[11px] font-semibold text-[#2a6f64] hover:bg-[#f3f8f6]"
                                                    >
                                                        <Link href={route('proctor.reports.index', { examination: report.id })}>View report</Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Panel>
                )}
            </div>
        </ProctorLayout>
    );
}
