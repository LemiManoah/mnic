import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';
import { index as notificationIndex } from '@/routes/notification';
import { edit as editProfile } from '@/routes/user-profile';
import { Link, usePage } from '@inertiajs/react';
import { Bell, Moon, Sun, UserRound } from 'lucide-react';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex items-center gap-1">
                <Button asChild variant="ghost" size="icon" className="relative" aria-label="Notifications">
                    <Link href={notificationIndex()}>
                        <Bell />
                        {auth.unread_notifications > 0 && (
                            <span className="absolute right-1 top-1 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] leading-none text-destructive-foreground">
                                {auth.unread_notifications > 9 ? '9+' : auth.unread_notifications}
                            </span>
                        )}
                    </Link>
                </Button>
                <Button asChild variant="ghost" size="icon" aria-label="Profile management">
                    <Link href={editProfile()}>
                        <UserRound />
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={`Switch to ${resolvedAppearance === 'dark' ? 'light' : 'dark'} mode`}
                    onClick={() => updateAppearance(resolvedAppearance === 'dark' ? 'light' : 'dark')}
                >
                    {resolvedAppearance === 'dark' ? <Sun /> : <Moon />}
                </Button>
            </div>
        </header>
    );
}
