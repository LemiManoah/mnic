import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ContributionPeriodController from '@/actions/App/Http/Controllers/ContributionPeriodController';
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
import {
    index as periodIndex,
    show as showPeriod,
} from '@/routes/contribution-period';
import type { BreadcrumbItem, ContributionPeriod, Paginated } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contribution periods',
        href: periodIndex(),
    },
];

function OpenPeriodDialog() {
    const [open, setOpen] = useState(false);
    const now = new Date();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Open period</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Open contribution period</DialogTitle>
                    <DialogDescription>
                        Creates one obligation for every active member, using
                        the contribution amount in force today. Changing the
                        amount later will not affect this period.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ContributionPeriodController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="year">Year</Label>
                                <Input
                                    id="year"
                                    name="year"
                                    type="number"
                                    required
                                    defaultValue={now.getFullYear()}
                                />
                                <InputError message={errors.year} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="month">Month</Label>
                                <Input
                                    id="month"
                                    name="month"
                                    type="number"
                                    min={1}
                                    max={12}
                                    required
                                    defaultValue={now.getMonth() + 1}
                                />
                                <InputError message={errors.month} />
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
                                    Open period
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ContributionPeriodIndex({
    periods,
    canOpenPeriod,
}: {
    periods: Paginated<ContributionPeriod>;
    canOpenPeriod: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contribution periods" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Contribution periods"
                        description="Monthly obligations and how much has been collected"
                    />

                    {canOpenPeriod && <OpenPeriodDialog />}
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Period</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Members</TableHead>
                                <TableHead>Expected</TableHead>
                                <TableHead>Collected</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {periods.data.map((period) => (
                                <TableRow key={period.id}>
                                    <TableCell className="font-medium">
                                        {period.year}-
                                        {String(period.month).padStart(2, '0')}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {period.due_date.slice(0, 10)}
                                    </TableCell>
                                    <TableCell>
                                        {period.obligations_count ?? 0}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(period.expected_total ?? 0)}
                                    </TableCell>
                                    <TableCell>
                                        {formatUgx(period.collected_total ?? 0)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                period.status === 'open'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {period.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link href={showPeriod(period.id)}>
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {periods.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No contribution periods opened yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={periods.links} />
            </AdminLayout>
        </AppLayout>
    );
}
