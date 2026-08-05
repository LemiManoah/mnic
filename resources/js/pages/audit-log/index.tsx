import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import PaginationLinks from '@/components/pagination-links';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as auditLogIndex } from '@/routes/audit-log';
import type { AuditLog, BreadcrumbItem, Paginated } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Log',
        href: auditLogIndex(),
    },
];

export default function AuditLogIndex({
    auditLogs,
}: {
    auditLogs: Paginated<AuditLog>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Log" />

            <AdminLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Audit log"
                        description="Append-only record of sensitive activity"
                    />

                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        When
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Event
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Actor
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Record
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {auditLogs.data.map((log) => (
                                    <tr key={log.id} className="border-t">
                                        <td className="px-4 py-2 whitespace-nowrap text-muted-foreground">
                                            {log.created_at}
                                        </td>
                                        <td className="px-4 py-2">
                                            {log.event}
                                        </td>
                                        <td className="px-4 py-2">
                                            {log.actor_member?.full_name ??
                                                'System'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {log.auditable_type
                                                .split('\\')
                                                .pop()}{' '}
                                            #{log.auditable_id.slice(0, 8)}
                                        </td>
                                    </tr>
                                ))}

                                {auditLogs.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            No activity recorded yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <PaginationLinks links={auditLogs.links} />
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
