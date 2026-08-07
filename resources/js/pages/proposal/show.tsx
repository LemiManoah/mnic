import { Form, Head } from '@inertiajs/react';
import ProposalVotingController from '@/actions/App/Http/Controllers/ProposalVotingController';
import VoteController from '@/actions/App/Http/Controllers/VoteController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    index as proposalIndex,
    show as showProposal,
} from '@/routes/proposal';
import type { BreadcrumbItem, Proposal, VoteRow, VoteTally } from '@/types';

const CHOICES = ['for', 'against', 'abstain'] as const;

export default function ProposalShow({
    proposal,
    canManageVoting,
    canVote,
    tally,
    votes,
    election,
}: {
    proposal: Proposal;
    canManageVoting: boolean;
    canVote: boolean;
    tally: VoteTally;
    votes: VoteRow[];
    election: { position: string; member_name: string } | null;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Proposals', href: proposalIndex() },
        { title: proposal.title, href: showProposal(proposal.id) },
    ];

    const turnout = tally.for + tally.against + tally.abstain;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={proposal.title} />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title={proposal.title}
                        description={
                            proposal.status === 'draft'
                                ? 'Draft — voting has not opened'
                                : `${proposal.eligible_voter_count} eligible · quorum ${proposal.quorum_required} · approval ${proposal.approval_percent}%`
                        }
                    />
                    <Badge variant="secondary">{proposal.status}</Badge>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Proposal</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        {election && (
                            <div className="rounded-md border px-3 py-2">
                                Election: {election.member_name} for{' '}
                                {election.position}
                            </div>
                        )}
                        <div className="whitespace-pre-wrap">
                            {proposal.description}
                        </div>
                    </CardContent>
                </Card>

                {proposal.status === 'draft' && canManageVoting && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Open voting</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...ProposalVotingController.store.form(
                                    proposal.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <p className="text-sm text-muted-foreground">
                                            Opening voting freezes the list of
                                            eligible voters and the quorum and
                                            approval thresholds. Later changes
                                            to membership or club settings will
                                            not affect this vote.
                                        </p>

                                        <div className="grid max-w-sm gap-2">
                                            <Label htmlFor="closes_at">
                                                Voting closes at
                                            </Label>
                                            <Input
                                                id="closes_at"
                                                name="closes_at"
                                                type="datetime-local"
                                                required
                                            />
                                            <InputError
                                                message={errors.closes_at}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Open voting
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {proposal.status === 'open' && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {canVote && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cast your vote</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        {...VoteController.store.form(
                                            proposal.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                        className="space-y-4"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="choice">
                                                        Choice
                                                    </Label>
                                                    <select
                                                        id="choice"
                                                        name="choice"
                                                        required
                                                        defaultValue=""
                                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            Select
                                                        </option>
                                                        {CHOICES.map(
                                                            (choice) => (
                                                                <option
                                                                    key={choice}
                                                                    value={
                                                                        choice
                                                                    }
                                                                >
                                                                    {choice}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        message={errors.choice}
                                                    />
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <Checkbox
                                                        id="has_conflict"
                                                        name="has_conflict"
                                                        value="1"
                                                    />
                                                    <Label htmlFor="has_conflict">
                                                        I declare a conflict of
                                                        interest
                                                    </Label>
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="conflict_note">
                                                        Conflict note (optional)
                                                    </Label>
                                                    <Input
                                                        id="conflict_note"
                                                        name="conflict_note"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.conflict_note
                                                        }
                                                    />
                                                </div>

                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    Submit vote
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Turnout</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <p>
                                    {turnout} of {proposal.eligible_voter_count}{' '}
                                    eligible members have voted (quorum{' '}
                                    {proposal.quorum_required}).
                                </p>
                                <p className="text-muted-foreground">
                                    Individual votes stay hidden until voting
                                    closes.
                                </p>

                                {canManageVoting && (
                                    <Form
                                        {...ProposalVotingController.update.form(
                                            proposal.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                Close voting and record result
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {(proposal.status === 'passed' ||
                    proposal.status === 'rejected') && (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Result</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <div className="flex gap-4">
                                    <span>For: {tally.for}</span>
                                    <span>Against: {tally.against}</span>
                                    <span>Abstain: {tally.abstain}</span>
                                </div>
                                <p className="text-muted-foreground">
                                    {proposal.outcome_note}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Votes cast</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Member</TableHead>
                                                <TableHead>Choice</TableHead>
                                                <TableHead>Conflict</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {votes.map((vote) => (
                                                <TableRow key={vote.id}>
                                                    <TableCell>
                                                        {vote.member_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {vote.choice}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {vote.has_conflict
                                                            ? (vote.conflict_note ??
                                                              'Declared')
                                                            : '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}

                                            {votes.length === 0 && (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={3}
                                                        className="py-6 text-center text-muted-foreground"
                                                    >
                                                        No votes were cast.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}
            </AdminLayout>
        </AppLayout>
    );
}
