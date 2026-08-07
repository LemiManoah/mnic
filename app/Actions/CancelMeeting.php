<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CancelMeeting
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * Call off a meeting that has not happened yet.
     *
     * A meeting that has already been held cannot be cancelled — attendance and
     * minutes may hang off it, and pretending it never took place would break
     * the record those depend on.
     */
    public function handle(
        Meeting $meeting,
        string $reason,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Meeting {
        throw_unless(
            $meeting->status === MeetingStatus::Scheduled,
            InvalidArgumentException::class,
            'Only a scheduled meeting can be cancelled.',
        );

        return DB::transaction(function () use ($meeting, $reason, $actor, $ipAddress): Meeting {
            $before = $meeting->toArray();

            $meeting->update([
                'status' => MeetingStatus::Cancelled,
                'cancellation_reason' => $reason,
            ]);

            $this->recordAuditEvent->handle(
                'meeting.cancelled',
                $meeting,
                $actor,
                $before,
                $meeting->toArray(),
                $ipAddress,
            );

            return $meeting;
        });
    }
}
