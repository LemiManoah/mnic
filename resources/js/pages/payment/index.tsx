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
import { index as paymentIndex } from '@/routes/payment';
import { show as showEvidence } from '@/routes/payment-evidence';
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
    reversed: 'secondary',
};

function RecordPaymentDialog({
    members,
    methodOptions,
}: {
    members: MemberOption[];
    methodOptions: Option[];
}) {
    const [open, setOpen] = useState(false);

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
                    onSuccess={() => setOpen(false)}
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
                                    defaultValue=""
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
                                <Button type="submit" disabled={processing}>
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

function ReversePaymentDialog({ payment }: { payment: PaymentRow }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Reverse
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reverse payment</DialogTitle>
                    <DialogDescription>
                        Reference {payment.reference} - allocations will be
                        removed and obligations restored.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PaymentReversalController.update.form(payment.id)}
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
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Reverse payment
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
    members: MemberOption[];
    statusOptions: Option[];
    methodOptions: Option[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payments" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Payments"
                        description="Recorded contributions awaiting or completed verification"
                    />

                    <RecordPaymentDialog
                        members={members}
                        methodOptions={methodOptions}
                    />
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
                                <TableHead>Amount</TableHead>
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
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[payment.status]
                                            }
                                        >
                                            {payment.status}
                                        </Badge>
                                        {payment.rejection_reason && (
                                            <span className="block text-xs text-muted-foreground">
                                                {payment.rejection_reason}
                                            </span>
                                        )}
                                        {payment.reversal_reason && (
                                            <span className="block text-xs text-muted-foreground">
                                                {payment.reversal_reason}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {(payment.can_review ||
                                            payment.can_reverse) && (
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
                                                            }) => (
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

                                                        <RejectPaymentDialog
                                                            payment={payment}
                                                        />
                                                    </>
                                                )}

                                                {payment.can_reverse && (
                                                    <ReversePaymentDialog
                                                        payment={payment}
                                                    />
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
