import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ProposalController from '@/actions/App/Http/Controllers/ProposalController';
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
    index as proposalIndex,
    show as showProposal,
} from '@/routes/proposal';
import { governance as exportGovernance } from '@/routes/export';
import type {
    BreadcrumbItem,
    MeetingOption,
    Option,
    Paginated,
    Proposal,
    ProposalStatus,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Proposals', href: proposalIndex() },
];

const STATUS_VARIANT: Record<
    ProposalStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    open: 'secondary',
    passed: 'default',
    rejected: 'destructive',
    withdrawn: 'outline',
};

function CreateProposalDialog({ meetings }: { meetings: MeetingOption[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>New proposal</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New proposal</DialogTitle>
                    <DialogDescription>
                        Created as a draft. Eligibility and thresholds are
                        frozen only when you open voting.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ProposalController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={5}
                                    required
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="meeting_id">
                                    Linked meeting (optional)
                                </Label>
                                <select
                                    id="meeting_id"
                                    name="meeting_id"
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="">None</option>
                                    {meetings.map((meeting) => (
                                        <option
                                            key={meeting.id}
                                            value={meeting.id}
                                        >
                                            {meeting.reference} —{' '}
                                            {meeting.title}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.meeting_id} />
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

export default function ProposalIndex({
    proposals,
    canCreate,
    meetings,
    filters,
    statusOptions,
}: {
    proposals: Paginated<Proposal>;
    canCreate: boolean;
    meetings: MeetingOption[];
    filters: { search: string | null; status: string | null };
    statusOptions: Option[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Proposals" />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Proposals"
                        description="Motions put to the membership and their results"
                    />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <a href={exportGovernance().url}>Export</a>
                        </Button>

                        {canCreate && (
                            <CreateProposalDialog meetings={meetings} />
                        )}
                    </div>
                </div>

                <ListFilters
                    url={proposalIndex().url}
                    search={filters.search}
                    placeholder="Search proposal title or description…"
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
                                <TableHead>Title</TableHead>
                                <TableHead>Votes</TableHead>
                                <TableHead>Eligible</TableHead>
                                <TableHead>Closes</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {proposals.data.map((proposal) => (
                                <TableRow key={proposal.id}>
                                    <TableCell className="font-medium">
                                        {proposal.title}
                                    </TableCell>
                                    <TableCell>
                                        {proposal.votes_count ?? 0}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {proposal.eligible_voter_count}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {proposal.closes_at?.slice(0, 10) ??
                                            '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[proposal.status]
                                            }
                                        >
                                            {proposal.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={showProposal(proposal.id)}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {proposals.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No proposals yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={proposals.links} />
            </AdminLayout>
        </AppLayout>
    );
}
