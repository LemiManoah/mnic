<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CloseProposalVoting;
use App\Actions\OpenProposalVoting;
use App\Http\Requests\CloseProposalVotingRequest;
use App\Http\Requests\OpenProposalVotingRequest;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ProposalVotingController
{
    public function store(
        OpenProposalVotingRequest $request,
        Proposal $proposal,
        #[CurrentUser] User $user,
        OpenProposalVoting $action,
    ): RedirectResponse {
        $action->handle(
            $proposal,
            $request->string('closes_at')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Voting opened.'),
        ]);

        return to_route('proposal.show', $proposal);
    }

    public function update(
        CloseProposalVotingRequest $request,
        Proposal $proposal,
        #[CurrentUser] User $user,
        CloseProposalVoting $action,
    ): RedirectResponse {
        $action->handle(
            $proposal,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Voting closed and the result recorded.'),
        ]);

        return to_route('proposal.show', $proposal);
    }
}
