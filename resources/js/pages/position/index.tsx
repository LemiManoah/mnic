import { Form, Head } from '@inertiajs/react';
import PositionController from '@/actions/App/Http/Controllers/PositionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { index as positionIndex } from '@/routes/position';
import type { BreadcrumbItem, MemberOption, Option } from '@/types';

type PositionRow = {
    value: string;
    label: string;
    holder: string | null;
    held_from: string | null;
};

type PositionHistoryRow = {
    id: string;
    position: string;
    member_name: string;
    held_from: string;
    held_to: string | null;
    source: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Positions', href: positionIndex() },
];

export default function PositionIndex({
    positions,
    history,
    members,
    positionOptions,
    canTransfer,
}: {
    positions: PositionRow[];
    history: PositionHistoryRow[];
    members: MemberOption[];
    positionOptions: Option[];
    canTransfer: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Positions" />

            <AdminLayout>
                <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Positions"
                            description="Current office holders and transfer history"
                        />

                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Office</TableHead>
                                        <TableHead>Current holder</TableHead>
                                        <TableHead>Held from</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {positions.map((position) => (
                                        <TableRow key={position.value}>
                                            <TableCell className="font-medium">
                                                {position.label}
                                            </TableCell>
                                            <TableCell>
                                                {position.holder ?? (
                                                    <span className="text-muted-foreground">
                                                        Vacant
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {position.held_from ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Office</TableHead>
                                        <TableHead>Member</TableHead>
                                        <TableHead>From</TableHead>
                                        <TableHead>To</TableHead>
                                        <TableHead>Source</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {history.map((holding) => (
                                        <TableRow key={holding.id}>
                                            <TableCell>
                                                {holding.position}
                                            </TableCell>
                                            <TableCell>
                                                {holding.member_name}
                                            </TableCell>
                                            <TableCell>
                                                {holding.held_from}
                                            </TableCell>
                                            <TableCell>
                                                {holding.held_to ?? 'Current'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {holding.source ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}

                                    {history.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                No position history yet.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    {canTransfer && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Transfer office</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...PositionController.store.form()}
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="position">
                                                    Office
                                                </Label>
                                                <select
                                                    id="position"
                                                    name="position"
                                                    required
                                                    defaultValue=""
                                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                >
                                                    <option value="" disabled>
                                                        Select office
                                                    </option>
                                                    {positionOptions.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={errors.position}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="member_id">
                                                    New holder
                                                </Label>
                                                <select
                                                    id="member_id"
                                                    name="member_id"
                                                    required
                                                    defaultValue=""
                                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                >
                                                    <option value="" disabled>
                                                        Select member
                                                    </option>
                                                    {members.map((member) => (
                                                        <option
                                                            key={member.id}
                                                            value={member.id}
                                                        >
                                                            {
                                                                member.member_number
                                                            }{' '}
                                                            - {member.full_name}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors.member_id}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="held_from">
                                                    Effective date
                                                </Label>
                                                <Input
                                                    id="held_from"
                                                    name="held_from"
                                                    type="date"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.held_from}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="reason">
                                                    Reason
                                                </Label>
                                                <Input
                                                    id="reason"
                                                    name="reason"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.reason}
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Transfer
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
