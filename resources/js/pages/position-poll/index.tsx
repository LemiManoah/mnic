import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import PositionPollController from '@/actions/App/Http/Controllers/PositionPollController';
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
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import {
    index as positionPollIndex,
    show as showPositionPoll,
} from '@/routes/position-poll';
import type {
    BreadcrumbItem,
    Option,
    Paginated,
    PositionPoll,
    PositionPollStatus,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Elections', href: positionPollIndex() },
];

const STATUS_VARIANT: Record<
    PositionPollStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    open: 'secondary',
    decided: 'default',
    failed: 'destructive',
};

function CreatePollDialog({ positionOptions }: { positionOptions: Option[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>New election</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New election</DialogTitle>
                    <DialogDescription>
                        Created as a draft. Nominate the candidates first — the
                        ballot is fixed once voting opens.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PositionPollController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="position">Office</Label>
                                <select
                                    id="position"
                                    name="position"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select office
                                    </option>
                                    {positionOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.position} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    placeholder="e.g. Treasurer election 2027"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description (optional)
                                </Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                />
                                <InputError message={errors.description} />
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
                                    Create draft
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function PositionPollIndex({
    polls,
    filters,
    statusOptions,
    positionOptions,
    canCreate,
}: {
    polls: Paginated<PositionPoll>;
    filters: {
        search: string | null;
        status: string | null;
        position: string | null;
    };
    statusOptions: Option[];
    positionOptions: Option[];
    canCreate: boolean;
}) {
    // The paginated rows carry the raw enum value; the options list is the only
    // place the human-readable office name is available.
    const officeLabels = new Map(
        positionOptions.map((option) => [option.value, option.label]),
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Elections" />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Elections"
                        description="Polls for club offices, decided by the most votes"
                    />

                    {canCreate && (
                        <CreatePollDialog positionOptions={positionOptions} />
                    )}
                </div>

                <ListFilters
                    url={positionPollIndex().url}
                    search={filters.search}
                    placeholder="Search election title…"
                    filters={[
                        {
                            name: 'status',
                            label: 'Status',
                            value: filters.status,
                            options: statusOptions,
                        },
                        {
                            name: 'position',
                            label: 'Office',
                            value: filters.position,
                            options: positionOptions,
                        },
                    ]}
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Election</TableHead>
                                <TableHead>Office</TableHead>
                                <TableHead>Candidates</TableHead>
                                <TableHead>Votes</TableHead>
                                <TableHead>Closes</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {polls.data.map((poll) => (
                                <TableRow key={poll.id}>
                                    <TableCell className="font-medium">
                                        {poll.title}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {officeLabels.get(poll.position) ??
                                            poll.position}
                                    </TableCell>
                                    <TableCell>
                                        {poll.candidates_count ?? 0}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {poll.status === 'open'
                                            ? '—'
                                            : (poll.votes_count ?? 0)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {poll.closes_at?.slice(0, 10) ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[poll.status]
                                            }
                                        >
                                            {poll.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={showPositionPoll(poll.id)}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {polls.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No elections yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={polls.links} />
            </AdminLayout>
        </AppLayout>
    );
}
