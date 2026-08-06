import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ReconciliationController from '@/actions/App/Http/Controllers/ReconciliationController';
import ReconciliationReviewController from '@/actions/App/Http/Controllers/ReconciliationReviewController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { index as reconciliationIndex } from '@/routes/reconciliation';
import type {
    BreadcrumbItem,
    ExternalAccountOption,
    Paginated,
    PeriodOption,
    ReconciliationRow,
    ReconciliationStatus,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reconciliation', href: reconciliationIndex() },
];

const STATUS_VARIANT: Record<
    ReconciliationStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    submitted: 'secondary',
    confirmed: 'default',
    rejected: 'destructive',
    locked: 'default',
};

function StartReconciliationDialog({
    periods,
    externalAccounts,
}: {
    periods: PeriodOption[];
    externalAccounts: ExternalAccountOption[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Start reconciliation</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Start reconciliation</DialogTitle>
                    <DialogDescription>
                        Enter the opening balance and the closing balance shown
                        on the external statement. The expected balance is
                        derived from verified records when you submit.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ReconciliationController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="contribution_period_id">
                                    Period
                                </Label>
                                <select
                                    id="contribution_period_id"
                                    name="contribution_period_id"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a period
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
                                <Label htmlFor="external_account_id">
                                    Account
                                </Label>
                                <select
                                    id="external_account_id"
                                    name="external_account_id"
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="">All accounts</option>
                                    {externalAccounts.map((account) => (
                                        <option
                                            key={account.id}
                                            value={account.id}
                                        >
                                            {account.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.external_account_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="opening_balance">
                                    Opening balance (UGX)
                                </Label>
                                <Input
                                    id="opening_balance"
                                    name="opening_balance"
                                    type="number"
                                    min={0}
                                    required
                                />
                                <InputError message={errors.opening_balance} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="statement_closing_balance">
                                    Statement closing balance (UGX)
                                </Label>
                                <Input
                                    id="statement_closing_balance"
                                    name="statement_closing_balance"
                                    type="number"
                                    min={0}
                                    required
                                />
                                <InputError
                                    message={errors.statement_closing_balance}
                                />
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
                                    Start
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ReconciliationIndex({
    reconciliations,
    canCreate,
    periods,
    externalAccounts,
}: {
    reconciliations: Paginated<ReconciliationRow>;
    canCreate: boolean;
    periods: PeriodOption[];
    externalAccounts: ExternalAccountOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reconciliation" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Reconciliation"
                        description="Match the club's records against the external statement, then close the month"
                    />

                    {canCreate && (
                        <StartReconciliationDialog
                            periods={periods}
                            externalAccounts={externalAccounts}
                        />
                    )}
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Period</TableHead>
                                <TableHead>Account</TableHead>
                                <TableHead>Statement</TableHead>
                                <TableHead>Expected</TableHead>
                                <TableHead>Difference</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {reconciliations.data.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="font-medium">
                                        {row.period}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {row.account ?? 'All'}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(
                                            row.statement_closing_balance,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(
                                            row.expected_closing_balance,
                                        )}
                                    </TableCell>
                                    <TableCell
                                        className={
                                            row.difference === 0
                                                ? 'text-muted-foreground'
                                                : 'font-medium'
                                        }
                                    >
                                        {formatUgx(row.difference)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={STATUS_VARIANT[row.status]}
                                        >
                                            {row.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {row.can_submit && (
                                                <Form
                                                    {...ReconciliationReviewController.store.form(
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
                                                            Submit
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}

                                            {row.can_confirm && (
                                                <Form
                                                    {...ReconciliationReviewController.update.form(
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
                                                            Confirm
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}

                                            {row.can_lock && (
                                                <Form
                                                    {...ReconciliationReviewController.destroy.form(
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
                                                            variant="outline"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Close month
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {reconciliations.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No reconciliations started yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={reconciliations.links} />
            </AdminLayout>
        </AppLayout>
    );
}
