<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Http\Requests\CreateProposalRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProposalController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Proposal::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();

        return Inertia::render('proposal/index', [
            'proposals' => Proposal::query()
                ->withCount('votes')
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('title', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('description', 'like', sprintf('%%%s%%', $search))))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
            ],
            'statusOptions' => array_map(
                static fn (ProposalStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ProposalStatus::cases(),
            ),
            'canCreate' => $user->can('create', Proposal::class),
            'meetings' => Meeting::query()
                ->orderByDesc('scheduled_for')
                ->get(['id', 'reference', 'title']),
        ]);
    }

    public function show(Proposal $proposal, #[CurrentUser] User $user): Response
    {
        Gate::authorize('view', $proposal);

        $proposal->loadMissing(['votes.member', 'meeting']);

        return Inertia::render('proposal/show', [
            'proposal' => $proposal,
            'canManageVoting' => $user->can('manageVoting', $proposal),
            'canVote' => $user->can('vote', $proposal),
            'canWithdraw' => $user->can('withdraw', $proposal),
            'tally' => [
                'for' => $proposal->countChoice(VoteChoice::For),
                'against' => $proposal->countChoice(VoteChoice::Against),
                'abstain' => $proposal->countChoice(VoteChoice::Abstain),
            ],
            // Individual votes stay private while voting is open, so nobody can
            // watch the tally shift and vote strategically.
            'votes' => $proposal->status === ProposalStatus::Open
                ? []
                : $proposal->votes->map(fn (Vote $vote): array => [
                    'id' => $vote->id,
                    'member_name' => $vote->member->full_name ?? __('Unknown member'),
                    'choice' => $vote->choice,
                    'has_conflict' => $vote->has_conflict,
                    'conflict_note' => $vote->conflict_note,
                ]),
        ]);
    }

    public function store(
        CreateProposalRequest $request,
        #[CurrentUser] User $user,
        Proposal $proposal,
    ): RedirectResponse {
        $proposal->fill([
            ...$request->validated(),
            'status' => ProposalStatus::Draft,
            'created_by_member_id' => Member::query()->firstWhere('user_id', $user->id)?->id,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Proposal created as a draft.'),
        ]);

        return to_route('proposal.show', $proposal);
    }
}
