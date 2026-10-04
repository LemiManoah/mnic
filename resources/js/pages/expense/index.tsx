import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ExpenseApprovalController from '@/actions/App/Http/Controllers/ExpenseApprovalController';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import ExpensePaymentController from '@/actions/App/Http/Controllers/ExpensePaymentController';
import ExpenseVerificationController from '@/actions/App/Http/Controllers/ExpenseVerificationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ListFilters from '@/components/list-filters';
import PaginationLinks from '@/components/pagination-links';
import StatusNote from '@/components/status-note';
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
import { index as expenseIndex } from '@/routes/expense';
import { expenses as exportExpenses } from '@/routes/export';
import type {
    BreadcrumbItem,
    ExpenseRow,
    ExpenseStatus,
    ExternalAccountOption,
    Option,
    Paginated,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: expenseIndex() },
];

const STATUS_VARIANT: Record<
    ExpenseStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    submitted: 'outline',
    approved: 'secondary',
    rejected: 'destructive',
    paid: 'secondary',
    verified: 'default',
};

function RequestExpenseDialog({
    categoryOptions,
}: {
    categoryOptions: Option[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Request expense</Button>
            </DialogTrigger>

            <DialogContent className="min-w-0 p-4 sm:max-w-lg sm:p-6">
                <DialogHeader>
                    <DialogTitle>Request expense</DialogTitle>
                    <DialogDescription>
                        Someone else must approve it, and a third person
                        verifies it once paid.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ExpenseController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="grid min-w-0 gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="reference">Reference</Label>
                                <Input
                                    id="reference"
                                    name="reference"
                                    required
                                    placeholder="EXP-0001"
                                />
                                <InputError message={errors.reference} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="purpose">Purpose</Label>
                                <Input id="purpose" name="purpose" required />
                                <InputError message={errors.purpose} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category">Category</Label>
                                <select
                                    id="category"
                                    name="category"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a category
                                    </option>
                                    {categoryOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="payee">Payee</Label>
                                <Input id="payee" name="payee" required />
                                <InputError message={errors.payee} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (UGX)</Label>
                                <Input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    min={1}
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="incurred_on">Incurred on</Label>
                                <Input
                                    id="incurred_on"
                                    name="incurred_on"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.incurred_on} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="resolution_reference">
                                    Resolution reference
                                </Label>
                                <Input
                                    id="resolution_reference"
                                    name="resolution_reference"
                                />
                                <InputError
                                    message={errors.resolution_reference}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="evidence">Evidence</Label>
                                <Input
                                    id="evidence"
                                    name="evidence"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <InputError message={errors.evidence} />
                            </div>

                            <DialogFooter className="col-span-full mt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Submit
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function PayExpenseDialog({
    expense,
    externalAccounts,
}: {
    expense: ExpenseRow;
    externalAccounts: ExternalAccountOption[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Record payment
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Record expense payment</DialogTitle>
                    <DialogDescription>
                        {expense.reference} — {formatUgx(expense.amount)}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ExpensePaymentController.update.form(expense.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`account-${expense.id}`}>
                                    Paid from
                                </Label>
                                <select
                                    id={`account-${expense.id}`}
                                    name="external_account_id"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select an account
                                    </option>
                                    {externalAccounts.map((account) => (
                                        <option
                                            key={account.id}
                                            value={account.id}
                                        >
                                            {account.name} (
                                            {account.masked_identifier})
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.external_account_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`ref-${expense.id}`}>
                                    Payment reference
                                </Label>
                                <Input
                                    id={`ref-${expense.id}`}
                                    name="payment_reference"
                                    required
                                />
                                <InputError
                                    message={errors.payment_reference}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`paid-${expense.id}`}>
                                    Paid on
                                </Label>
                                <Input
                                    id={`paid-${expense.id}`}
                                    name="paid_on"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.paid_on} />
                            </div>

                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    Record payment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RejectExpenseDialog({ expense }: { expense: ExpenseRow }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Reject
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reject expense</DialogTitle>
                    <DialogDescription>{expense.reference}</DialogDescription>
                </DialogHeader>

                <Form
                    {...ExpenseApprovalController.update.form(expense.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`reason-${expense.id}`}>
                                    Reason
                                </Label>
                                <Input
                                    id={`reason-${expense.id}`}
                                    name="reason"
                                    required
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Reject
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ExpenseIndex({
    expenses,
    canRequest,
    filters,
    statusOptions,
    categoryOptions,
    externalAccounts,
}: {
    expenses: Paginated<ExpenseRow>;
    canRequest: boolean;
    filters: {
        search: string | null;
        status: string | null;
        category: string | null;
    };
    statusOptions: Option[];
    categoryOptions: Option[];
    externalAccounts: ExternalAccountOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Expenses" />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Expenses"
                        description="Requested, approved, paid and verified outflows"
                    />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <a
                                href={
                                    exportExpenses({ query: { format: 'pdf' } })
                                        .url
                                }
                            >
                                PDF
                            </a>
                        </Button>

                        <Button asChild variant="outline">
                            <a href={exportExpenses().url}>CSV</a>
                        </Button>

                        {canRequest && (
                            <RequestExpenseDialog
                                categoryOptions={categoryOptions}
                            />
                        )}
                    </div>
                </div>

                <ListFilters
                    url={expenseIndex().url}
                    search={filters.search}
                    placeholder="Search reference, purpose or payee..."
                    filters={[
                        {
                            name: 'status',
                            label: 'Status',
                            value: filters.status,
                            options: statusOptions,
                        },
                        {
                            name: 'category',
                            label: 'Category',
                            value: filters.category,
                            options: categoryOptions,
                        },
                    ]}
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Reference</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Payee</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Requested by</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {expenses.data.map((expense) => (
                                <TableRow key={expense.id}>
                                    <TableCell className="font-medium">
                                        {expense.reference}
                                    </TableCell>
                                    <TableCell>{expense.purpose}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {expense.payee}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(expense.amount)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {expense.requested_by ?? '—'}
                                    </TableCell>
                                    <TableCell className="max-w-72 align-top whitespace-normal">
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[expense.status]
                                            }
                                        >
                                            {expense.status}
                                        </Badge>
                                        {expense.rejection_reason && (
                                            <StatusNote>
                                                {expense.rejection_reason}
                                            </StatusNote>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {expense.can_approve && (
                                                <>
                                                    <Form
                                                        {...ExpenseApprovalController.store.form(
                                                            expense.id,
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
                                                    <RejectExpenseDialog
                                                        expense={expense}
                                                    />
                                                </>
                                            )}

                                            {expense.can_pay && (
                                                <PayExpenseDialog
                                                    expense={expense}
                                                    externalAccounts={
                                                        externalAccounts
                                                    }
                                                />
                                            )}

                                            {expense.can_verify && (
                                                <Form
                                                    {...ExpenseVerificationController.update.form(
                                                        expense.id,
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
                                                            Verify
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {expenses.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No expenses recorded yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={expenses.links} />
            </AdminLayout>
        </AppLayout>
    );
}
