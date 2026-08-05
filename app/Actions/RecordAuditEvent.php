<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Database\Eloquent\Model;

final readonly class RecordAuditEvent
{
    /**
     * @param  array<mixed>|null  $before
     * @param  array<mixed>|null  $after
     */
    public function handle(
        string $event,
        Model $auditable,
        ?Member $actor,
        ?array $before,
        ?array $after,
        ?string $ipAddress = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_member_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => $ipAddress,
        ]);
    }
}
