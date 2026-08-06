import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    index as reportIndex,
    show as showReport,
} from '@/routes/monthly-report';
import type { BreadcrumbItem, ContributionPeriodStatus } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monthly reports', href: reportIndex() },
];

type PeriodRow = {
    id: string;
    label: string;
    status: ContributionPeriodStatus;
};

export default function MonthlyReportIndex({
    periods,
}: {
    periods: PeriodRow[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monthly reports" />

            <AdminLayout>
                <Heading
                    variant="small"
                    title="Monthly transparency reports"
                    description="One report per contribution period, open to every member"
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Period</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {periods.map((period) => (
                                <TableRow key={period.id}>
                                    <TableCell className="font-medium">
                                        {period.label}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {period.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link href={showReport(period.id)}>
                                                View report
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {periods.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No contribution periods yet.
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
