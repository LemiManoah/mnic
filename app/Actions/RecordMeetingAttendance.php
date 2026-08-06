<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordMeetingAttendance
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  array<string, string>  $attendance  member id => attendance status value
     */
    public function handle(Meeting $meeting, array $attendance, ?Member $actor = null, ?string $ipAddress = null): Meeting
    {
        throw_if($meeting->status === MeetingStatus::Cancelled, InvalidArgumentException::class, 'Attendance cannot be recorded for a cancelled meeting.');

        return DB::transaction(function () use ($meeting, $attendance, $actor, $ipAddress): Meeting {
            foreach ($attendance as $memberId => $status) {
                MeetingAttendance::query()->updateOrCreate(
                    ['meeting_id' => $meeting->id, 'member_id' => $memberId],
                    ['status' => AttendanceStatus::from($status)],
                );
            }

            $meeting->update(['status' => MeetingStatus::Completed]);

            $this->recordAuditEvent->handle(
                'meeting.attendance_recorded',
                $meeting,
                $actor,
                null,
                ['attendance' => $attendance],
                $ipAddress,
            );

            return $meeting;
        });
    }
}
