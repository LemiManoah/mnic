<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConfirmMinutes;
use App\Actions\PublishMinutes;
use App\Http\Requests\ConfirmMinutesRequest;
use App\Http\Requests\PublishMinutesRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MeetingMinutesController
{
    public function store(
        PublishMinutesRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        PublishMinutes $action,
    ): RedirectResponse {
        $action->handle(
            $meeting,
            $request->string('body')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Minutes published.'),
        ]);

        return to_route('meeting.show', $meeting);
    }

    public function update(
        ConfirmMinutesRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        ConfirmMinutes $action,
    ): RedirectResponse {
        $confirmer = Member::query()->where('user_id', $user->id)->firstOrFail();

        $minute = $meeting->minutes()->orderByDesc('version')->firstOrFail();

        $action->handle($minute, $confirmer, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Minutes confirmed.'),
        ]);

        return to_route('meeting.show', $meeting);
    }
}
