<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ChangeMemberStatus
{
    /**
     * @var array<string, list<string>>
     */
    private const array ALLOWED_TRANSITIONS = [
        'prospective' => ['active', 'removed'],
        'active' => ['suspended', 'exited'],
        'suspended' => ['active', 'exited', 'removed'],
        'exited' => [],
        'removed' => [],
    ];

    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public static function isTransitionAllowed(MemberStatus $from, MemberStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true);
    }

    /**
     * @return list<MemberStatus>
     */
    public static function allowedTransitionsFrom(MemberStatus $from): array
    {
        return array_map(
            MemberStatus::from(...),
            self::ALLOWED_TRANSITIONS[$from->value],
        );
    }

    public function handle(
        Member $member,
        MemberStatus $toStatus,
        string $reason,
        string $effectiveDate,
        ?string $resolutionReference,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Member {
        $fromStatus = $member->status;

        if (! self::isTransitionAllowed($fromStatus, $toStatus)) {
            throw new InvalidArgumentException(sprintf('Cannot transition member from %s to %s.', $fromStatus->value, $toStatus->value));
        }

        return DB::transaction(function () use ($member, $fromStatus, $toStatus, $reason, $effectiveDate, $resolutionReference, $actor, $ipAddress): Member {
            $before = $member->toArray();

            $member->update(['status' => $toStatus]);

            MembershipStatusHistory::query()->create([
                'member_id' => $member->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'effective_date' => $effectiveDate,
                'resolution_reference' => $resolutionReference,
            ]);

            $this->recordAuditEvent->handle('member.status_changed', $member, $actor, $before, $member->toArray(), $ipAddress);

            return $member;
        });
    }
}
