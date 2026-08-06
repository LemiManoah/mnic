<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MeetingStatus;
use App\Models\Member;
use App\Models\Minute;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ConfirmMinutes
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Minute $minute, Member $confirmer, ?string $ipAddress = null): Minute
    {
        throw_if($minute->isConfirmed(), InvalidArgumentException::class, 'These minutes are already confirmed.');

        return DB::transaction(function () use ($minute, $confirmer, $ipAddress): Minute {
            $before = $minute->toArray();

            $minute->update([
                'confirmed_at' => now(),
                'confirmed_by_member_id' => $confirmer->id,
            ]);

            $minute->meeting->update(['status' => MeetingStatus::Confirmed]);

            $this->recordAuditEvent->handle(
                'minutes.confirmed',
                $minute,
                $confirmer,
                $before,
                $minute->toArray(),
                $ipAddress,
            );

            return $minute;
        });
    }
}
