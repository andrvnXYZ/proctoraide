import InputError from '@/components/input-error';
import ExaminationStatusBadge from '@/components/proctor/examination-status-badge';
import FlashMessages from '@/components/proctor/flash-messages';
import { EmptyState, Panel } from '@/components/proctor/panel';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProctorLayout from '@/layouts/proctor-layout';
import { type ProctorExamination } from '@/types/proctor';
import { Head, router, useForm } from '@inertiajs/react';
import { ClipboardList, KeyRound, LoaderCircle, Play, Plus, Square } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

interface ExaminationsProps {
    examinations: ProctorExamination[];
    sections: string[];
    openCreateForm: boolean;
}

type ExaminationForm = {
    title: string;
    subject: string;
    section: string;
    scheduled_date: string;
    start_time: string;
    end_time: string;
};

function today(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

export default function ExaminationsIndex({ examinations, sections, openCreateForm }: ExaminationsProps) {
    const [dialogOpen, setDialogOpen] = useState(openCreateForm);
    const [busyExamination, setBusyExamination] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<ExaminationForm>({
        title: '',
        subject: '',
        section: '',
        scheduled_date: today(),
        start_time: '09:00',
        end_time: '10:00',
    });

    useEffect(() => {
        if (openCreateForm) {
            setDialogOpen(true);
        }
    }, [openCreateForm]);

    const openDialog = () => {
        clearErrors();
        setDialogOpen(true);
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('proctor.examinations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setDialogOpen(false);
            },
        });
    };

    const changeStatus = (examination: ProctorExamination, action: 'start' | 'end') => {
        const url = action === 'start' ? route('proctor.examinations.start', examination.id) : route('proctor.examinations.end', examination.id);

        router.patch(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => setBusyExamination(`${examination.id}-${action}`),
                onFinish: () => setBusyExamination(null),
            },
        );
    };

    return (
        <ProctorLayout title="Examinations">
            <Head title="Examinations" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">Examinations</h2>
                    <p className="mt-1.5 text-sm text-[#6b7f7b]">
                        Create sessions, share the access code, and start or end each examination when your section is ready.
                    </p>
                </div>

                <Button
                    type="button"
                    onClick={openDialog}
                    className="h-10 shrink-0 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white shadow-[0_14px_26px_-16px_rgba(6,47,44,0.9)] hover:bg-[#103f39]"
                >
                    <Plus className="size-4" aria-hidden="true" />
                    Create Examination
                </Button>
            </div>

            <div className="mt-5 space-y-4">
                <FlashMessages />

                <Panel
                    title="Examination Sessions"
                    description={`${examinations.length} session${examinations.length === 1 ? '' : 's'} under your proctoring.`}
                >
                    {examinations.length === 0 ? (
                        <EmptyState
                            icon={ClipboardList}
                            title="No examination sessions yet."
                            description="Create your first examination to generate a six-character access code for your students."
                            action={
                                <Button
                                    type="button"
                                    onClick={openDialog}
                                    className="h-10 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white hover:bg-[#103f39]"
                                >
                                    <Plus className="size-4" aria-hidden="true" />
                                    Create Examination
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[980px] text-left">
                                    <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                        <tr>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Examination
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Course / Subject
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Section
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Schedule
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Access Code
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
                                        {examinations.map((examination) => (
                                            <tr key={examination.id} className="transition-colors hover:bg-[#fafcfb]">
                                                <td className="px-6 py-4">
                                                    <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                                    <p className="mt-0.5 text-[11px] text-[#9baba7]">
                                                        {examination.enrolled_students ?? 0} enrolled student
                                                        {(examination.enrolled_students ?? 0) === 1 ? '' : 's'}
                                                    </p>
                                                </td>
                                                <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{examination.subject}</td>
                                                <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{examination.section}</td>
                                                <td className="px-6 py-4">
                                                    <p className="text-[13px] font-medium text-[#3c534f]">{examination.time_range}</p>
                                                    <p className="mt-0.5 text-[11px] text-[#9baba7]">{examination.date_label}</p>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center gap-1.5 rounded-md border border-[#dbe7e3] bg-[#f6faf8] px-2.5 py-1 font-mono text-[12px] font-semibold tracking-[0.14em] text-[#1f5a51]">
                                                        <KeyRound className="size-3.5" aria-hidden="true" />
                                                        {examination.access_code}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <ExaminationStatusBadge status={examination.status} />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex justify-end">
                                                        {examination.status === 'scheduled' && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                onClick={() => changeStatus(examination, 'start')}
                                                                disabled={busyExamination !== null}
                                                                className="h-8 gap-1.5 rounded-lg bg-[#164f48] px-3 text-[11px] font-semibold text-white hover:bg-[#103f39]"
                                                            >
                                                                {busyExamination === `${examination.id}-start` ? (
                                                                    <LoaderCircle className="size-3.5 animate-spin" aria-hidden="true" />
                                                                ) : (
                                                                    <Play className="size-3.5" aria-hidden="true" />
                                                                )}
                                                                Start
                                                            </Button>
                                                        )}

                                                        {examination.status === 'ongoing' && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => changeStatus(examination, 'end')}
                                                                disabled={busyExamination !== null}
                                                                className="h-8 gap-1.5 rounded-lg border-[#e8c8c2] bg-white px-3 text-[11px] font-semibold text-[#a44037] hover:bg-[#fdf3f2] hover:text-[#8f332b]"
                                                            >
                                                                {busyExamination === `${examination.id}-end` ? (
                                                                    <LoaderCircle className="size-3.5 animate-spin" aria-hidden="true" />
                                                                ) : (
                                                                    <Square className="size-3.5" aria-hidden="true" />
                                                                )}
                                                                End
                                                            </Button>
                                                        )}

                                                        {examination.status === 'completed' && (
                                                            <span className="text-[11px] font-medium text-[#9baba7]">Session closed</span>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <ul className="divide-y divide-[#eef3f1] lg:hidden">
                                {examinations.map((examination) => (
                                    <li key={examination.id} className="space-y-3 px-5 py-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                                <p className="mt-0.5 text-xs text-[#7b8f8a]">
                                                    {examination.subject} · {examination.section}
                                                </p>
                                                <p className="mt-1 text-xs text-[#93a5a1]">{examination.schedule}</p>
                                            </div>
                                            <ExaminationStatusBadge status={examination.status} />
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="inline-flex items-center gap-1.5 rounded-md border border-[#dbe7e3] bg-[#f6faf8] px-2.5 py-1 font-mono text-[11px] font-semibold tracking-[0.14em] text-[#1f5a51]">
                                                <KeyRound className="size-3.5" aria-hidden="true" />
                                                {examination.access_code}
                                            </span>

                                            {examination.status === 'scheduled' && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    onClick={() => changeStatus(examination, 'start')}
                                                    disabled={busyExamination !== null}
                                                    className="h-8 gap-1.5 rounded-lg bg-[#164f48] px-3 text-[11px] font-semibold text-white hover:bg-[#103f39]"
                                                >
                                                    <Play className="size-3.5" aria-hidden="true" />
                                                    Start
                                                </Button>
                                            )}

                                            {examination.status === 'ongoing' && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => changeStatus(examination, 'end')}
                                                    disabled={busyExamination !== null}
                                                    className="h-8 gap-1.5 rounded-lg border-[#e8c8c2] bg-white px-3 text-[11px] font-semibold text-[#a44037] hover:bg-[#fdf3f2]"
                                                >
                                                    <Square className="size-3.5" aria-hidden="true" />
                                                    End
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

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Create Examination</DialogTitle>
                        <DialogDescription>
                            A six-character access code is generated automatically and can be shared with your section.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-4" noValidate>
                        <div>
                            <Label htmlFor="title">Examination title</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(event) => setData('title', event.target.value)}
                                placeholder="Midterm Exam"
                                className="mt-1.5"
                                autoComplete="off"
                            />
                            <InputError message={errors.title} className="mt-1.5" />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="subject">Course / Subject</Label>
                                <Input
                                    id="subject"
                                    value={data.subject}
                                    onChange={(event) => setData('subject', event.target.value)}
                                    placeholder="Networking 2"
                                    className="mt-1.5"
                                    autoComplete="off"
                                />
                                <InputError message={errors.subject} className="mt-1.5" />
                            </div>

                            <div>
                                <Label htmlFor="section">Section</Label>
                                <Input
                                    id="section"
                                    value={data.section}
                                    onChange={(event) => setData('section', event.target.value)}
                                    placeholder="BSIT-3A"
                                    list="proctor-sections"
                                    className="mt-1.5"
                                    autoComplete="off"
                                />
                                <datalist id="proctor-sections">
                                    {sections.map((section) => (
                                        <option key={section} value={section} />
                                    ))}
                                </datalist>
                                <InputError message={errors.section} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div>
                                <Label htmlFor="scheduled_date">Date</Label>
                                <Input
                                    id="scheduled_date"
                                    type="date"
                                    value={data.scheduled_date}
                                    onChange={(event) => setData('scheduled_date', event.target.value)}
                                    className="mt-1.5"
                                />
                                <InputError message={errors.scheduled_date} className="mt-1.5" />
                            </div>

                            <div>
                                <Label htmlFor="start_time">Start time</Label>
                                <Input
                                    id="start_time"
                                    type="time"
                                    value={data.start_time}
                                    onChange={(event) => setData('start_time', event.target.value)}
                                    className="mt-1.5"
                                />
                                <InputError message={errors.start_time} className="mt-1.5" />
                            </div>

                            <div>
                                <Label htmlFor="end_time">End time</Label>
                                <Input
                                    id="end_time"
                                    type="time"
                                    value={data.end_time}
                                    onChange={(event) => setData('end_time', event.target.value)}
                                    className="mt-1.5"
                                />
                                <InputError message={errors.end_time} className="mt-1.5" />
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)} disabled={processing}>
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="gap-2 bg-[#164f48] text-[13px] font-semibold text-white hover:bg-[#103f39]"
                            >
                                {processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                                Create Examination
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ProctorLayout>
    );
}
