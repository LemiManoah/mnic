<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Carbon\CarbonInterface;
use Database\Factories\MeetingAttendanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $meeting_id
 * @property-read string $member_id
 * @property-read AttendanceStatus $status
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MeetingAttendance extends Model
{
    /** @use HasFactory<MeetingAttendanceFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'meeting_id' => 'string',
            'member_id' => 'string',
            'status' => AttendanceStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
