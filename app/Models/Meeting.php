<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use Carbon\CarbonInterface;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $reference
 * @property-read string $title
 * @property-read CarbonInterface $scheduled_for
 * @property-read string|null $location
 * @property-read string|null $agenda
 * @property-read MeetingStatus $status
 * @property-read string|null $scheduled_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'reference' => 'string',
            'title' => 'string',
            'scheduled_for' => 'datetime',
            'location' => 'string',
            'agenda' => 'string',
            'status' => MeetingStatus::class,
            'scheduled_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<MeetingAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    /**
     * @return HasMany<Minute, $this>
     */
    public function minutes(): HasMany
    {
        return $this->hasMany(Minute::class);
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * @return HasMany<ActionItem, $this>
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function scheduledByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'scheduled_by_member_id');
    }

    public function presentCount(): int
    {
        return $this->attendances()
            ->where('status', AttendanceStatus::Present->value)
            ->count();
    }

    public function latestMinute(): ?Minute
    {
        return $this->minutes()->orderByDesc('version')->first();
    }
}
