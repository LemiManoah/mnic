import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
import {
    index as periodIndex,
    show as showPeriod,
} from '@/routes/contribution-period';
import type {
    BreadcrumbItem,
    ContributionPeriod,
    ObligationStatus,
    PeriodObligation,
} from '@/types';

const STATUS_VARIANT: Record<
    ObligationStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    unpaid: 'destructive',
    partially_paid: 'secondary',
    paid: 'default',
    waived: 'outline',
    cancelled: 'outline',
};

export default function ContributionPeriodShow({
    period,
    label,
    obligations,
}: {
    period: ContributionPeriod;
    label: string;
    obligations: PeriodObligation[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Contribution periods',
            href: periodIndex(),
        },
        {
            title: label,
            href: showPeriod(period.id),
        },
    ];

    const expected = obligations.reduce((sum, o) => sum + o.amount, 0);
    const collected = obligations.reduce((sum, o) => sum + o.amount_paid, 0);
    const outstanding = expected - collected;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Period ${label}`} />

            <AdminLayout>
                <Heading
                    variant="small"
                    title={`Contribution period ${label}`}
                    description={`Due ${period.due_date.slice(0, 10)} · grace ends ${period.grace_ends_on.slice(0, 10)} · ${formatUgx(period.amount)} per member`}
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">
                                Expected
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {formatUgx(expected)}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">
                                Collected
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {formatUgx(collected)}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">
                                Outstanding
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {formatUgx(outstanding)}
                        </CardContent>
                    </Card>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member #</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Expected</TableHead>
                                <TableHead>Paid</TableHead>
                                <TableHead>Outstanding</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {obligations.map((obligation) => (
                                <TableRow key={obligation.id}>
                                    <TableCell>
                                        {obligation.member_number}
                                    </TableCell>
                                    <TableCell>
                                        {obligation.member_name}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(obligation.amount)}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(obligation.amount_paid)}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(obligation.outstanding)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[
                                                    obligation.status
                                                ]
                                            }
                                        >
                                            {obligation.status}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {obligations.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No obligations in this period.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
