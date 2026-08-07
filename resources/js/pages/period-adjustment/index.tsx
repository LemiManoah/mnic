import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import PeriodAdjustmentController from '@/actions/App/Http/Controllers/PeriodAdjustmentController';
import PeriodAdjustmentReviewController from '@/actions/App/Http/Controllers/PeriodAdjustmentReviewController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ListFilters from '@/components/list-filters';
import PaginationLinks from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { index as periodAdjustmentIndex } from '@/routes/period-adjustment';
import type {
    AdjustmentStatus,
    BreadcrumbItem,
    Option,
    Paginated,
    PeriodAdjustmentRow,
    PeriodOption,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Adjustments', href: periodAdjustmentIndex() },
];

const STATUS_VARIANT: Record<
    AdjustmentStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
};

function RaiseAdjustmentDialog({ periods }: { periods: PeriodOption[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button disabled={periods.length === 0}>
                    Raise an adjustment
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Adjust a closed month</DialogTitle>
                    <DialogDescription>
                        The closed month is not edited. This records a
                        correction beside it, and a second officer has to
                        approve it before it counts.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PeriodAdjustmentController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="contribution_period_id">
                                    Closed month
                                </Label>
                                <select
                                    id="contribution_period_id"
                                    name="contribution_period_id"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a month
                                    </option>
                                    {periods.map((period) => (
                                        <option
                                            key={period.id}
                                            value={period.id}
                                        >
                                            {period.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.contribution_period_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (UGX)</Label>
                                <Input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    required
                                    placeholder="e.g. -60000"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Negative if the closed month was overstated,
                                    positive if it was understated.
                                </p>
                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason</Label>
                                <Input id="reason" name="reason" required />
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
                                    Raise
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function PeriodAdjustmentIndex({
    adjustments,
    filters,
    statusOptions,
    canCreate,
    closedPeriods,
}: {
    adjustments: Paginated<PeriodAdjustmentRow>;
    filters: { status: string | null };
    statusOptions: Option[];
    canCreate: boolean;
    closedPeriods: PeriodOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Adjustments" />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Adjustments to closed months"
                        description="A closed month is never edited. Corrections are recorded beside it and need two officers."
                    />

                    {canCreate && (
                        <RaiseAdjustmentDialog periods={closedPeriods} />
                    )}
                </div>

                <ListFilters
                    url={periodAdjustmentIndex().url}
                    filters={[
                        {
                            name: 'status',
                            label: 'Status',
                            value: filters.status,
                            options: statusOptions,
                        },
                    ]}
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Month</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Raised by</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {adjustments.data.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="font-medium">
                                        {row.period}
                                    </TableCell>
                                    <TableCell
                                        className={
                                            row.amount < 0
                                                ? 'text-destructive'
                                                : ''
                                        }
                                    >
                                        {formatUgx(row.amount)}
                                    </TableCell>
                                    <TableCell className="max-w-72 text-muted-foreground">
                                        {row.reason}
                                        {row.rejection_reason && (
                                            <span className="block text-xs text-destructive">
                                                Rejected: {row.rejection_reason}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {row.requested_by ?? '-'}
                                        {row.reviewed_by && (
                                            <span className="block text-xs">
                                                Reviewed by {row.reviewed_by}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={STATUS_VARIANT[row.status]}
                                        >
                                            {row.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {row.can_review && (
                                            <div className="flex justify-end gap-2">
                                                <Form
                                                    {...PeriodAdjustmentReviewController.update.form(
                                                        row.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Approve
                                                        </Button>
                                                    )}
                                                </Form>

                                                <Form
                                                    {...PeriodAdjustmentReviewController.destroy.form(
                                                        row.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    className="flex items-end gap-2"
                                                >
                                                    {({
                                                        processing,
                                                        errors,
                                                    }) => (
                                                        <>
                                                            <div className="grid gap-1">
                                                                <Input
                                                                    name="reason"
                                                                    required
                                                                    placeholder="Why not?"
                                                                    aria-label="Reason for rejecting"
                                                                    className="w-44"
                                                                />
                                                                <InputError
                                                                    message={
                                                                        errors.reason
                                                                    }
                                                                />
                                                            </div>
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Reject
                                                            </Button>
                                                        </>
                                                    )}
                                                </Form>
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}

                            {adjustments.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No adjustments raised.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={adjustments.links} />
            </AdminLayout>
        </AppLayout>
    );
}
