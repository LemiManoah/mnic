<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CorrectMinutes;
use App\Http\Requests\CorrectMinutesRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MeetingMinutesCorrectionController
{
    public function store(
        CorrectMinutesRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        CorrectMinutes $action,
    ): RedirectResponse {
        $action->handle(
            $meeting,
            $request->string('body')->value(),
            $request->string('reason')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Correction published. It must be confirmed before it is official.'),
        ]);

        return to_route('meeting.show', $meeting);
    }
}
