import ExaminationStatusBadge from '@/components/student/examination-status-badge';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student-layout';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { type Announcement, type Examination } from '@/types/student';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Bell,
    Building2,
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    Clock,
    GraduationCap,
    History,
    Layers,
    Mail,
    Megaphone,
    PencilLine,
    PlayCircle,
    Video,
    type LucideIcon,
} from 'lucide-react';

interface StudentDashboardProps {
    /**
     * These props are optional on purpose: no examination module exists yet,
     * so the dashboard must render calm empty states until a controller
     * starts passing real records to this page.
     */
    upcomingExams?: Examination[];
    activeExam?: Examination | null;
    examHistory?: Examination[];
    announcements?: Announcement[];
    examinationsUrl?: string | null;
    historyUrl?: string | null;
}

function getGreeting(): string {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
}

function formatSchedule(examination: Examination): string {
    if (examination.schedule) {
        return examination.schedule;
    }

    if (!examination.starts_at) {
        return 'To be announced';
    }

    const start = new Date(examination.starts_at);

    if (Number.isNaN(start.getTime())) {
        return 'To be announced';
    }

    const date = start.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    const startTime = start.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });

    if (!examination.ends_at) {
        return `${date} · ${startTime}`;
    }

    const end = new Date(examination.ends_at);

    if (Number.isNaN(end.getTime())) {
        return `${date} · ${startTime}`;
    }

    const endTime = end.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });

    return `${date} · ${startTime} – ${endTime}`;
}

function formatExaminationDate(examination: Examination): string {
    const value = examination.ends_at ?? examination.starts_at;

    if (!value) {
        return examination.schedule ?? '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return examination.schedule ?? '—';
    }

    return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatAnnouncementDate(value?: string | null): string | null {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatScore(examination: Examination): string {
    if (examination.score === null || examination.score === undefined) {
        return '—';
    }

    return examination.total_items ? `${examination.score}/${examination.total_items}` : `${examination.score}`;
}

function StatCard({ icon: Icon, label, value, hint }: { icon: LucideIcon; label: string; value: string; hint: string }) {
    return (
        <div className="flex items-center gap-4 rounded-2xl border border-[#e2ebe7] bg-white px-5 py-4 shadow-[0_18px_40px_-38px_rgba(9,48,45,0.6)]">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#e7f2ef] text-[#1c6b5e]">
                <Icon className="size-5" aria-hidden="true" />
            </span>
            <div className="min-w-0">
                <p className="text-xl leading-none font-semibold tracking-tight text-[#102b28]">{value}</p>
                <p className="mt-1.5 truncate text-[12px] font-medium text-[#5f7b75]">{label}</p>
                <p className="mt-0.5 truncate text-[11px] text-[#9baba7]">{hint}</p>
            </div>
        </div>
    );
}

function EmptyState({ icon: Icon, title, description }: { icon: LucideIcon; title: string; description: string }) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <span className="flex size-12 items-center justify-center rounded-full bg-[#f1f7f5] text-[#8aa39d]">
                <Icon className="size-5" aria-hidden="true" />
            </span>
            <p className="mt-4 text-sm font-semibold text-[#2f4a45]">{title}</p>
            <p className="mt-1 max-w-xs text-xs leading-relaxed text-[#93a5a1]">{description}</p>
        </div>
    );
}

function Panel({
    id,
    title,
    description,
    action,
    children,
    className,
}: {
    id?: string;
    title: string;
    description?: string;
    action?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <section
            id={id}
            className={cn(
                'scroll-mt-28 overflow-hidden rounded-2xl border border-[#e2ebe7] bg-white shadow-[0_18px_40px_-36px_rgba(9,48,45,0.55)]',
                className,
            )}
        >
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#eef3f1] px-5 py-4 sm:px-6">
                <div className="min-w-0">
                    <h2 className="text-[15px] font-semibold tracking-tight text-[#102b28]">{title}</h2>
                    {description && <p className="mt-0.5 text-xs text-[#93a5a1]">{description}</p>}
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}

function ProfileRow({ icon: Icon, label, value }: { icon: LucideIcon; label: string; value?: string | null }) {
    return (
        <div className="flex items-start gap-3 py-3">
            <Icon className="mt-0.5 size-4 shrink-0 text-[#9baba7]" aria-hidden="true" />
            <div className="min-w-0">
                <dt className="text-[10px] font-semibold tracking-[0.14em] text-[#9baba7] uppercase">{label}</dt>
                <dd className="mt-0.5 truncate text-[13px] font-medium text-[#2f4a45]" title={value ?? undefined}>
                    {value && value.trim().length > 0 ? value : 'Not provided'}
                </dd>
            </div>
        </div>
    );
}

export default function StudentDashboard({
    upcomingExams,
    activeExam,
    examHistory,
    announcements,
    examinationsUrl,
    historyUrl,
}: StudentDashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const upcoming = upcomingExams ?? [];
    const history = examHistory ?? [];
    const notices = announcements ?? [];
    const active = activeExam ?? null;

    const completedCount = history.filter((examination) => examination.status === 'completed').length;
    const unreadCount = notices.filter((announcement) => announcement.is_unread).length;
    const firstName = user.first_name?.trim() || user.name.split(' ')[0] || 'Student';
    const initials = user.name
        .trim()
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    const today = new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    return (
        <StudentLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="min-w-0">
                    <p className="text-[10px] font-semibold tracking-[0.24em] text-[#2a7b6d] uppercase">{today}</p>
                    <h2 className="mt-2.5 text-3xl font-semibold tracking-tight text-[#102b28] sm:text-4xl">
                        {getGreeting()}, {firstName}!
                    </h2>
                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-[#6b7f7b] sm:text-[15px]">
                        Please check your upcoming examinations and make sure your device is ready before every session.
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <Button
                        asChild
                        className="h-11 w-full justify-center gap-2 rounded-lg bg-[#164f48] px-5 text-[13px] font-semibold text-white shadow-[0_14px_26px_-16px_rgba(6,47,44,0.9)] hover:bg-[#103f39] sm:w-auto"
                    >
                        <a href="#active-examination">
                            <PlayCircle aria-hidden="true" />
                            Join Examination
                        </a>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full justify-center gap-2 rounded-lg border-[#cfe0db] bg-white px-5 text-[13px] font-semibold text-[#2a6f64] shadow-none hover:bg-[#f3f8f6] hover:text-[#174f47] sm:w-auto"
                    >
                        <a href="#examination-history">
                            <History aria-hidden="true" />
                            View Examination History
                        </a>
                    </Button>
                </div>
            </div>

            <div className="mt-7 grid gap-4 sm:grid-cols-3">
                <StatCard icon={CalendarClock} label="Upcoming exams" value={String(upcoming.length)} hint="Scheduled for you" />
                <StatCard icon={Clock} label="Active now" value={active ? '1' : 'None'} hint={active ? 'Open for joining' : 'Nothing in progress'} />
                <StatCard icon={CheckCircle2} label="Completed" value={String(completedCount)} hint="Examinations in your history" />
            </div>

            <section id="active-examination" className="mt-5 scroll-mt-28">
                <div className="relative overflow-hidden rounded-2xl border border-[#d8e7e2] bg-white shadow-[0_18px_40px_-36px_rgba(9,48,45,0.55)]">
                    <span aria-hidden="true" className="absolute inset-y-0 left-0 w-1 bg-[#2d8a7b]" />

                    <div className="flex flex-col gap-5 p-6 pl-7 sm:p-7 sm:pl-8 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex min-w-0 gap-4">
                            <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f2ef] text-[#1c6b5e]">
                                <Video className="size-5" aria-hidden="true" />
                            </span>

                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-[10px] font-semibold tracking-[0.22em] text-[#2a7b6d] uppercase">Active examination</p>
                                    {active ? (
                                        <ExaminationStatusBadge status={active.status} />
                                    ) : (
                                        <span className="rounded-full border border-[#e4ece9] bg-[#f7faf9] px-2.5 py-1 text-[11px] font-semibold text-[#8a9b97]">
                                            None in progress
                                        </span>
                                    )}
                                </div>

                                <h2 className="mt-2 text-lg font-semibold tracking-tight break-words text-[#102b28]">
                                    {active?.title ?? 'No examination in progress'}
                                </h2>

                                <p className="mt-1 text-[13px] text-[#6b7f7b]">
                                    {active
                                        ? [active.course_code ?? active.course, formatSchedule(active)].filter(Boolean).join(' · ')
                                        : 'When your proctor opens an examination, it will appear here with a join button.'}
                                </p>

                                {active?.duration_minutes ? (
                                    <p className="mt-2 inline-flex items-center gap-1.5 text-xs text-[#8a9b97]">
                                        <Clock className="size-3.5" aria-hidden="true" />
                                        {active.duration_minutes} minutes allowed
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <div className="shrink-0 lg:pl-4">
                            {active && active.join_url ? (
                                <Button
                                    asChild
                                    className="h-11 w-full gap-2 rounded-lg bg-[#164f48] px-5 text-[13px] font-semibold text-white shadow-[0_14px_26px_-16px_rgba(6,47,44,0.9)] hover:bg-[#103f39] sm:w-auto"
                                >
                                    <a href={active.join_url}>
                                        Join Exam
                                        <ArrowUpRight aria-hidden="true" />
                                    </a>
                                </Button>
                            ) : (
                                <span className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-[#dbe7e3] bg-[#f7faf9] px-4 text-[13px] font-medium text-[#7b8f8a] sm:w-auto">
                                    <Clock className="size-4" aria-hidden="true" />
                                    {active ? 'Join link will open soon' : 'Waiting for the next session'}
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <div className="mt-5 grid items-start gap-5 xl:grid-cols-3">
                <Panel
                    id="upcoming-examinations"
                    className="xl:col-span-2"
                    title="Upcoming Examinations"
                    description="Your scheduled examination sessions."
                    action={
                        examinationsUrl ? (
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="h-9 rounded-lg border-[#d7e4e0] bg-white px-3.5 text-xs font-semibold text-[#2a6f64] shadow-none hover:bg-[#f3f8f6]"
                            >
                                <a href={examinationsUrl}>View all</a>
                            </Button>
                        ) : (
                            <span className="rounded-full bg-[#f1f6f4] px-2.5 py-1 text-[11px] font-semibold text-[#5f7b75]">
                                {upcoming.length} scheduled
                            </span>
                        )
                    }
                >
                    {upcoming.length === 0 ? (
                        <EmptyState
                            icon={ClipboardList}
                            title="No examinations scheduled yet."
                            description="Records will appear here when your proctor assigns an examination to your section."
                        />
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full min-w-[560px] text-left">
                                    <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                        <tr>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Examination
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Course
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 font-semibold">
                                                Schedule
                                            </th>
                                            <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#eef3f1]">
                                        {upcoming.map((examination) => (
                                            <tr key={examination.id} className="transition-colors hover:bg-[#fafcfb]">
                                                <td className="px-6 py-4">
                                                    <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                                    {examination.duration_minutes ? (
                                                        <p className="mt-0.5 text-[11px] text-[#9baba7]">{examination.duration_minutes} minutes</p>
                                                    ) : null}
                                                </td>
                                                <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">
                                                    {examination.course_code ?? examination.course ?? '—'}
                                                </td>
                                                <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{formatSchedule(examination)}</td>
                                                <td className="px-6 py-4 text-right">
                                                    <ExaminationStatusBadge status={examination.status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <ul className="divide-y divide-[#eef3f1] md:hidden">
                                {upcoming.map((examination) => (
                                    <li key={examination.id} className="px-5 py-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                                <p className="mt-1 text-xs text-[#7b8f8a]">
                                                    {examination.course_code ?? examination.course ?? 'Course not set'}
                                                </p>
                                                <p className="mt-1 text-xs text-[#93a5a1]">{formatSchedule(examination)}</p>
                                            </div>
                                            <ExaminationStatusBadge status={examination.status} />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Panel>

                <div className="space-y-5">
                    <section className="overflow-hidden rounded-2xl border border-[#e2ebe7] bg-white shadow-[0_18px_40px_-36px_rgba(9,48,45,0.55)]">
                        <div className="flex items-center gap-3.5 border-b border-[#eef3f1] px-5 py-4">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#f4e9c7] text-sm font-bold text-[#3b4527]">
                                {initials || 'S'}
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold text-[#102b28]" title={user.name}>
                                    {user.name}
                                </p>
                                <p className="mt-0.5 truncate text-[11px] text-[#8a9b97]">{user.student_id_number ?? 'Student account'}</p>
                            </div>
                            <Link
                                href={route('profile.edit')}
                                aria-label="Edit your profile"
                                className="flex size-8 shrink-0 items-center justify-center rounded-lg text-[#8a9b97] transition-colors hover:bg-[#f4f8f6] hover:text-[#1c6b5e] focus-visible:ring-2 focus-visible:ring-[#2d8a7b] focus-visible:outline-hidden"
                            >
                                <PencilLine className="size-4" aria-hidden="true" />
                            </Link>
                        </div>

                        <dl className="divide-y divide-[#f1f5f3] px-5">
                            <ProfileRow icon={Mail} label="Email" value={user.email} />
                            <ProfileRow icon={Building2} label="College" value={user.college} />
                            <ProfileRow icon={Layers} label="Department" value={user.department} />
                            <ProfileRow icon={GraduationCap} label="Year level" value={user.year_level} />
                        </dl>

                        <div className="px-5 pt-1 pb-4">
                            <Link
                                href={route('profile.edit')}
                                className="inline-flex items-center gap-1 text-xs font-semibold text-[#1c6b5e] transition-colors hover:text-[#124f46] hover:underline"
                            >
                                View full profile
                                <ArrowUpRight className="size-3.5" aria-hidden="true" />
                            </Link>
                        </div>
                    </section>

                    <Panel
                        title="Notifications & Announcements"
                        action={
                            <span className="relative flex size-9 items-center justify-center rounded-lg bg-[#f1f6f4] text-[#5f7b75]">
                                <Bell className="size-4" aria-hidden="true" />
                                {unreadCount > 0 && (
                                    <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-[#c25a4f] ring-2 ring-[#f1f6f4]" />
                                )}
                            </span>
                        }
                    >
                        {notices.length === 0 ? (
                            <EmptyState
                                icon={Megaphone}
                                title="No announcements yet."
                                description="Updates and reminders from your proctors will appear here."
                            />
                        ) : (
                            <ul className="divide-y divide-[#eef3f1]">
                                {notices.map((announcement) => (
                                    <li key={announcement.id} className="px-5 py-4">
                                        <div className="flex gap-3">
                                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#f1f7f5] text-[#5d8a80]">
                                                <Megaphone className="size-4" aria-hidden="true" />
                                            </span>
                                            <div className="min-w-0">
                                                <div className="flex items-start gap-2">
                                                    <p className="text-[13px] font-semibold text-[#223c38]">{announcement.title}</p>
                                                    {announcement.is_unread && (
                                                        <span className="mt-1 size-1.5 shrink-0 rounded-full bg-[#2f9e6b]" title="Unread" />
                                                    )}
                                                </div>
                                                {announcement.body && (
                                                    <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-[#7b8f8a]">{announcement.body}</p>
                                                )}
                                                {formatAnnouncementDate(announcement.published_at) && (
                                                    <p className="mt-1.5 text-[10px] font-medium tracking-wide text-[#a3b2ae] uppercase">
                                                        {formatAnnouncementDate(announcement.published_at)}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>
            </div>

            <Panel
                id="examination-history"
                className="mt-5"
                title="Examination History"
                description="Completed and closed examination sessions."
                action={
                    historyUrl ? (
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            className="h-9 rounded-lg border-[#d7e4e0] bg-white px-3.5 text-xs font-semibold text-[#2a6f64] shadow-none hover:bg-[#f3f8f6]"
                        >
                            <a href={historyUrl}>View all</a>
                        </Button>
                    ) : (
                        <span className="rounded-full bg-[#f1f6f4] px-2.5 py-1 text-[11px] font-semibold text-[#5f7b75]">
                            {history.length} records
                        </span>
                    )
                }
            >
                {history.length === 0 ? (
                    <EmptyState
                        icon={History}
                        title="No examination records yet."
                        description="Once you finish an examination, your results will be listed here for review."
                    />
                ) : (
                    <>
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[640px] text-left">
                                <thead className="bg-[#f4f7f5] text-[10px] font-semibold tracking-[0.16em] text-[#7b8f8a] uppercase">
                                    <tr>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Examination
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Course
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Date
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 font-semibold">
                                            Score
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 text-right font-semibold">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#eef3f1]">
                                    {history.map((examination) => (
                                        <tr key={examination.id} className="transition-colors hover:bg-[#fafcfb]">
                                            <td className="px-6 py-4 text-sm font-semibold text-[#1e3834]">{examination.title}</td>
                                            <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">
                                                {examination.course_code ?? examination.course ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{formatExaminationDate(examination)}</td>
                                            <td className="px-6 py-4 text-[13px] font-medium text-[#2f4a45]">{formatScore(examination)}</td>
                                            <td className="px-6 py-4 text-right">
                                                <ExaminationStatusBadge status={examination.status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <ul className="divide-y divide-[#eef3f1] md:hidden">
                            {history.map((examination) => (
                                <li key={examination.id} className="px-5 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                            <p className="mt-1 text-xs text-[#7b8f8a]">
                                                {examination.course_code ?? examination.course ?? 'Course not set'}
                                            </p>
                                            <p className="mt-1 text-xs text-[#93a5a1]">{formatExaminationDate(examination)}</p>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-2">
                                            <ExaminationStatusBadge status={examination.status} />
                                            <span className="text-xs font-semibold text-[#2f4a45]">{formatScore(examination)}</span>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Panel>
        </StudentLayout>
    );
}
