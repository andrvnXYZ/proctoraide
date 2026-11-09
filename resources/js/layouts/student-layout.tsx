import BrandMark from '@/components/auth/brand-mark';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, CalendarCheck2, ClipboardList, LayoutDashboard, LogOut, Menu, UserRound, type LucideIcon } from 'lucide-react';
import { useState } from 'react';

type NavigationItem = {
    label: string;
    href?: string;
    icon: LucideIcon;
    comingSoon?: boolean;
};

function SidebarNavigationGroup({
    title,
    items,
    currentUrl,
    onNavigate,
}: {
    title: string;
    items: NavigationItem[];
    currentUrl: string;
    onNavigate?: () => void;
}) {
    const currentPath = currentUrl.split('?')[0];

    return (
        <div>
            <p className="px-3 text-[10px] font-semibold tracking-[0.24em] text-white/40 uppercase">{title}</p>
            <ul className="mt-2 space-y-1">
                {items.map((item) => {
                    const Icon = item.icon;
                    // Ziggy can return absolute URLs, so compare paths instead of full URLs.
                    const itemPath = item.href?.replace(/^https?:\/\/[^/]+/, '') ?? '';
                    const isActive = itemPath !== '' && (currentPath === itemPath || currentPath.startsWith(`${itemPath}/`));
                    const itemClasses =
                        'flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-[#8fd6c7] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b3b3d] focus-visible:outline-hidden';

                    if (!item.href || item.comingSoon) {
                        return (
                            <li key={item.label}>
                                <span aria-disabled="true" className={cn(itemClasses, 'cursor-not-allowed text-white/35')}>
                                    <Icon className="size-[18px] shrink-0" aria-hidden="true" />
                                    <span className="truncate">{item.label}</span>
                                    <span className="ml-auto rounded-full bg-white/[0.06] px-2 py-0.5 text-[9px] font-semibold tracking-[0.12em] text-white/30 uppercase">
                                        Soon
                                    </span>
                                </span>
                            </li>
                        );
                    }

                    return (
                        <li key={item.label}>
                            <Link
                                href={item.href}
                                prefetch
                                onClick={onNavigate}
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    itemClasses,
                                    isActive
                                        ? 'bg-white/[0.13] text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]'
                                        : 'text-white/70 hover:bg-white/[0.06] hover:text-white',
                                )}
                            >
                                <Icon className="size-[18px] shrink-0" aria-hidden="true" />
                                <span className="truncate">{item.label}</span>
                                {isActive && <span className="ml-auto size-1.5 rounded-full bg-[#8fd6c7]" aria-hidden="true" />}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

function SidebarPanel({ currentUrl, onNavigate }: { currentUrl: string; onNavigate?: () => void }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const user = auth.user;

    const learningNavigation: NavigationItem[] = [
        { label: 'Dashboard', href: route('student.dashboard'), icon: LayoutDashboard },
        { label: 'Courses', icon: BookOpen, comingSoon: true },
        { label: 'Examinations', icon: ClipboardList, comingSoon: true },
        { label: 'Attendance', icon: CalendarCheck2, comingSoon: true },
    ];

    const accountNavigation: NavigationItem[] = [{ label: 'My profile', href: route('profile.edit'), icon: UserRound }];

    return (
        <div className="flex h-full min-h-0 flex-col bg-[#0b3b3d] text-white">
            <div className="flex items-center px-5 py-6 pr-12 lg:pr-5">
                <Link
                    href={route('student.dashboard')}
                    onClick={onNavigate}
                    aria-label="ProctorAide dashboard"
                    className="rounded-lg focus-visible:ring-2 focus-visible:ring-[#8fd6c7] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b3b3d] focus-visible:outline-hidden"
                >
                    <BrandMark linked={false} variant="light" iconClassName="size-10" wordmarkClassName="text-xl" />
                </Link>
            </div>

            <nav aria-label="Student navigation" className="min-h-0 flex-1 space-y-7 overflow-y-auto px-3 py-3">
                <SidebarNavigationGroup title="Learning" items={learningNavigation} currentUrl={currentUrl} onNavigate={onNavigate} />
                <SidebarNavigationGroup title="Account" items={accountNavigation} currentUrl={currentUrl} onNavigate={onNavigate} />
            </nav>

            <div className="border-t border-white/10 p-4">
                <div className="flex items-center gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#f4e9c7] text-sm font-bold text-[#3b4527]">
                        {getInitials(user.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-[13px] font-semibold text-white" title={user.email}>
                            {user.email}
                        </p>
                        <p className="mt-0.5 text-[11px] text-white/45 capitalize">{user.role ?? 'Student'}</p>
                    </div>
                </div>

                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="mt-4 flex h-10 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-white/70 transition-colors hover:bg-white/[0.06] hover:text-white focus-visible:ring-2 focus-visible:ring-[#8fd6c7] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b3b3d] focus-visible:outline-hidden"
                >
                    <LogOut className="size-[18px]" aria-hidden="true" />
                    Sign Out
                </Link>
            </div>
        </div>
    );
}

export default function StudentLayout({ title, children }: { title: string; children: React.ReactNode }) {
    const [mobileNavigationOpen, setMobileNavigationOpen] = useState(false);
    const { url, props } = usePage<SharedData>();
    const user = props.auth.user;

    return (
        <div className="min-h-svh bg-[#f6f8f7] text-[#123c37]">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-[272px] lg:block">
                <SidebarPanel currentUrl={url} />
            </aside>

            <Sheet open={mobileNavigationOpen} onOpenChange={setMobileNavigationOpen}>
                <SheetContent side="left" className="w-[280px] border-0 bg-[#0b3b3d] p-0 text-white sm:max-w-[280px] [&>button]:text-white/60">
                    <SheetTitle className="sr-only">Student navigation</SheetTitle>
                    <SheetDescription className="sr-only">Links to your dashboard and account settings.</SheetDescription>
                    <SidebarPanel currentUrl={url} onNavigate={() => setMobileNavigationOpen(false)} />
                </SheetContent>
            </Sheet>

            <div className="flex min-h-svh flex-col lg:pl-[272px]">
                <header className="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between gap-4 border-b border-white/5 bg-[#0b3b3d] px-4 sm:px-6 lg:h-[88px] lg:px-10">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setMobileNavigationOpen(true)}
                            aria-label="Open navigation"
                            className="flex size-10 shrink-0 items-center justify-center rounded-lg text-white/80 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-[#8fd6c7] focus-visible:outline-hidden lg:hidden"
                        >
                            <Menu className="size-5" aria-hidden="true" />
                        </button>
                        <h1 className="truncate text-lg font-semibold tracking-tight text-white sm:text-xl">{title}</h1>
                    </div>

                    <div className="flex shrink-0 items-center gap-3 sm:gap-5">
                        <span className="rounded-full bg-[#f4e9c7] px-3.5 py-1 text-xs font-semibold text-[#3b4527] capitalize">
                            {user.role ?? 'Student'}
                        </span>
                        <span className="hidden max-w-[240px] truncate text-[11px] font-semibold tracking-[0.18em] text-white/50 uppercase sm:block">
                            {user.college ?? 'ProctorAide'}
                        </span>
                    </div>
                </header>

                <main className="flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-9">
                    <div className="mx-auto w-full max-w-[1400px]">{children}</div>
                </main>
            </div>
        </div>
    );
}
