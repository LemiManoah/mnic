<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePositionPoll;
use App\Enums\ClubPosition;
use App\Enums\MemberStatus;
use App\Enums\PositionPollStatus;
use App\Http\Requests\CreatePositionPollRequest;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PositionPollController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', PositionPoll::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();
        $position = $request->string('position')->value();

        return Inertia::render('position-poll/index', [
            'polls' => PositionPoll::query()
                ->withCount(['candidates', 'votes'])
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where('title', 'like', sprintf('%%%s%%', $search)))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->when($position !== '', fn (Builder $query): Builder => $query
                    ->where('position', $position))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
                'position' => $position === '' ? null : $position,
            ],
            'statusOptions' => array_map(
                static fn (PositionPollStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                PositionPollStatus::cases(),
            ),
            'positionOptions' => array_map(
                static fn (ClubPosition $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ClubPosition::cases(),
            ),
            'canCreate' => $user->can('create', PositionPoll::class),
        ]);
    }

    public function show(PositionPoll $poll, #[CurrentUser] User $user): Response
    {
        Gate::authorize('view', $poll);

        $poll->loadMissing(['candidates.member', 'winningCandidate.member']);

        $isOpen = $poll->isOpen();

        // Running totals stay hidden while voting is open so nobody can watch
        // the count and vote tactically.
        /** @var array<string, int> $tallies */
        $tallies = [];

        if (! $isOpen) {
            foreach ($poll->votes()->get() as $vote) {
                $tallies[$vote->position_poll_candidate_id] =
                    ($tallies[$vote->position_poll_candidate_id] ?? 0) + 1;
            }
        }

        return Inertia::render('position-poll/show', [
            'poll' => [
                'id' => $poll->id,
                'position' => $poll->position->label(),
                'title' => $poll->title,
                'description' => $poll->description,
                'status' => $poll->status,
                'opened_at' => $poll->opened_at?->toDayDateTimeString(),
                'closes_at' => $poll->closes_at?->toDayDateTimeString(),
                'closed_at' => $poll->closed_at?->toDayDateTimeString(),
                'eligible_voter_count' => $poll->eligible_voter_count,
                'quorum_required' => $poll->quorum_required,
                'outcome_note' => $poll->outcome_note,
                'winner_name' => $poll->winningCandidate?->member?->full_name,
            ],
            'candidates' => $poll->candidates->map(fn (PositionPollCandidate $candidate): array => [
                'id' => $candidate->id,
                'member_name' => $candidate->member->full_name ?? __('Unknown member'),
                'manifesto' => $candidate->manifesto,
                'votes' => $isOpen ? null : ($tallies[$candidate->id] ?? 0),
            ])->all(),
            'resultsVisible' => ! $isOpen,
            'canNominate' => $user->can('nominate', $poll),
            'canOpenVoting' => $user->can('openVoting', $poll),
            'canCloseVoting' => $user->can('closeVoting', $poll),
            'canVote' => $user->can('vote', $poll),
            'members' => $user->can('nominate', $poll)
                ? Member::query()
                    ->where('status', MemberStatus::Active->value)
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'member_number'])
                : [],
        ]);
    }

    public function store(
        CreatePositionPollRequest $request,
        #[CurrentUser] User $user,
        CreatePositionPoll $action,
    ): RedirectResponse {
        $poll = $action->handle(
            ClubPosition::from($request->string('position')->value()),
            $request->string('title')->value(),
            $request->string('description')->value() ?: null,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Poll created. Nominate candidates, then open voting.'),
        ]);

        return to_route('position-poll.show', $poll);
    }
}
