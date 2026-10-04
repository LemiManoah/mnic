<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ClubProfile;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

final readonly class UpdateClubProfile
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * @param  array<string, array<string, string>>  $content
     */
    public function handle(ClubProfile $profile, array $content, ?Member $actor, ?string $ipAddress): ClubProfile
    {
        return DB::transaction(function () use ($profile, $content, $actor, $ipAddress): ClubProfile {
            $before = ['content' => $profile->content];

            $profile->update([
                'content' => $content,
                'updated_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle(
                'club_profile.updated',
                $profile,
                $actor,
                $before,
                ['content' => $profile->content],
                $ipAddress,
            );

            return $profile;
        });
    }
}
