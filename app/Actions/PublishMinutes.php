<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Meeting;
use App\Models\Member;
use App\Models\Minute;
use App\Notifications\MinutesPublished;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class PublishMinutes
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(Meeting $meeting, string $body, ?Member $actor = null, ?string $ipAddress = null): Minute
    {
        $latest = $meeting->latestMinute();

        throw_if($latest instanceof Minute && $latest->isConfirmed(), InvalidArgumentException::class, 'Confirmed minutes cannot be replaced; record a correction instead.');

        $minute = DB::transaction(function () use ($meeting, $body, $latest, $actor, $ipAddress): Minute {
            // Publishing always adds a version rather than editing in place.
            $minute = Minute::query()->create([
                'meeting_id' => $meeting->id,
                'version' => ($latest->version ?? 0) + 1,
                'body' => $body,
                'published_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle(
                'minutes.published',
                $minute,
                $actor,
                null,
                $minute->toArray(),
                $ipAddress,
            );

            return $minute;
        });

        $this->notifyMembers->toActiveMembers(new MinutesPublished($meeting, $minute));

        return $minute;
    }
}
