import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import ListFilters from '@/components/list-filters';
import PaginationLinks from '@/components/pagination-links';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import {
    auditRecordName,
    auditRecordType,
    readableAuditLabel,
} from '@/lib/audit';
import { formatClubDateTime } from '@/lib/date';
import { index as auditLogIndex } from '@/routes/audit-log';
import { auditLog as exportAuditLog } from '@/routes/export';
import type { AuditLog, BreadcrumbItem, Option, Paginated } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audit log', href: auditLogIndex() },
];

function RecordDetails({ log }: { log: AuditLog }) {
    return (
        <div className="min-w-0 space-y-1">
            <div className="font-medium break-words">
                {auditRecordName(log)}
            </div>
            <div className="text-xs text-muted-foreground">
                {auditRecordType(log)}
            </div>
            <details className="pt-1 text-xs text-muted-foreground">
                <summary className="cursor-pointer py-1">
                    Technical details
                </summary>
                <dl className="mt-2 space-y-2 break-all">
                    <div>
                        <dt className="font-medium">Record ID</dt>
                        <dd>{log.auditable_id}</dd>
                    </div>
                    <div>
                        <dt className="font-medium">Event code</dt>
                        <dd>{log.event}</dd>
                    </div>
                </dl>
            </details>
        </div>
    );
}

export default function AuditLogIndex({
    auditLogs,
    filters,
    eventOptions,
}: {
    auditLogs: Paginated<AuditLog>;
    filters: { search: string | null; event: string | null };
    eventOptions: Option[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit log" />
            <AdminLayout>
                <div className="space-y-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <Heading
                            variant="small"
                            title="Activity & audit log"
                            description="Who changed what and when. Times are shown in East Africa Time (EAT)."
                        />
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <a
                                    href={
                                        exportAuditLog({
                                            query: { format: 'pdf' },
                                        }).url
                                    }
                                >
                                    PDF (last 500)
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <a href={exportAuditLog().url}>CSV (all)</a>
                            </Button>
                        </div>
                    </div>
                    <ListFilters
                        url={auditLogIndex().url}
                        search={filters.search}
                        placeholder="Search activity, record type or person…"
                        filters={[
                            {
                                name: 'event',
                                label: 'Activity',
                                value: filters.event,
                                options: eventOptions.map((option) => ({
                                    ...option,
                                    label: readableAuditLabel(option.value),
                                })),
                            },
                        ]}
                    />
                    <p className="text-sm text-muted-foreground">
                        “Affected record” is the item that changed, such as a
                        member, payment or action item. The name comes from the
                        saved activity; the original ID is available in
                        Technical details.
                    </p>
                    <div className="space-y-3 md:hidden">
                        {auditLogs.data.map((log) => (
                            <article
                                key={log.id}
                                className="space-y-3 rounded-lg border bg-card p-4"
                            >
                                <div className="space-y-1">
                                    <h2 className="font-semibold">
                                        {readableAuditLabel(log.event)}
                                    </h2>
                                    <time
                                        dateTime={log.created_at}
                                        className="text-xs text-muted-foreground"
                                    >
                                        {formatClubDateTime(log.created_at)}
                                    </time>
                                </div>
                                <p className="text-sm">
                                    By {log.actor_member?.full_name ?? 'System'}
                                </p>
                                <div className="border-t pt-3 text-sm">
                                    <RecordDetails log={log} />
                                </div>
                            </article>
                        ))}
                    </div>
                    <div className="hidden overflow-x-auto rounded-lg border md:block">
                        <table className="w-full table-fixed text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="w-1/4 px-4 py-3 font-medium">
                                        When (EAT)
                                    </th>
                                    <th className="w-1/4 px-4 py-3 font-medium">
                                        Activity
                                    </th>
                                    <th className="w-1/5 px-4 py-3 font-medium">
                                        Performed by
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Affected record
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {auditLogs.data.map((log) => (
                                    <tr key={log.id} className="align-top">
                                        <td className="px-4 py-4 text-muted-foreground">
                                            <time dateTime={log.created_at}>
                                                {formatClubDateTime(
                                                    log.created_at,
                                                )}
                                            </time>
                                        </td>
                                        <td className="px-4 py-4 font-medium break-words">
                                            {readableAuditLabel(log.event)}
                                        </td>
                                        <td className="px-4 py-4 break-words">
                                            {log.actor_member?.full_name ??
                                                'System'}
                                        </td>
                                        <td className="px-4 py-4">
                                            <RecordDetails log={log} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {auditLogs.data.length === 0 && (
                        <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                            No activity matches these filters.
                        </p>
                    )}
                    <PaginationLinks links={auditLogs.links} />
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
