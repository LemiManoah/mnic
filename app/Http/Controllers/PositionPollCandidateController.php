<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\NominatePositionPollCandidate;
use App\Http\Requests\NominatePositionPollCandidateRequest;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PositionPollCandidateController
{
    public function store(
        NominatePositionPollCandidateRequest $request,
        PositionPoll $poll,
        #[CurrentUser] User $user,
        NominatePositionPollCandidate $action,
    ): RedirectResponse {
        $action->handle(
            $poll,
            Member::query()->findOrFail($request->string('member_id')->value()),
            $request->string('manifesto')->value() ?: null,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Candidate nominated.'),
        ]);

        return to_route('position-poll.show', $poll);
    }
}
