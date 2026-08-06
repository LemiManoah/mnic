<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttendance>
 */
final class MeetingAttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'member_id' => Member::factory(),
            'status' => AttendanceStatus::Present,
        ];
    }
}
