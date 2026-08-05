<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use Illuminate\Support\Facades\DB;

final readonly class CreateMember
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, ?Member $actor = null, ?string $ipAddress = null): Member
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress): Member {
            $member = Member::query()->create([
                ...$attributes,
                'status' => MemberStatus::Prospective,
            ]);

            MembershipStatusHistory::query()->create([
                'member_id' => $member->id,
                'from_status' => null,
                'to_status' => MemberStatus::Prospective,
                'reason' => 'Member onboarded.',
                'effective_date' => now()->toDateString(),
            ]);

            $this->recordAuditEvent->handle('member.created', $member, $actor, null, $member->toArray(), $ipAddress);

            return $member;
        });
    }
}
