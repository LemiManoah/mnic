<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastVote;
use App\Enums\VoteChoice;
use App\Http\Requests\CastVoteRequest;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class VoteController
{
    public function store(
        CastVoteRequest $request,
        Proposal $proposal,
        #[CurrentUser] User $user,
        CastVote $action,
    ): RedirectResponse {
        // ProposalPolicy::vote already guarantees an eligible member record.
        $member = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle(
            $proposal,
            $member,
            VoteChoice::from($request->string('choice')->value()),
            $request->boolean('has_conflict'),
            $request->filled('conflict_note') ? $request->string('conflict_note')->value() : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Your vote has been recorded.'),
        ]);

        return to_route('proposal.show', $proposal);
    }
}
