import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import MemberObligationAdjustmentController from '@/actions/App/Http/Controllers/MemberObligationAdjustmentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
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

const ADJUSTABLE_STATUSES = ['waived', 'cancelled'] as const;

function AdjustObligationDialog({
    obligation,
}: {
    obligation: PeriodObligation;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Adjust
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Adjust obligation</DialogTitle>
                    <DialogDescription>
                        {obligation.member_name} - this records a waive or
                        cancellation reason in the audit log.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...MemberObligationAdjustmentController.update.form(
                        obligation.id,
                    )}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`adjust-status-${obligation.id}`}
                                >
                                    Status
                                </Label>
                                <select
                                    id={`adjust-status-${obligation.id}`}
                                    name="status"
                                    required
                                    defaultValue="waived"
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    {ADJUSTABLE_STATUSES.map((status) => (
                                        <option key={status} value={status}>
                                            {status}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`adjust-reason-${obligation.id}`}
                                >
                                    Reason
                                </Label>
                                <Textarea
                                    id={`adjust-reason-${obligation.id}`}
                                    name="reason"
                                    required
                                    rows={4}
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Save adjustment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

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

    const expected = obligations.reduce(
        (sum, o) =>
            sum +
            (o.status === 'waived' || o.status === 'cancelled'
                ? 0
                : o.amount),
        0,
    );
    const collected = obligations.reduce((sum, o) => sum + o.amount_paid, 0);
    const outstanding = obligations.reduce((sum, o) => sum + o.outstanding, 0);

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
                                <TableHead />
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
                                        {obligation.adjustment_reason && (
                                            <span className="block text-xs text-muted-foreground">
                                                {obligation.adjustment_reason}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {obligation.can_adjust &&
                                            obligation.amount_paid === 0 && (
                                                <AdjustObligationDialog
                                                    obligation={obligation}
                                                />
                                            )}
                                    </TableCell>
                                </TableRow>
                            ))}

                            {obligations.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
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
