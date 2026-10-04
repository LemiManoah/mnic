import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { readableAuditLabel } from '@/lib/audit';
import { formatClubDateTime } from '@/lib/date';
import { formatUgx } from '@/lib/money';
import { memberStatement, memberStatementPdf } from '@/routes/export';
import {
    edit as editMember,
    index as memberIndex,
    show as showMember,
} from '@/routes/member';
import type {
    AuditLog,
    BreadcrumbItem,
    LedgerObligation,
    LedgerPayment,
    Member,
    MembershipStatusHistory,
} from '@/types';

const STATUS_VARIANT: Record<
    Member['status'],
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    prospective: 'outline',
    active: 'default',
    suspended: 'secondary',
    exited: 'secondary',
    removed: 'destructive',
};

function DetailRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="text-sm sm:col-span-2">{value}</dd>
        </div>
    );
}

export default function MemberShow({
    member,
    referredByName,
    position,
    currentRole,
    email,
    canUpdate,
    canViewActivity,
    statusHistories,
    obligations,
    payments,
    auditLogs,
}: {
    member: Member;
    referredByName: string | null;
    position: string | null;
    currentRole?: string;
    email: string | null;
    canUpdate: boolean;
    canViewActivity: boolean;
    statusHistories: MembershipStatusHistory[];
    obligations: LedgerObligation[];
    payments: LedgerPayment[];
    auditLogs: AuditLog[];
}) {
    const totalOutstanding = obligations.reduce(
        (sum, obligation) => sum + obligation.outstanding,
        0,
    );
    const totalAdvance = payments.reduce(
        (sum, payment) => sum + payment.unapplied_amount,
        0,
    );
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Members',
            href: memberIndex(),
        },
        {
            title: member.full_name,
            href: showMember(member.id),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={member.full_name} />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex min-w-0 flex-wrap items-center gap-3">
                        <Heading
                            variant="small"
                            title={member.full_name}
                            description={`Member #${member.member_number}`}
                        />
                        <Badge variant={STATUS_VARIANT[member.status]}>
                            {member.status}
                        </Badge>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* A plain anchor, not Inertia's Link: this returns a
                            file download rather than a page. */}
                        <Button asChild variant="outline">
                            <a href={memberStatementPdf(member.id).url}>
                                Statement (PDF)
                            </a>
                        </Button>

                        <Button asChild variant="outline">
                            <a href={memberStatement(member.id).url}>CSV</a>
                        </Button>

                        {canUpdate && (
                            <Button asChild>
                                <Link href={editMember(member.id)}>
                                    Edit member
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <Tabs defaultValue="overview" className="min-w-0">
                    <div className="w-full min-w-0 overflow-x-auto overscroll-x-contain pb-2">
                        <TabsList
                            aria-label="Member profile sections"
                            className="min-w-max group-data-[orientation=horizontal]/tabs:h-11"
                        >
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="contributions">
                                Contributions
                            </TabsTrigger>
                            <TabsTrigger value="status">
                                Status history
                            </TabsTrigger>
                            <TabsTrigger value="access">Access</TabsTrigger>
                            {canViewActivity && (
                                <TabsTrigger value="activity">
                                    Activity
                                </TabsTrigger>
                            )}
                        </TabsList>
                    </div>
                    <p className="text-xs text-muted-foreground sm:hidden">
                        Swipe the tabs to see all sections.
                    </p>

                    <TabsContent value="overview">
                        <Card>
                            <CardHeader>
                                <CardTitle>Member details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="divide-y">
                                    <DetailRow
                                        label="Full name"
                                        value={member.full_name}
                                    />
                                    <DetailRow
                                        label="Member number"
                                        value={member.member_number}
                                    />
                                    <DetailRow
                                        label="Status"
                                        value={
                                            <Badge
                                                variant={
                                                    STATUS_VARIANT[
                                                        member.status
                                                    ]
                                                }
                                            >
                                                {member.status}
                                            </Badge>
                                        }
                                    />
                                    <DetailRow
                                        label="Club position"
                                        value={
                                            position ?? (
                                                <span className="text-muted-foreground">
                                                    No elected office
                                                </span>
                                            )
                                        }
                                    />
                                    <DetailRow
                                        label="Phone"
                                        value={member.phone}
                                    />
                                    <DetailRow
                                        label="Pioneer member"
                                        value={member.is_pioneer ? 'Yes' : 'No'}
                                    />
                                    <DetailRow
                                        label="Joined"
                                        value={member.joined_at.slice(0, 10)}
                                    />
                                    <DetailRow
                                        label="Referred by"
                                        value={
                                            referredByName ?? (
                                                <span className="text-muted-foreground">
                                                    Not recorded
                                                </span>
                                            )
                                        }
                                    />
                                </dl>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="contributions" className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm text-muted-foreground">
                                        Outstanding
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold">
                                    {formatUgx(totalOutstanding)}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm text-muted-foreground">
                                        Unapplied advance
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-semibold">
                                    {formatUgx(totalAdvance)}
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>Obligations</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Period</TableHead>
                                                <TableHead>Expected</TableHead>
                                                <TableHead>Paid</TableHead>
                                                <TableHead>
                                                    Outstanding
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {obligations.map((obligation) => (
                                                <TableRow key={obligation.id}>
                                                    <TableCell>
                                                        {obligation.period}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatUgx(
                                                            obligation.amount,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatUgx(
                                                            obligation.amount_paid,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatUgx(
                                                            obligation.outstanding,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {obligation.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}

                                            {obligations.length === 0 && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={5}
                                                        className="py-6 text-center text-muted-foreground"
                                                    >
                                                        No obligations yet.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payments</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Reference</TableHead>
                                                <TableHead>Paid on</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {payments.map((payment) => (
                                                <TableRow key={payment.id}>
                                                    <TableCell>
                                                        {payment.reference}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {payment.paid_on}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatUgx(
                                                            payment.amount,
                                                        )}
                                                        <span className="block text-xs text-muted-foreground">
                                                            Contributions /
                                                            advance:{' '}
                                                            {formatUgx(
                                                                payment.contribution_amount,
                                                            )}
                                                        </span>
                                                        <span className="block text-xs text-muted-foreground">
                                                            Withdrawal fees:{' '}
                                                            {formatUgx(
                                                                payment.withdrawal_fee_amount,
                                                            )}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {payment.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}

                                            {payments.length === 0 && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={4}
                                                        className="py-6 text-center text-muted-foreground"
                                                    >
                                                        No payments recorded.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="status">
                        <Card>
                            <CardHeader>
                                <CardTitle>Status history</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Effective</TableHead>
                                                <TableHead>From</TableHead>
                                                <TableHead>To</TableHead>
                                                <TableHead>Reason</TableHead>
                                                <TableHead>
                                                    Resolution
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {statusHistories.map((history) => (
                                                <TableRow key={history.id}>
                                                    <TableCell className="whitespace-nowrap text-muted-foreground">
                                                        {history.effective_date.slice(
                                                            0,
                                                            10,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {history.from_status ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {history.to_status}
                                                    </TableCell>
                                                    <TableCell>
                                                        {history.reason}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {history.resolution_reference ??
                                                            '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}

                                            {statusHistories.length === 0 && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={5}
                                                        className="py-6 text-center text-muted-foreground"
                                                    >
                                                        No status changes
                                                        recorded.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="access">
                        <Card>
                            <CardHeader>
                                <CardTitle>Access</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="divide-y">
                                    <DetailRow
                                        label="Login account"
                                        value={
                                            email ?? (
                                                <span className="text-muted-foreground">
                                                    No linked user account
                                                </span>
                                            )
                                        }
                                    />
                                    <DetailRow
                                        label="Club role"
                                        value={
                                            currentRole ? (
                                                <Badge variant="secondary">
                                                    {currentRole}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    No role assigned
                                                </span>
                                            )
                                        }
                                    />
                                </dl>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {canViewActivity && (
                        <TabsContent value="activity">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Recent activity</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>When</TableHead>
                                                    <TableHead>Event</TableHead>
                                                    <TableHead>Actor</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {auditLogs.map((log) => (
                                                    <TableRow key={log.id}>
                                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                                            {formatClubDateTime(
                                                                log.created_at,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {readableAuditLabel(
                                                                log.event,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {log.actor_member
                                                                ?.full_name ??
                                                                'System'}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}

                                                {auditLogs.length === 0 && (
                                                    <TableRow>
                                                        <TableCell
                                                            colSpan={3}
                                                            className="py-6 text-center text-muted-foreground"
                                                        >
                                                            No activity recorded
                                                            yet.
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}
                </Tabs>
            </AdminLayout>
        </AppLayout>
    );
}
