import InputError from '@/components/input-error';
import FlashMessages from '@/components/proctor/flash-messages';
import { EmptyState, Panel } from '@/components/proctor/panel';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProctorLayout from '@/layouts/proctor-layout';
import { type ProctorAssignment, type ProctorStudent } from '@/types/proctor';
import { Head, useForm } from '@inertiajs/react';
import { GraduationCap, LoaderCircle, Plus, Search, Users } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

interface StudentsProps {
    students: ProctorStudent[];
    sections: string[];
    subjects: string[];
    assignments: ProctorAssignment[];
}

interface StudentLookup {
    name: string;
    student_id_number: string;
    email: string | null;
    year_level: string | null;
}

const avatarPalette = ['bg-[#e7f2ef] text-[#1c6b5e]', 'bg-[#f4e9c7] text-[#3b4527]', 'bg-[#e8eef7] text-[#2f4a6b]', 'bg-[#f7e8e4] text-[#8a4a3d]'];

function initials(name: string): string {
    return name
        .trim()
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export default function StudentsIndex({ students, sections, subjects, assignments }: StudentsProps) {
    const [query, setQuery] = useState('');
    const [section, setSection] = useState('all');

    const [addDialogOpen, setAddDialogOpen] = useState(false);
    const [lookupId, setLookupId] = useState('');
    const [lookupError, setLookupError] = useState<string | null>(null);
    const [searching, setSearching] = useState(false);
    const [foundStudent, setFoundStudent] = useState<StudentLookup | null>(null);

    const enrollmentForm = useForm({ student_id: '', subject: '', section: '' });

    const assignmentSubjects = useMemo(() => Array.from(new Set(assignments.map((item) => item.subject))).sort(), [assignments]);

    const assignmentSections = useMemo(() => {
        const matching = assignments.filter((item) => item.subject === enrollmentForm.data.subject);

        return Array.from(new Set(matching.map((item) => item.section))).sort();
    }, [assignments, enrollmentForm.data.subject]);

    const resetAddDialog = () => {
        setLookupId('');
        setLookupError(null);
        setFoundStudent(null);
        setSearching(false);
        enrollmentForm.reset();
        enrollmentForm.clearErrors();
    };

    const closeAddDialog = () => {
        if (enrollmentForm.processing) {
            return;
        }

        setAddDialogOpen(false);
        resetAddDialog();
    };

    const openAddDialog = () => {
        resetAddDialog();
        setAddDialogOpen(true);
    };

    const searchStudent = async (event?: React.FormEvent) => {
        event?.preventDefault();

        const studentId = lookupId.trim();

        setLookupError(null);
        setFoundStudent(null);
        enrollmentForm.clearErrors();
        enrollmentForm.setData('student_id', '');

        if (studentId === '') {
            setLookupError('Enter the Student ID to search for.');
            return;
        }

        setSearching(true);

        try {
            const response = await fetch(route('proctor.students.search', { student_id: studentId }), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = (await response.json().catch(() => null)) as { student?: StudentLookup; message?: string } | null;

            if (!response.ok || !payload?.student) {
                setLookupError(payload?.message ?? 'No student found with this Student ID.');
                return;
            }

            setFoundStudent(payload.student);
            enrollmentForm.setData('student_id', payload.student.student_id_number);
        } catch {
            setLookupError('Unable to search right now. Please try again.');
        } finally {
            setSearching(false);
        }
    };

    const changeSubject = (value: string) => {
        enrollmentForm.setData('subject', value);

        const allowed = assignments.filter((item) => item.subject === value).map((item) => item.section);

        if (enrollmentForm.data.section !== '' && !allowed.includes(enrollmentForm.data.section)) {
            enrollmentForm.setData('section', '');
        }
    };

    const submitEnrollment: FormEventHandler = (event) => {
        event.preventDefault();

        if (!foundStudent) {
            setLookupError('Search for and select a student first.');
            return;
        }

        enrollmentForm.post(route('proctor.students.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setAddDialogOpen(false);
                resetAddDialog();
            },
        });
    };

    // Enter in the Student ID field searches first; once a student is found the
    // same form submits the enrollment.
    const handleFormSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        if (!foundStudent) {
            void searchStudent();
            return;
        }

        submitEnrollment(event);
    };

    const filtered = useMemo(() => {
        const needle = query.trim().toLowerCase();

        return students.filter((student) => {
            const matchesSection = section === 'all' || student.section === section;
            const matchesQuery =
                needle === '' ||
                student.student_name.toLowerCase().includes(needle) ||
                (student.student_id_number ?? '').toLowerCase().includes(needle) ||
                (student.subject ?? '').toLowerCase().includes(needle);

            return matchesSection && matchesQuery;
        });
    }, [students, query, section]);

    return (
        <ProctorLayout title="Students">
            <Head title="Students" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">Students</h2>
                    <p className="mt-1.5 text-sm text-[#6b7f7b]">
                        Students enrolled in the subjects and sections assigned to you. Other sections are not shown.
                    </p>
                </div>

                <Button
                    type="button"
                    onClick={openAddDialog}
                    className="h-10 shrink-0 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white shadow-[0_14px_26px_-16px_rgba(6,47,44,0.9)] hover:bg-[#103f39]"
                >
                    <Plus className="size-4" aria-hidden="true" />
                    Add Student
                </Button>
            </div>

            <div className="mt-4 empty:hidden">
                <FlashMessages />
            </div>

            <div className="mt-5 grid gap-3 sm:grid-cols-3">
                <div className="rounded-xl border border-[#e2ebe7] bg-white px-5 py-4">
                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Enrolled students</p>
                    <p className="mt-1 text-2xl font-semibold tracking-tight text-[#102b28] tabular-nums">{students.length}</p>
                </div>
                <div className="rounded-xl border border-[#e2ebe7] bg-white px-5 py-4">
                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Sections</p>
                    <p className="mt-1 text-2xl font-semibold tracking-tight text-[#102b28] tabular-nums">{sections.length}</p>
                </div>
                <div className="rounded-xl border border-[#e2ebe7] bg-white px-5 py-4">
                    <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Subjects</p>
                    <p className="mt-1 text-2xl font-semibold tracking-tight text-[#102b28] tabular-nums">{subjects.length}</p>
                </div>
            </div>

            <Panel
                className="mt-4"
                title="Student Roster"
                description="Search by name or student ID to find a student quickly."
                action={
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="relative">
                            <Search
                                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[#9baba7]"
                                aria-hidden="true"
                            />
                            <Input
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Search students"
                                aria-label="Search students"
                                className="h-9 w-full pl-9 text-xs sm:w-56"
                            />
                        </div>

                        <select
                            value={section}
                            onChange={(event) => setSection(event.target.value)}
                            aria-label="Filter by section"
                            className="h-9 rounded-md border border-[#dbe7e3] bg-white px-2.5 text-xs font-medium text-[#3c534f] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20"
                        >
                            <option value="all">All sections</option>
                            {sections.map((item) => (
                                <option key={item} value={item}>
                                    {item}
                                </option>
                            ))}
                        </select>
                    </div>
                }
            >
                {students.length === 0 ? (
                    <EmptyState
                        icon={GraduationCap}
                        title="No students enrolled yet."
                        description="Students appear here once they are enrolled in one of your subjects or sections."
                    />
                ) : filtered.length === 0 ? (
                    <EmptyState icon={Users} title="No matching students." description="Try a different name, student ID, or section filter." />
                ) : (
                    <>
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[720px] text-left">
                                <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                    <tr>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Student
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Student ID
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Subject
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Section
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Year Level
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#eef3f1]">
                                    {filtered.map((student, index) => (
                                        <tr key={student.id} className="transition-colors hover:bg-[#fafcfb]">
                                            <td className="px-6 py-3.5">
                                                <div className="flex items-center gap-3">
                                                    <span
                                                        className={`flex size-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ${avatarPalette[index % avatarPalette.length]}`}
                                                    >
                                                        {initials(student.student_name)}
                                                    </span>
                                                    <p className="text-sm font-semibold text-[#1e3834]">{student.student_name}</p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-3.5 font-mono text-[12px] text-[#5c6f6b]">{student.student_id_number ?? '—'}</td>
                                            <td className="px-6 py-3.5 text-[13px] text-[#6b7f7b]">{student.subject ?? '—'}</td>
                                            <td className="px-6 py-3.5 text-[13px] text-[#6b7f7b]">{student.section ?? '—'}</td>
                                            <td className="px-6 py-3.5 text-[13px] text-[#6b7f7b]">{student.year_level ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <ul className="divide-y divide-[#eef3f1] md:hidden">
                            {filtered.map((student, index) => (
                                <li key={student.id} className="flex items-center gap-3 px-5 py-4">
                                    <span
                                        className={`flex size-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ${avatarPalette[index % avatarPalette.length]}`}
                                    >
                                        {initials(student.student_name)}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-[#1e3834]">{student.student_name}</p>
                                        <p className="mt-0.5 font-mono text-[11px] text-[#7b8f8a]">{student.student_id_number ?? '—'}</p>
                                        <p className="mt-0.5 text-[11px] text-[#93a5a1]">
                                            {[student.subject, student.section].filter(Boolean).join(' · ') || '—'}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Panel>
            <Dialog
                open={addDialogOpen}
                onOpenChange={(open) => {
                    if (open) {
                        setAddDialogOpen(true);
                    } else {
                        closeAddDialog();
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Student</DialogTitle>
                        <DialogDescription>
                            Search for an existing student account by Student ID, then enroll it in one of your subjects.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleFormSubmit} className="space-y-5">
                        <div className="space-y-2">
                            <Label htmlFor="add-student-id">Student ID</Label>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Input
                                    id="add-student-id"
                                    value={lookupId}
                                    onChange={(event) => setLookupId(event.target.value)}
                                    placeholder="e.g. 2024-00006"
                                    autoComplete="off"
                                    className="h-10"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => void searchStudent()}
                                    disabled={searching}
                                    className="h-10 shrink-0 gap-2"
                                >
                                    {searching ? (
                                        <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />
                                    ) : (
                                        <Search className="size-4" aria-hidden="true" />
                                    )}
                                    {searching ? 'Searching…' : 'Search Student'}
                                </Button>
                            </div>
                            <p className="text-[11px] text-[#93a5a1]">Enter the Student ID exactly as it appears on the student account.</p>
                        </div>

                        {lookupError && (
                            <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700">
                                {lookupError}
                            </p>
                        )}

                        {foundStudent && (
                            <div className="rounded-xl border border-[#dbe7e3] bg-[#f7faf9] px-4 py-3">
                                <p className="text-[10px] font-semibold tracking-[0.16em] text-[#9baba7] uppercase">Student information</p>
                                <p className="mt-1.5 text-sm font-semibold text-[#1e3834]">{foundStudent.name}</p>
                                <dl className="mt-2 grid gap-1 text-xs text-[#5c6f6b] sm:grid-cols-2">
                                    <div>
                                        <dt className="inline text-[#93a5a1]">Student ID: </dt>
                                        <dd className="inline font-mono">{foundStudent.student_id_number ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="inline text-[#93a5a1]">Year level: </dt>
                                        <dd className="inline">{foundStudent.year_level ?? '—'}</dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="inline text-[#93a5a1]">Email: </dt>
                                        <dd className="inline break-all">{foundStudent.email ?? '—'}</dd>
                                    </div>
                                </dl>
                            </div>
                        )}

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="add-student-subject">Subject</Label>
                                <select
                                    id="add-student-subject"
                                    value={enrollmentForm.data.subject}
                                    onChange={(event) => changeSubject(event.target.value)}
                                    disabled={!foundStudent || assignmentSubjects.length === 0}
                                    className="h-10 w-full rounded-md border border-[#dbe7e3] bg-white px-2.5 text-sm text-[#3c534f] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20 disabled:cursor-not-allowed disabled:bg-[#f4f7f5] disabled:text-[#9baba7]"
                                >
                                    <option value="">Select a subject</option>
                                    {assignmentSubjects.map((item) => (
                                        <option key={item} value={item}>
                                            {item}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={enrollmentForm.errors.subject} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="add-student-section">Section</Label>
                                <select
                                    id="add-student-section"
                                    value={enrollmentForm.data.section}
                                    onChange={(event) => enrollmentForm.setData('section', event.target.value)}
                                    disabled={!foundStudent || enrollmentForm.data.subject === ''}
                                    className="h-10 w-full rounded-md border border-[#dbe7e3] bg-white px-2.5 text-sm text-[#3c534f] focus:border-[#2d8a7b] focus:ring-[#2d8a7b]/20 disabled:cursor-not-allowed disabled:bg-[#f4f7f5] disabled:text-[#9baba7]"
                                >
                                    <option value="">{enrollmentForm.data.subject === '' ? 'Select a subject first' : 'Select a section'}</option>
                                    {assignmentSections.map((item) => (
                                        <option key={item} value={item}>
                                            {item}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={enrollmentForm.errors.section} />
                            </div>
                        </div>

                        {assignmentSubjects.length === 0 && (
                            <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                You have no subject and section assigned yet. Create an examination or receive an enrollment for a subject first.
                            </p>
                        )}

                        <InputError message={enrollmentForm.errors.student_id} />

                        <DialogFooter className="gap-2 pt-1">
                            <Button type="button" variant="outline" onClick={closeAddDialog} disabled={enrollmentForm.processing}>
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    enrollmentForm.processing ||
                                    !foundStudent ||
                                    enrollmentForm.data.subject === '' ||
                                    enrollmentForm.data.section === ''
                                }
                                className="gap-2 bg-[#164f48] text-white hover:bg-[#103f39]"
                            >
                                {enrollmentForm.processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                                Add Student
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ProctorLayout>
    );
}
