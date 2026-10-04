import { Form, Head, Link, router } from '@inertiajs/react';
import { IconBellOff } from '@tabler/icons-react';
import NotificationReadController from '@/actions/App/Http/Controllers/NotificationReadController';
import Heading from '@/components/heading';
import PaginationLinks from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index as notificationIndex } from '@/routes/notification';
import type { BreadcrumbItem, NotificationRow, Paginated } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: notificationIndex() },
];

export default function NotificationIndex({
    notifications,
    filters,
    unreadCount,
}: {
    notifications: Paginated<NotificationRow>;
    filters: { unread: boolean };
    unreadCount: number;
}) {
    const toggleUnread = () => {
        router.get(
            notificationIndex().url,
            filters.unread ? {} : { unread: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6 px-4 py-4 lg:px-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Notifications"
                        description={
                            unreadCount > 0
                                ? `${unreadCount} unread`
                                : 'Nothing unread'
                        }
                    />

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={toggleUnread}
                        >
                            {filters.unread ? 'Show all' : 'Unread only'}
                        </Button>

                        {unreadCount > 0 && (
                            <Form
                                {...NotificationReadController.store.form()}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        Mark all read
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </div>

                <div className="divide-y rounded-md border">
                    {notifications.data.map((notification) => (
                        <div
                            key={notification.id}
                            className={`flex flex-wrap items-start justify-between gap-3 p-4 ${
                                notification.read_at === null
                                    ? 'bg-muted/40'
                                    : ''
                            }`}
                        >
                            <div className="min-w-56 flex-1 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {notification.subject}
                                    </span>
                                    {notification.read_at === null && (
                                        <Badge variant="secondary">new</Badge>
                                    )}
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {notification.body}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {notification.created_at}
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                {notification.url && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={notification.url}>
                                            Open
                                        </Link>
                                    </Button>
                                )}

                                {notification.read_at === null && (
                                    <Form
                                        {...NotificationReadController.update.form(
                                            notification.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="sm"
                                                disabled={processing}
                                            >
                                                Mark read
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    ))}

                    {notifications.data.length === 0 && (
                        <div className="flex flex-col items-center gap-2 py-12 text-center text-muted-foreground">
                            <IconBellOff className="size-6" />
                            <p>
                                {filters.unread
                                    ? 'Nothing unread.'
                                    : 'No notifications yet.'}
                            </p>
                        </div>
                    )}
                </div>

                <PaginationLinks links={notifications.links} />
            </div>
        </AppLayout>
    );
}
