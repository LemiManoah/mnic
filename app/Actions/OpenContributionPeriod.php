<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContributionPeriodStatus;
use App\Enums\MemberStatus;
use App\Enums\ObligationStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final readonly class OpenContributionPeriod
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(int $year, int $month, ?Member $actor = null, ?string $ipAddress = null): ContributionPeriod
    {
        $exists = ContributionPeriod::query()
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException("A contribution period for {$year}-{$month} already exists.");
        }

        // The amount is snapshotted onto the period and each obligation, so a
        // later settings change never rewrites history.
        $amount = $this->settingValue('contribution_amount');
        $dueDay = $this->settingValue('due_day');
        $graceDay = $this->settingValue('grace_day');

        return DB::transaction(function () use ($year, $month, $amount, $dueDay, $graceDay, $actor, $ipAddress): ContributionPeriod {
            $period = ContributionPeriod::query()->create([
                'year' => $year,
                'month' => $month,
                'amount' => $amount,
                'due_date' => CarbonImmutable::createFromDate($year, $month, $dueDay)->toDateString(),
                'grace_ends_on' => CarbonImmutable::createFromDate($year, $month, $graceDay)->toDateString(),
                'status' => ContributionPeriodStatus::Open,
                'opened_by_member_id' => $actor?->id,
            ]);

            $activeMembers = Member::query()
                ->where('status', MemberStatus::Active->value)
                ->get();

            foreach ($activeMembers as $member) {
                MemberObligation::query()->create([
                    'contribution_period_id' => $period->id,
                    'member_id' => $member->id,
                    'amount' => $amount,
                    'amount_paid' => 0,
                    'status' => ObligationStatus::Unpaid,
                ]);
            }

            $this->recordAuditEvent->handle(
                'contribution_period.opened',
                $period,
                $actor,
                null,
                $period->toArray(),
                $ipAddress,
            );

            return $period;
        });
    }

    private function settingValue(string $key): int
    {
        $version = Setting::query()->where('key', $key)->first()?->currentVersion();

        if ($version === null) {
            throw new RuntimeException("Missing club setting [{$key}].");
        }

        return (int) $version->value;
    }
}
