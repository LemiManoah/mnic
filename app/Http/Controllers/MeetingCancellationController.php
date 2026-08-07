<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CancelMeeting;
use App\Http\Requests\CancelMeetingRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MeetingCancellationController
{
    public function update(
        CancelMeetingRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        CancelMeeting $action,
    ): RedirectResponse {
        $action->handle(
            $meeting,
            $request->string('reason')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Meeting cancelled.'),
        ]);

        return to_route('meeting.show', $meeting);
    }
}
