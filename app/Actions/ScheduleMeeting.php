<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Member;
use App\Notifications\MeetingScheduled;
use Illuminate\Support\Facades\DB;

final readonly class ScheduleMeeting
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, ?Member $actor = null, ?string $ipAddress = null): Meeting
    {
        $meeting = DB::transaction(function () use ($attributes, $actor, $ipAddress): Meeting {
            $meeting = Meeting::query()->create([
                ...$attributes,
                'status' => MeetingStatus::Scheduled,
                'scheduled_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle(
                'meeting.scheduled',
                $meeting,
                $actor,
                null,
                $meeting->toArray(),
                $ipAddress,
            );

            return $meeting;
        });

        $this->notifyMembers->toActiveMembers(new MeetingScheduled($meeting));

        return $meeting;
    }
}
