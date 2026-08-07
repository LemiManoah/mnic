<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ClosePositionPollVoting;
use App\Actions\OpenPositionPollVoting;
use App\Http\Requests\OpenPositionPollVotingRequest;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class PositionPollVotingController
{
    public function store(
        OpenPositionPollVotingRequest $request,
        PositionPoll $poll,
        #[CurrentUser] User $user,
        OpenPositionPollVoting $action,
    ): RedirectResponse {
        $action->handle(
            $poll,
            $request->string('closes_at')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Voting is open.'),
        ]);

        return to_route('position-poll.show', $poll);
    }

    public function update(
        Request $request,
        PositionPoll $poll,
        #[CurrentUser] User $user,
        ClosePositionPollVoting $action,
    ): RedirectResponse {
        Gate::authorize('closeVoting', $poll);

        $action->handle(
            $poll,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Voting closed and the result recorded.'),
        ]);

        return to_route('position-poll.show', $poll);
    }
}
