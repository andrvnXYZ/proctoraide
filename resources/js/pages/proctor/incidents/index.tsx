import InputError from '@/components/input-error';
import FlashMessages from '@/components/proctor/flash-messages';
import IncidentStatusBadge from '@/components/proctor/incident-status-badge';
import { EmptyState, Panel } from '@/components/proctor/panel';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import ProctorLayout from '@/layouts/proctor-layout';
import { type ExaminationWithStudents, type ProctorIncident } from '@/types/proctor';
import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Check, LoaderCircle, X } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

interface IncidentsProps {
    incidents: ProctorIncident[];
    examinations: ExaminationWithStudents[];
    selectedExaminationId: string | null;
    counts: { pending: number; reviewed: number; dismissed: number };
}

type IncidentForm = {
    examination_id: string;
    student_id: string;
    description: string;
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function IncidentsIndex({ incidents, examinations, selectedExaminationId, counts }: IncidentsProps) {
    const defaultExaminationId = selectedExaminationId ?? examinations[0]?.id ?? '';
    const [busyIncident, setBusyIncident] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm<IncidentForm>({
        examination_id: defaultExaminationId,
        student_id: '',
        description: '',
    });

    const students = useMemo(
        () => examinations.find((examination) => examination.id === data.examination_id)?.students ?? [],
        [examinations, data.examination_id],
    );

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('proctor.incidents.store'), {
            preserveScroll: true,
            onSuccess: () => reset('student_id', 'description'),
        });
    };

    const updateStatus = (incident: ProctorIncident, status: 'reviewed' | 'dismissed' | 'pending') => {
        router.patch(
            route('proctor.incidents.status', incident.id),
            { status },
            {
                preserveScroll: true,
                onStart: () => setBusyIncident(incident.id),
                onFinish: () => setBusyIncident(null),
            },
        );
    };

    return (
        <ProctorLayout title="Incidents">
            <Head title="Incidents" />

            <div>
                <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">Incidents</h2>
                <p className="mt-1.5 text-sm text-[#6b7f7b]">
                    Record an incident manually when something needs review. Students are never flagged automatically.
                </p>
            </div>

            <div className="mt-5 space-y-4">
                <FlashMessages />

                <div className="flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-[#efe0b6] bg-[#fdf8ea] px-2.5 py-1 text-[11px] font-semibold text-[#8a6a1f]">
                        Pending <span className="tabular-nums">{counts.pending}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-[#c4e6d5] bg-[#f0f9f4] px-2.5 py-1 text-[11px] font-semibold text-[#1b7a52]">
                        Reviewed <span className="tabular-nums">{counts.reviewed}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-[#e2ebe7] bg-[#f7faf9] px-2.5 py-1 text-[11px] font-semibold text-[#6b7f7b]">
                        Dismissed <span className="tabular-nums">{counts.dismissed}</span>
                    </span>
                </div>

                <Panel title="Report an Incident" description="Only submit what you personally observed during the session.">
                    {examinations.length === 0 ? (
                        <EmptyState
                            icon={AlertTriangle}
                            title="No examination sessions available."
                            description="Incidents are linked to an examination session. Create one first, then file the report."
                        />
                    ) : (
                        <form onSubmit={submit} className="space-y-4 px-5 py-5 sm:px-6" noValidate>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="examination_id">Examination session</Label>
                                    <select
                                        id="examination_id"
                                        value={data.examination_id}
                                        onChange={(event) => setData({ ...data, examination_id: event.target.value, student_id: '' })}
                                        className="mt-1.5 h-10 w-full rounded-md border border-[#dbe7e3] bg-white px-3 text-sm text-[#2f4a45] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20"
                                    >
                                        {examinations.map((examination) => (
                                            <option key={examination.id} value={examination.id}>
                                                {examination.title} · {examination.section} · {examination.date_label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.examination_id} className="mt-1.5" />
                                </div>

                                <div>
                                    <Label htmlFor="student_id">Involved student (optional)</Label>
                                    <select
                                        id="student_id"
                                        value={data.student_id}
                                        onChange={(event) => setData('student_id', event.target.value)}
                                        className="mt-1.5 h-10 w-full rounded-md border border-[#dbe7e3] bg-white px-3 text-sm text-[#2f4a45] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20"
                                    >
                                        <option value="">No specific student</option>
                                        {students.map((student) => (
                                            <option key={student.id} value={student.id}>
                                                {student.name}
                                                {student.student_id_number ? ` · ${student.student_id_number}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.student_id} className="mt-1.5" />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="description">What happened?</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    rows={3}
                                    maxLength={2000}
                                    placeholder="Describe the behaviour you observed and where in the room it happened."
                                    className="mt-1.5 w-full rounded-md border border-[#dbe7e3] bg-white px-3 py-2.5 text-sm text-[#2f4a45] shadow-xs placeholder:text-[#9baba7] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20"
                                />
                                <div className="mt-1 flex items-center justify-between gap-3">
                                    <InputError message={errors.description} />
                                    <span className="ml-auto text-[11px] text-[#a3b2ae]">{data.description.length}/2000</span>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="h-10 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white hover:bg-[#103f39]"
                                >
                                    {processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                                    Submit Report
                                </Button>
                            </div>
                        </form>
                    )}
                </Panel>

                <Panel title="Reported Incidents" description="Reports stay pending until you review or dismiss them.">
                    {incidents.length === 0 ? (
                        <EmptyState
                            icon={AlertTriangle}
                            title="No incidents reported."
                            description="Any incident you submit will be listed here with its review status."
                        />
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[980px] text-left">
                                    <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                        <tr>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Reported
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Examination
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Student
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Description
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Status
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#eef3f1]">
                                        {incidents.map((incident) => (
                                            <tr key={incident.id} className="align-top transition-colors hover:bg-[#fafcfb]">
                                                <td className="px-6 py-4 text-[12px] whitespace-nowrap text-[#6b7f7b]">
                                                    {formatDate(incident.reported_at)}
                                                </td>
                                                <td className="px-6 py-4 text-[13px] text-[#3c534f]">{incident.examination_title ?? '—'}</td>
                                                <td className="px-6 py-4">
                                                    <p className="text-[13px] font-medium text-[#2f4a45]">
                                                        {incident.student_name ?? 'Not specified'}
                                                    </p>
                                                    {incident.student_id_number && (
                                                        <p className="mt-0.5 font-mono text-[11px] text-[#9baba7]">{incident.student_id_number}</p>
                                                    )}
                                                </td>
                                                <td className="max-w-[320px] px-6 py-4 text-[13px] leading-relaxed text-[#6b7f7b]">
                                                    <p className="line-clamp-3" title={incident.description}>
                                                        {incident.description}
                                                    </p>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <IncidentStatusBadge status={incident.status} />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex justify-end gap-1.5">
                                                        {incident.status === 'pending' ? (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    onClick={() => updateStatus(incident, 'reviewed')}
                                                                    disabled={busyIncident !== null}
                                                                    className="h-8 gap-1.5 rounded-lg bg-[#164f48] px-3 text-[11px] font-semibold text-white hover:bg-[#103f39]"
                                                                >
                                                                    {busyIncident === incident.id ? (
                                                                        <LoaderCircle className="size-3.5 animate-spin" aria-hidden="true" />
                                                                    ) : (
                                                                        <Check className="size-3.5" aria-hidden="true" />
                                                                    )}
                                                                    Reviewed
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => updateStatus(incident, 'dismissed')}
                                                                    disabled={busyIncident !== null}
                                                                    className="h-8 gap-1.5 rounded-lg border-[#e2ebe7] bg-white px-3 text-[11px] font-semibold text-[#6b7f7b] hover:bg-[#f7faf9]"
                                                                >
                                                                    <X className="size-3.5" aria-hidden="true" />
                                                                    Dismiss
                                                                </Button>
                                                            </>
                                                        ) : (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => updateStatus(incident, 'pending')}
                                                                disabled={busyIncident !== null}
                                                                className="h-8 rounded-lg border-[#e2ebe7] bg-white px-3 text-[11px] font-semibold text-[#6b7f7b] hover:bg-[#f7faf9]"
                                                            >
                                                                Reopen
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <ul className="divide-y divide-[#eef3f1] lg:hidden">
                                {incidents.map((incident) => (
                                    <li key={incident.id} className="space-y-2.5 px-5 py-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-[#1e3834]">{incident.examination_title ?? 'Incident'}</p>
                                                <p className="mt-0.5 text-xs text-[#7b8f8a]">
                                                    {incident.student_name ?? 'No specific student'} · {formatDate(incident.reported_at)}
                                                </p>
                                            </div>
                                            <IncidentStatusBadge status={incident.status} />
                                        </div>

                                        <p className="text-xs leading-relaxed text-[#6b7f7b]">{incident.description}</p>

                                        <div className="flex flex-wrap gap-1.5">
                                            {incident.status === 'pending' ? (
                                                <>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() => updateStatus(incident, 'reviewed')}
                                                        disabled={busyIncident !== null}
                                                        className="h-8 rounded-lg bg-[#164f48] px-3 text-[11px] font-semibold text-white hover:bg-[#103f39]"
                                                    >
                                                        Mark reviewed
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => updateStatus(incident, 'dismissed')}
                                                        disabled={busyIncident !== null}
                                                        className="h-8 rounded-lg border-[#e2ebe7] bg-white px-3 text-[11px] font-semibold text-[#6b7f7b]"
                                                    >
                                                        Dismiss
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => updateStatus(incident, 'pending')}
                                                    disabled={busyIncident !== null}
                                                    className="h-8 rounded-lg border-[#e2ebe7] bg-white px-3 text-[11px] font-semibold text-[#6b7f7b]"
                                                >
                                                    Reopen
                                                </Button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Panel>
            </div>
        </ProctorLayout>
    );
}
