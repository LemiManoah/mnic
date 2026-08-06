<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordMeetingAttendance;
use App\Http\Requests\RecordMeetingAttendanceRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MeetingAttendanceController
{
    public function update(
        RecordMeetingAttendanceRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        RecordMeetingAttendance $action,
    ): RedirectResponse {
        /** @var array<string, string> $attendance */
        $attendance = $request->validated('attendance');

        $action->handle(
            $meeting,
            $attendance,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Attendance recorded.'),
        ]);

        return to_route('meeting.show', $meeting);
    }
}
