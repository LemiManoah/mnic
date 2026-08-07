import { Form, Head } from '@inertiajs/react';
import PositionPollCandidateController from '@/actions/App/Http/Controllers/PositionPollCandidateController';
import PositionPollVoteController from '@/actions/App/Http/Controllers/PositionPollVoteController';
import PositionPollVotingController from '@/actions/App/Http/Controllers/PositionPollVotingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import {
    index as positionPollIndex,
    show as showPositionPoll,
} from '@/routes/position-poll';
import type {
    BreadcrumbItem,
    MemberOption,
    PositionPollCandidate,
    PositionPollDetail,
} from '@/types';

export default function PositionPollShow({
    poll,
    candidates,
    resultsVisible,
    canNominate,
    canOpenVoting,
    canCloseVoting,
    canVote,
    members,
}: {
    poll: PositionPollDetail;
    candidates: PositionPollCandidate[];
    resultsVisible: boolean;
    canNominate: boolean;
    canOpenVoting: boolean;
    canCloseVoting: boolean;
    canVote: boolean;
    members: MemberOption[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Elections', href: positionPollIndex() },
        { title: poll.title, href: showPositionPoll(poll.id) },
    ];

    const totalVotes = candidates.reduce(
        (sum, candidate) => sum + (candidate.votes ?? 0),
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={poll.title} />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title={poll.title}
                        description={
                            poll.status === 'draft'
                                ? `${poll.position} — draft, voting has not opened`
                                : `${poll.position} · ${poll.eligible_voter_count} eligible · quorum ${poll.quorum_required}`
                        }
                    />
                    <Badge variant="secondary">{poll.status}</Badge>
                </div>

                {poll.description && (
                    <Card>
                        <CardHeader>
                            <CardTitle>About this election</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm whitespace-pre-wrap">
                            {poll.description}
                        </CardContent>
                    </Card>
                )}

                {poll.outcome_note && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Result</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            {poll.outcome_note}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>
                            Candidates{' '}
                            {resultsVisible && candidates.length > 0 && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    · {totalVotes} vote
                                    {totalVotes === 1 ? '' : 's'} cast
                                </span>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {candidates.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Nobody has been nominated yet.
                            </p>
                        )}

                        {candidates.map((candidate) => (
                            <div
                                key={candidate.id}
                                className="flex flex-wrap items-start justify-between gap-3 rounded-md border px-3 py-2"
                            >
                                <div className="min-w-0">
                                    <div className="font-medium">
                                        {candidate.member_name}
                                        {poll.winner_name ===
                                            candidate.member_name && (
                                            <Badge
                                                variant="default"
                                                className="ml-2"
                                            >
                                                Elected
                                            </Badge>
                                        )}
                                    </div>
                                    {candidate.manifesto && (
                                        <p className="mt-1 text-sm whitespace-pre-wrap text-muted-foreground">
                                            {candidate.manifesto}
                                        </p>
                                    )}
                                </div>

                                {resultsVisible && (
                                    <span className="shrink-0 text-sm text-muted-foreground">
                                        {candidate.votes ?? 0} vote
                                        {candidate.votes === 1 ? '' : 's'}
                                    </span>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {canVote && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Cast your vote</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...PositionPollVoteController.store.form(
                                    poll.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <fieldset className="space-y-2">
                                            <legend className="sr-only">
                                                Candidates
                                            </legend>
                                            {candidates.map((candidate) => (
                                                <label
                                                    key={candidate.id}
                                                    className="flex items-center gap-3 rounded-md border px-3 py-2 text-sm"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="position_poll_candidate_id"
                                                        value={candidate.id}
                                                        required
                                                    />
                                                    {candidate.member_name}
                                                </label>
                                            ))}
                                        </fieldset>
                                        <InputError
                                            message={
                                                errors.position_poll_candidate_id
                                            }
                                        />

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

                {canNominate && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Nominate a candidate</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...PositionPollCandidateController.store.form(
                                    poll.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="grid max-w-xl gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="member_id">
                                                Member
                                            </Label>
                                            <select
                                                id="member_id"
                                                name="member_id"
                                                required
                                                defaultValue=""
                                                className="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                            >
                                                <option value="" disabled>
                                                    Select member
                                                </option>
                                                {members.map((member) => (
                                                    <option
                                                        key={member.id}
                                                        value={member.id}
                                                    >
                                                        {member.member_number} -{' '}
                                                        {member.full_name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.member_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="manifesto">
                                                Manifesto (optional)
                                            </Label>
                                            <Textarea
                                                id="manifesto"
                                                name="manifesto"
                                                rows={3}
                                            />
                                            <InputError
                                                message={errors.manifesto}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            Nominate
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {canOpenVoting && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Open voting</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...PositionPollVotingController.store.form(
                                    poll.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="grid max-w-xl gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="closes_at">
                                                Voting closes
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
                                            <p className="text-sm text-muted-foreground">
                                                The electorate and quorum are
                                                frozen at this moment and cannot
                                                be changed afterwards.
                                            </p>
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            Open voting
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {canCloseVoting && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Close voting</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...PositionPollVotingController.update.form(
                                    poll.id,
                                )}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <>
                                        <p className="mb-4 text-sm text-muted-foreground">
                                            The candidate with the most votes
                                            takes the office immediately. If
                                            turnout misses quorum or the leaders
                                            tie, the office is left unchanged.
                                        </p>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Close voting and declare the result
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}
            </AdminLayout>
        </AppLayout>
    );
}
