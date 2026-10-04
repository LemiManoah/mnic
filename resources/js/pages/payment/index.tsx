import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import PaymentRejectionController from '@/actions/App/Http/Controllers/PaymentRejectionController';
import PaymentReversalController from '@/actions/App/Http/Controllers/PaymentReversalController';
import PaymentVerificationController from '@/actions/App/Http/Controllers/PaymentVerificationController';
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
import { payments as exportPayments } from '@/routes/export';
import { index as paymentIndex } from '@/routes/payment';
import { show as showEvidence } from '@/routes/payment-evidence';
import { show as paymentReceipt } from '@/routes/payment-receipt';
import type {
    BreadcrumbItem,
    MemberOption,
    Option,
    Paginated,
    PaymentRow,
    PaymentStatus,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: paymentIndex(),
    },
];

const STATUS_VARIANT: Record<
    PaymentStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    submitted: 'outline',
    verified: 'default',
    rejected: 'destructive',
    reversal_pending: 'outline',
    reversed: 'secondary',
};

type PaymentMemberOption = MemberOption & {
    contribution_due_amount: number;
    contribution_due_period: string | null;
    periods: { id: string; label: string; outstanding: number }[];
};

function RecordPaymentDialog({
    members,
    methodOptions,
}: {
    members: PaymentMemberOption[];
    methodOptions: Option[];
}) {
    const [open, setOpen] = useState(false);
    const [memberId, setMemberId] = useState('');
    const [periodId, setPeriodId] = useState('');
    const [amount, setAmount] = useState('');
    const [allocation, setAllocation] = useState('');
    const [splitFee, setSplitFee] = useState('');
    const member = members.find((item) => item.id === memberId);
    const period = member?.periods.find((item) => item.id === periodId);
    const due = period?.outstanding ?? 0;
    const received = Number(amount) || 0;
    const excess = due > 0 ? Math.max(0, received - due) : 0;
    const fee =
        excess === 0
            ? 0
            : allocation === 'fees'
              ? excess
              : allocation === 'split'
                ? Number(splitFee) || 0
                : 0;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Record payment</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Record payment</DialogTitle>
                    <DialogDescription>
                        The payment is submitted for verification. Someone other
                        than you must verify it before it settles obligations.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PaymentController.store.form()}
                    options={{ preserveScroll: true }}
                    transform={(data) => ({
                        ...data,
                        contribution_due_amount: due,
                        contribution_period_id: periodId || null,
                        excess_allocation: excess > 0 ? allocation : null,
                        withdrawal_fee_amount: fee,
                    })}
                    onSuccess={() => {
                        setOpen(false);
                        setMemberId('');
                        setPeriodId('');
                        setAmount('');
                        setAllocation('');
                        setSplitFee('');
                    }}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="member_id">Member</Label>
                                <select
                                    id="member_id"
                                    name="member_id"
                                    required
                                    value={memberId}
                                    onChange={(event) => {
                                        setMemberId(event.target.value);
                                        setPeriodId('');
                                        setAllocation('');
                                        setSplitFee('');
                                    }}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a member
                                    </option>
                                    {members.map((member) => (
                                        <option
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.member_number} —{' '}
                                            {member.full_name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.member_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contribution_period_id">
                                    Contribution period
                                </Label>
                                <select
                                    id="contribution_period_id"
                                    name="contribution_period_id"
                                    value={periodId}
                                    onChange={(event) => {
                                        setPeriodId(event.target.value);
                                        setAllocation('');
                                        setSplitFee('');
                                    }}
                                    disabled={!member}
                                    required
                                    className="h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="" disabled>
                                        Select an open period
                                    </option>
                                    {member?.periods.map((option) => (
                                        <option
                                            key={option.id}
                                            value={option.id}
                                        >
                                            {option.label} -{' '}
                                            {formatUgx(option.outstanding)}{' '}
                                            outstanding
                                        </option>
                                    ))}
                                </select>
                                {member && member.periods.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        This member has no eligible open
                                        periods.
                                    </p>
                                )}
                                <InputError
                                    message={errors.contribution_period_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">
                                    Total received (UGX)
                                </Label>
                                <Input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    min={1}
                                    step={1}
                                    value={amount}
                                    onChange={(event) => {
                                        setAmount(event.target.value);
                                        setAllocation('');
                                        setSplitFee('');
                                    }}
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>

                            {period && (
                                <div className="space-y-3 rounded-lg border bg-muted/30 p-3 text-sm">
                                    <p>
                                        {due > 0
                                            ? `${period.label}: ${formatUgx(due)} outstanding.`
                                            : 'This period is fully paid. This payment will be advance credit for later open months.'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Based on verified payments. Pending
                                        payments do not reduce this balance. The
                                        selected month is paid first, then later
                                        open months. Earlier arrears are not
                                        changed.
                                    </p>
                                    <InputError
                                        message={errors.contribution_due_amount}
                                    />
                                    {excess > 0 ? (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="excess_allocation">
                                                    Allocate the extra{' '}
                                                    {formatUgx(excess)}
                                                </Label>
                                                <select
                                                    id="excess_allocation"
                                                    name="excess_allocation"
                                                    value={allocation}
                                                    onChange={(event) =>
                                                        setAllocation(
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                    className="h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm"
                                                >
                                                    <option value="" disabled>
                                                        Choose allocation
                                                    </option>
                                                    <option value="advance">
                                                        Contribution advance
                                                    </option>
                                                    <option value="fees">
                                                        Withdrawal fee
                                                        contribution
                                                    </option>
                                                    <option
                                                        value="split"
                                                        disabled={excess < 2}
                                                    >
                                                        Split between advance
                                                        and fees
                                                    </option>
                                                </select>
                                                <InputError
                                                    message={
                                                        errors.excess_allocation
                                                    }
                                                />
                                            </div>
                                            {allocation === 'split' && (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="withdrawal_fee_amount">
                                                        Withdrawal fee portion
                                                        (UGX)
                                                    </Label>
                                                    <Input
                                                        id="withdrawal_fee_amount"
                                                        name="withdrawal_fee_amount"
                                                        type="number"
                                                        min={1}
                                                        max={excess - 1}
                                                        step={1}
                                                        value={splitFee}
                                                        onChange={(event) =>
                                                            setSplitFee(
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        required
                                                    />
                                                </div>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                Advance settles later open
                                                months; any remainder is held as
                                                credit. Fee contributions are
                                                money collected for charges, not
                                                charges already spent.
                                            </p>
                                        </>
                                    ) : received > 0 && due > 0 ? (
                                        <p>
                                            The full payment goes to
                                            contributions. No withdrawal fee is
                                            deducted.
                                        </p>
                                    ) : null}
                                    <InputError
                                        message={errors.withdrawal_fee_amount}
                                    />
                                    {received > 0 &&
                                        (excess === 0 || allocation !== '') && (
                                            <dl className="grid grid-cols-2 gap-2 border-t pt-3">
                                                <dt>Contributions / advance</dt>
                                                <dd className="text-right font-medium">
                                                    {formatUgx(received - fee)}
                                                </dd>
                                                <dt>
                                                    Withdrawal fees collected
                                                </dt>
                                                <dd className="text-right font-medium">
                                                    {formatUgx(fee)}
                                                </dd>
                                                <dt>Total received</dt>
                                                <dd className="text-right font-semibold">
                                                    {formatUgx(received)}
                                                </dd>
                                            </dl>
                                        )}
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="paid_on">Paid on</Label>
                                <Input
                                    id="paid_on"
                                    name="paid_on"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.paid_on} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="method">Method</Label>
                                <select
                                    id="method"
                                    name="method"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a method
                                    </option>
                                    {methodOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.method} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reference">
                                    Transaction reference
                                </Label>
                                <Input
                                    id="reference"
                                    name="reference"
                                    required
                                    placeholder="MM-12345678"
                                />
                                <InputError message={errors.reference} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="evidence">
                                    Evidence (PDF or image)
                                </Label>
                                <Input
                                    id="evidence"
                                    name="evidence"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <InputError message={errors.evidence} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !period}
                                >
                                    Submit payment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RejectPaymentDialog({ payment }: { payment: PaymentRow }) {
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
                    <DialogTitle>Reject payment</DialogTitle>
                    <DialogDescription>
                        Reference {payment.reference} — the reason is recorded
                        on the payment and in the audit log.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PaymentRejectionController.update.form(payment.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`reason-${payment.id}`}>
                                    Reason
                                </Label>
                                <Input
                                    id={`reason-${payment.id}`}
                                    name="reason"
                                    required
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
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Reject payment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RequestReversalDialog({ payment }: { payment: PaymentRow }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Request reversal
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Request a reversal</DialogTitle>
                    <DialogDescription>
                        Reference {payment.reference} — nothing is unwound yet.
                        A second officer has to approve this before the
                        allocations are removed.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PaymentReversalController.store.form(payment.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`reverse-reason-${payment.id}`}>
                                    Reason
                                </Label>
                                <Input
                                    id={`reverse-reason-${payment.id}`}
                                    name="reason"
                                    required
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
                                    Request reversal
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function PaymentIndex({
    payments,
    filters,
    members,
    statusOptions,
    methodOptions,
}: {
    payments: Paginated<PaymentRow>;
    filters: { search: string | null; status: string | null };
    members: PaymentMemberOption[];
    statusOptions: Option[];
    methodOptions: Option[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payments" />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Payments"
                        description="Receipts with contributions, advances, and withdrawal fee collections"
                    />

                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild variant="outline">
                            <a
                                href={
                                    exportPayments({ query: { format: 'pdf' } })
                                        .url
                                }
                            >
                                Verified (PDF)
                            </a>
                        </Button>

                        <Button asChild variant="outline">
                            <a href={exportPayments().url}>CSV</a>
                        </Button>

                        <RecordPaymentDialog
                            members={members}
                            methodOptions={methodOptions}
                        />
                    </div>
                </div>

                <ListFilters
                    url={paymentIndex().url}
                    search={filters.search}
                    placeholder="Search reference, name or member number..."
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
                                <TableHead>Reference</TableHead>
                                <TableHead>Member</TableHead>
                                <TableHead>
                                    Total received / allocation
                                </TableHead>
                                <TableHead>Paid on</TableHead>
                                <TableHead>Recorded by</TableHead>
                                <TableHead>Evidence</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payments.data.map((payment) => (
                                <TableRow key={payment.id}>
                                    <TableCell className="font-medium">
                                        {payment.reference}
                                    </TableCell>
                                    <TableCell>{payment.member_name}</TableCell>
                                    <TableCell>
                                        {formatUgx(payment.amount)}
                                        {payment.selected_period && (
                                            <span className="block text-xs text-muted-foreground">
                                                Selected period:{' '}
                                                {payment.selected_period}
                                            </span>
                                        )}
                                        <span className="block text-xs text-muted-foreground">
                                            {formatUgx(
                                                payment.contribution_amount,
                                            )}{' '}
                                            contributions / advance
                                        </span>
                                        {payment.withdrawal_fee_amount > 0 && (
                                            <span className="block text-xs text-muted-foreground">
                                                {formatUgx(
                                                    payment.withdrawal_fee_amount,
                                                )}{' '}
                                                withdrawal fees
                                            </span>
                                        )}
                                        {payment.status === 'submitted' &&
                                            payment.contribution_due_amount !==
                                                null && (
                                                <span className="block text-xs text-muted-foreground">
                                                    Starting month balance:{' '}
                                                    {formatUgx(
                                                        payment.contribution_due_amount,
                                                    )}
                                                </span>
                                            )}
                                        {payment.unapplied_amount > 0 && (
                                            <span className="block text-xs text-muted-foreground">
                                                {formatUgx(
                                                    payment.unapplied_amount,
                                                )}{' '}
                                                advance
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {payment.paid_on}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {payment.recorded_by ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {payment.evidence.length === 0 ? (
                                            <span className="text-muted-foreground">
                                                None
                                            </span>
                                        ) : (
                                            payment.evidence.map((file) => (
                                                <a
                                                    key={file.id}
                                                    href={
                                                        showEvidence({
                                                            payment: payment.id,
                                                            evidence: file.id,
                                                        }).url
                                                    }
                                                    className="block text-sm underline underline-offset-4"
                                                >
                                                    {file.original_name}
                                                </a>
                                            ))
                                        )}
                                    </TableCell>
                                    <TableCell className="max-w-72 align-top whitespace-normal">
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[payment.status]
                                            }
                                        >
                                            {payment.status}
                                        </Badge>
                                        {payment.rejection_reason && (
                                            <StatusNote>
                                                {payment.rejection_reason}
                                            </StatusNote>
                                        )}
                                        {payment.reversal_reason && (
                                            <StatusNote>
                                                {payment.reversal_requested_by
                                                    ? `${payment.reversal_requested_by}: ${payment.reversal_reason}`
                                                    : payment.reversal_reason}
                                            </StatusNote>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {payment.status === 'verified' && (
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="ghost"
                                                className="mb-1"
                                            >
                                                <a
                                                    href={
                                                        paymentReceipt(
                                                            payment.id,
                                                        ).url
                                                    }
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    Receipt
                                                </a>
                                            </Button>
                                        )}

                                        {(payment.can_review ||
                                            payment.can_request_reversal ||
                                            payment.can_decide_reversal) && (
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {payment.can_review && (
                                                    <>
                                                        <Form
                                                            {...PaymentVerificationController.update.form(
                                                                payment.id,
                                                            )}
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                                errors,
                                                            }) => (
                                                                <>
                                                                    <Button
                                                                        type="submit"
                                                                        size="sm"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                    >
                                                                        Verify
                                                                    </Button>
                                                                    <InputError
                                                                        message={
                                                                            errors.payment
                                                                        }
                                                                    />
                                                                </>
                                                            )}
                                                        </Form>

                                                        <RejectPaymentDialog
                                                            payment={payment}
                                                        />
                                                    </>
                                                )}

                                                {payment.can_request_reversal && (
                                                    <RequestReversalDialog
                                                        payment={payment}
                                                    />
                                                )}

                                                {payment.can_decide_reversal && (
                                                    <>
                                                        <Form
                                                            {...PaymentReversalController.update.form(
                                                                payment.id,
                                                            )}
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    type="submit"
                                                                    size="sm"
                                                                    variant="destructive"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    Approve
                                                                    reversal
                                                                </Button>
                                                            )}
                                                        </Form>

                                                        <Form
                                                            {...PaymentReversalController.destroy.form(
                                                                payment.id,
                                                            )}
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    type="submit"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    Decline
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    </>
                                                )}
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}

                            {payments.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No payments recorded yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={payments.links} />
            </AdminLayout>
        </AppLayout>
    );
}
