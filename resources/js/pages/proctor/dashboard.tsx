import ExaminationStatusBadge from '@/components/proctor/examination-status-badge';
import { EmptyState, Panel } from '@/components/proctor/panel';
import StatPill from '@/components/proctor/stat-pill';
import { Button } from '@/components/ui/button';
import ProctorLayout from '@/layouts/proctor-layout';
import { type SharedData } from '@/types';
import { type ProctorExamination } from '@/types/proctor';
import { Head, Link, usePage } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';

interface DashboardProps {
    stats: {
        active_examinations: number;
        reported_incidents: number;
        pending_incidents: number;
        active_students: number;
    };
    upcomingExaminations: ProctorExamination[];
    totalExaminations: number;
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

export default function ProctorDashboard({ stats, upcomingExaminations, totalExaminations }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const firstName = user.first_name?.trim() || user.name.split(' ')[0] || 'Proctor';

    return (
        <ProctorLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                <div className="min-w-0">
                    <h2 className="text-2xl font-semibold tracking-tight text-[#102b28] sm:text-[28px]">
                        {getGreeting()}, {firstName}!
                    </h2>
                    <p className="mt-1.5 text-sm text-[#6b7f7b]">Here&apos;s what&apos;s happening with your examinations.</p>
                </div>

                <div className="flex flex-wrap gap-2.5">
                    <StatPill label="Active Examinations" value={stats.active_examinations} tone="teal" />
                    <StatPill label="Reported Incidents" value={stats.reported_incidents} tone="red" />
                    <StatPill label="Active Students" value={stats.active_students} tone="green" />
                </div>
            </div>

            <div className="mt-8 flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs text-[#93a5a1]">
                    {stats.pending_incidents > 0
                        ? `${stats.pending_incidents} incident ${stats.pending_incidents === 1 ? 'report is' : 'reports are'} awaiting review.`
                        : 'No incident reports are awaiting review.'}
                </p>

                <Button
                    asChild
                    className="h-10 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white shadow-[0_14px_26px_-16px_rgba(6,47,44,0.9)] hover:bg-[#103f39]"
                >
                    <Link href={route('proctor.examinations.index', { create: 1 })}>
                        <Plus className="size-4" aria-hidden="true" />
                        Create Examination
                    </Link>
                </Button>
            </div>

            <Panel
                className="mt-3"
                title="Upcoming Examinations"
                action={
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="h-9 rounded-lg border-[#d7e4e0] bg-white px-3.5 text-xs font-semibold text-[#2a6f64] shadow-none hover:bg-[#f3f8f6] hover:text-[#174f47]"
                    >
                        <Link href={route('proctor.examinations.index')}>View all</Link>
                    </Button>
                }
            >
                {upcomingExaminations.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title={totalExaminations === 0 ? 'No examinations yet.' : 'Nothing scheduled right now.'}
                        description="Create an examination session to generate an access code and start tracking attendance."
                        action={
                            <Button
                                asChild
                                className="h-10 gap-2 rounded-lg bg-[#164f48] px-4 text-[13px] font-semibold text-white hover:bg-[#103f39]"
                            >
                                <Link href={route('proctor.examinations.index', { create: 1 })}>
                                    <Plus className="size-4" aria-hidden="true" />
                                    Create Examination
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[620px] text-left">
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
                                    {upcomingExaminations.map((examination) => (
                                        <tr key={examination.id} className="transition-colors hover:bg-[#fafcfb]">
                                            <td className="px-6 py-4">
                                                <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                                <p className="mt-0.5 text-[11px] text-[#9baba7]">{examination.section}</p>
                                            </td>
                                            <td className="px-6 py-4 text-[13px] text-[#6b7f7b]">{examination.subject}</td>
                                            <td className="px-6 py-4">
                                                <p className="text-[13px] font-medium text-[#3c534f]">{examination.time_range}</p>
                                                <p className="mt-0.5 text-[11px] text-[#9baba7]">{examination.date_label}</p>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <ExaminationStatusBadge status={examination.status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <ul className="divide-y divide-[#eef3f1] md:hidden">
                            {upcomingExaminations.map((examination) => (
                                <li key={examination.id} className="px-5 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-[#1e3834]">{examination.title}</p>
                                            <p className="mt-1 text-xs text-[#7b8f8a]">{examination.subject}</p>
                                            <p className="mt-1 text-xs text-[#93a5a1]">{examination.schedule}</p>
                                        </div>
                                        <ExaminationStatusBadge status={examination.status} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Panel>
        </ProctorLayout>
    );
}
