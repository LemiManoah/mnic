<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\WithdrawProposal;
use App\Http\Requests\WithdrawProposalRequest;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ProposalWithdrawalController
{
    public function update(
        WithdrawProposalRequest $request,
        Proposal $proposal,
        #[CurrentUser] User $user,
        WithdrawProposal $action,
    ): RedirectResponse {
        $action->handle(
            $proposal,
            $request->string('reason')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Proposal withdrawn.'),
        ]);

        return to_route('proposal.show', $proposal);
    }
}
