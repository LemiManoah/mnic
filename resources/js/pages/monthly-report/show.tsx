import { Head } from '@inertiajs/react';
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
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { formatUgx } from '@/lib/money';
import { monthlyReportPdf } from '@/routes/export';
import { index as reportIndex } from '@/routes/monthly-report';
import type { BreadcrumbItem, ContributionPeriodStatus } from '@/types';

type ReportPeriod = {
    id: string;
    label: string;
    due_date: string;
    grace_ends_on: string;
    is_overdue: boolean;
    status: ContributionPeriodStatus;
};

type Contributions = {
    expected: number;
    collected: number;
    outstanding: number;
    members_in_arrears: number;
};

type ReportArrear = {
    member_id: string;
    member_name: string;
    member_number: string | null;
    amount: number;
    amount_paid: number;
    outstanding: number;
};

type Cash = {
    inflows: number;
    outflows: number;
};

type ReconciliationSummary = {
    status: string;
    is_confirmed: boolean;
    opening_balance: number;
    expected_closing_balance: number;
    statement_closing_balance: number;
    difference: number;
};

type ReportPayment = {
    id: string;
    member_name: string;
    reference: string;
    amount: number;
    paid_on: string;
};

type ReportExpense = {
    id: string;
    reference: string;
    purpose: string;
    payee: string;
    amount: number;
    status: string;
};

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm text-muted-foreground">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
                {value}
            </CardContent>
        </Card>
    );
}

type ReportAdjustment = {
    id: string;
    amount: number;
    reason: string;
    requested_by: string | null;
};

export default function MonthlyReportShow({
    period,
    contributions,
    arrears,
    cash,
    reconciliation,
    adjustments,
    payments,
    expenses,
}: {
    period: ReportPeriod;
    contributions: Contributions;
    arrears: ReportArrear[];
    cash: Cash;
    reconciliation: ReconciliationSummary | null;
    adjustments: ReportAdjustment[];
    payments: ReportPayment[];
    expenses: ReportExpense[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Monthly reports', href: reportIndex() },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Report ${period.label}`} />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title={`Monthly report ${period.label}`}
                        description={`Contributions due ${period.due_date}`}
                    />
                    <div className="flex flex-wrap items-center gap-3">
                        {reconciliation?.is_confirmed ? (
                            <Badge>Reconciliation confirmed</Badge>
                        ) : (
                            <Badge variant="destructive">
                                Not reconciled — figures unconfirmed
                            </Badge>
                        )}

                        <Button asChild variant="outline">
                            <a href={monthlyReportPdf(period.id).url}>
                                Download PDF
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat
                        label="Expected"
                        value={formatUgx(contributions.expected)}
                    />
                    <Stat
                        label="Collected"
                        value={formatUgx(contributions.collected)}
                    />
                    <Stat
                        label="Outstanding"
                        value={formatUgx(contributions.outstanding)}
                    />
                    <Stat
                        label={period.is_overdue ? 'Members in arrears' : 'Members with a balance'}
                        value={String(contributions.members_in_arrears)}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{period.is_overdue ? 'Members in arrears' : 'Outstanding contributions'}</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Remaining contributions for {period.label}, including partial payments.
                            {' '}Grace ends on {period.grace_ends_on}. Balances reflect verified payments to date.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="divide-y sm:hidden">
                            {arrears.map((member) => (
                                <div key={member.member_id} className="space-y-2 py-3">
                                    <div className="font-medium">{member.member_name}</div>
                                    <div className="text-xs text-muted-foreground">{member.member_number}</div>
                                    <dl className="grid grid-cols-2 gap-2 text-sm">
                                        <div><dt className="text-muted-foreground">Paid</dt><dd>{formatUgx(member.amount_paid)}</dd></div>
                                        <div><dt className="text-muted-foreground">Outstanding</dt><dd className="font-semibold">{formatUgx(member.outstanding)}</dd></div>
                                    </dl>
                                </div>
                            ))}
                        </div>
                        <div className="hidden sm:block">
                            <Table>
                                <TableHeader><TableRow>
                                    <TableHead>Member</TableHead>
                                    <TableHead className="text-right">Expected</TableHead>
                                    <TableHead className="text-right">Paid</TableHead>
                                    <TableHead className="text-right">Outstanding</TableHead>
                                </TableRow></TableHeader>
                                <TableBody>
                                    {arrears.map((member) => (
                                        <TableRow key={member.member_id}>
                                            <TableCell><div className="font-medium">{member.member_name}</div><div className="text-xs text-muted-foreground">{member.member_number}</div></TableCell>
                                            <TableCell className="text-right">{formatUgx(member.amount)}</TableCell>
                                            <TableCell className="text-right">{formatUgx(member.amount_paid)}</TableCell>
                                            <TableCell className="text-right font-semibold">{formatUgx(member.outstanding)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        {arrears.length === 0 && <p className="py-4 text-sm text-muted-foreground">No outstanding contributions for this period.</p>}
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Stat
                        label="Verified inflows"
                        value={formatUgx(cash.inflows)}
                    />
                    <Stat
                        label="Settled outflows"
                        value={formatUgx(cash.outflows)}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Reconciliation</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm">
                        {reconciliation ? (
                            <dl className="grid gap-2 sm:grid-cols-2">
                                <div>
                                    Opening balance:{' '}
                                    {formatUgx(reconciliation.opening_balance)}
                                </div>
                                <div>
                                    Expected closing:{' '}
                                    {formatUgx(
                                        reconciliation.expected_closing_balance,
                                    )}
                                </div>
                                <div>
                                    Statement closing:{' '}
                                    {formatUgx(
                                        reconciliation.statement_closing_balance,
                                    )}
                                </div>
                                <div>
                                    Difference:{' '}
                                    {formatUgx(reconciliation.difference)}
                                </div>
                            </dl>
                        ) : (
                            <span className="text-muted-foreground">
                                No reconciliation has been started for this
                                period.
                            </span>
                        )}
                    </CardContent>
                </Card>

                {adjustments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Corrections after this month closed
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <p className="text-muted-foreground">
                                These corrections were approved after the period
                                closed. Contribution balances above remain live
                                and can change when later payments are verified.
                            </p>

                            {adjustments.map((adjustment) => (
                                <div
                                    key={adjustment.id}
                                    className="flex flex-wrap items-baseline justify-between gap-2 rounded-md border p-3"
                                >
                                    <div>
                                        <div>{adjustment.reason}</div>
                                        <div className="text-xs text-muted-foreground">
                                            Raised by{' '}
                                            {adjustment.requested_by ??
                                                'an officer'}
                                        </div>
                                    </div>
                                    <span
                                        className={
                                            adjustment.amount < 0
                                                ? 'font-medium text-destructive'
                                                : 'font-medium'
                                        }
                                    >
                                        {formatUgx(adjustment.amount)}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Verified payments received this month</CardTitle>
                        <p className="text-sm text-muted-foreground">Shown by payment date. A payment received this month may settle an earlier contribution period.</p>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Member</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Paid on</TableHead>
                                        <TableHead>Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell>
                                                {payment.member_name}
                                            </TableCell>
                                            <TableCell>
                                                {payment.reference}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {payment.paid_on}
                                            </TableCell>
                                            <TableCell>
                                                {formatUgx(payment.amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))}

                                    {payments.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                No verified payments received this
                                                month.
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
                        <CardTitle>Expenses</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Purpose</TableHead>
                                        <TableHead>Payee</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {expenses.map((expense) => (
                                        <TableRow key={expense.id}>
                                            <TableCell>
                                                {expense.reference}
                                            </TableCell>
                                            <TableCell>
                                                {expense.purpose}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {expense.payee}
                                            </TableCell>
                                            <TableCell>
                                                {formatUgx(expense.amount)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {expense.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}

                                    {expenses.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                No expenses paid this period.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </AdminLayout>
        </AppLayout>
    );
}
