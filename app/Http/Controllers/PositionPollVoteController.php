<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastPositionPollVote;
use App\Http\Requests\CastPositionPollVoteRequest;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PositionPollVoteController
{
    public function store(
        CastPositionPollVoteRequest $request,
        PositionPoll $poll,
        #[CurrentUser] User $user,
        CastPositionPollVote $action,
    ): RedirectResponse {
        $action->handle(
            $poll,
            Member::query()->where('user_id', $user->id)->firstOrFail(),
            PositionPollCandidate::query()->findOrFail(
                $request->string('position_poll_candidate_id')->value(),
            ),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Your vote has been recorded.'),
        ]);

        return to_route('position-poll.show', $poll);
    }
}
