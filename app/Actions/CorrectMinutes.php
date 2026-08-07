<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Meeting;
use App\Models\Member;
use App\Models\Minute;
use App\Notifications\MinutesPublished;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CorrectMinutes
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    /**
     * Correct minutes that have already been confirmed.
     *
     * The club's decision was that a correction is a **superseding version**,
     * not an edit and not an appendix. The confirmed version stays exactly as
     * it was, the corrected text becomes the next version, and the new version
     * has to be confirmed on its own account before it is official. That way
     * the record shows both what was originally agreed and what replaced it,
     * and nobody can quietly rewrite a decision after the fact.
     *
     * Unconfirmed minutes do not come through here — republishing those is
     * PublishMinutes, because there is nothing official to supersede yet.
     */
    public function handle(
        Meeting $meeting,
        string $body,
        string $reason,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Minute {
        $superseded = $meeting->latestMinute();

        throw_unless(
            $superseded instanceof Minute && $superseded->isConfirmed(),
            InvalidArgumentException::class,
            'Only confirmed minutes can be corrected; publish a new version instead.',
        );

        $minute = DB::transaction(function () use ($meeting, $body, $reason, $superseded, $actor, $ipAddress): Minute {
            $minute = Minute::query()->create([
                'meeting_id' => $meeting->id,
                'version' => $superseded->version + 1,
                'body' => $body,
                'published_by_member_id' => $actor?->id,
                'correction_reason' => $reason,
                'corrects_minute_id' => $superseded->id,
            ]);

            $this->recordAuditEvent->handle(
                'minutes.corrected',
                $minute,
                $actor,
                $superseded->toArray(),
                $minute->toArray(),
                $ipAddress,
            );

            return $minute;
        });

        // Everyone hears about a correction, not just whoever noticed it — a
        // quiet correction to a confirmed record is the thing this whole
        // mechanism exists to prevent.
        $this->notifyMembers->toActiveMembers(new MinutesPublished($meeting, $minute));

        return $minute;
    }
}
