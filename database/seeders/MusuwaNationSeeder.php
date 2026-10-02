<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\OpenContributionPeriod;
use App\Actions\RecordAuditEvent;
use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PositionHolding;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

final class MusuwaNationSeeder extends Seeder
{
    private const string PASSWORD = 'password';

    private const string STARTED_ON = '2026-08-01';

    /** @var array<int, int> */
    private const array AUGUST_CONTRIBUTIONS = [
        1 => 60000, 2 => 60000, 3 => 60000, 4 => 60000,
        6 => 60000, 7 => 60000, 8 => 60000, 10 => 60000,
        11 => 60000, 12 => 60000, 13 => 60000, 15 => 45000,
        17 => 60000, 18 => 60000, 19 => 60000, 20 => 60000,
    ];

    /** @var list<array{name: string, email: string, position?: ClubPosition, role?: ClubRole}> */
    private const array MEMBERS = [
        ['name' => 'Ssekweyama Fredrick', 'email' => 'mubuukefredrick24@gmail.com', 'position' => ClubPosition::Chairperson, 'role' => ClubRole::InterimChairperson],
        ['name' => 'Tumwijukye Conrad', 'email' => 'tumwijukyeconrad99@gmail.com', 'position' => ClubPosition::ViceChairperson, 'role' => ClubRole::InterimChairperson],
        ['name' => 'Lemi Manoah', 'email' => 'lemi.manoah@gmail.com', 'position' => ClubPosition::Treasurer, 'role' => ClubRole::Administrator],
        ['name' => 'Nyero John', 'email' => 'johnnyero02@gmail.com'],
        ['name' => 'Namara Honest', 'email' => 'honestnamara42@gmail.com'],
        ['name' => 'Musiimenta Alfred Marvin', 'email' => 'alfredomarvinez@gmail.com', 'position' => ClubPosition::ChiefWhip, 'role' => ClubRole::FinancialVerifier],
        ['name' => 'Ishimwe Mark', 'email' => 'markishimwe7@gmail.com', 'position' => ClubPosition::AssistantChiefWhip, 'role' => ClubRole::FinancialVerifier],
        ['name' => 'Ssegawa Kibombo', 'email' => 'ismailseis156@gmail.com'],
        ['name' => 'Ndagije Ronald', 'email' => 'ndagijeronnie@gmail.com'],
        ['name' => 'Feta Jeff Owen', 'email' => 'fetaowen@gmail.com', 'position' => ClubPosition::Mobilizer],
        ['name' => 'Nuwagaba Michael Kanyima', 'email' => 'kmnuwagaba@gmail.com', 'position' => ClubPosition::AssistantGeneralSecretary, 'role' => ClubRole::Secretary],
        ['name' => 'Lubega James Benjamin', 'email' => 'jamesbenjaminmarvin@gmail.com', 'position' => ClubPosition::AssistantTreasurer, 'role' => ClubRole::Treasurer],
        ['name' => 'Odongkara Fred Ojok', 'email' => 'fredodongkara18@gmail.com'],
        ['name' => 'Kiwanuka Joseph', 'email' => 'josephkiwanuka871@gmail.com'],
        ['name' => 'Ariko Shaun Opio', 'email' => 'shaunopio44@gmail.com', 'position' => ClubPosition::GeneralSecretary, 'role' => ClubRole::Secretary],
        ['name' => 'Luate Simon Jackson', 'email' => 'jacksonsimeon17@gmail.com'],
        ['name' => 'Turyakira Trevor', 'email' => 'trevorturyakira78@gmail.com'],
        ['name' => 'Gimei Jude Tadeo', 'email' => 'gimeijude75@gmail.com'],
        ['name' => 'Rwothomio Paul', 'email' => 'rwothomiopaul0@gmail.com'],
        ['name' => 'Tumwine John Esau', 'email' => 'johnesaut@gmail.com', 'position' => ClubPosition::AssistantMobilizer],
    ];

    public function run(): void
    {
        throw_if(
            ContributionPeriod::query()->where('year', '<', 2026)
                ->orWhere(fn ($query) => $query->where('year', 2026)->where('month', '<', 8))->exists()
                || Payment::query()->where('reference', 'like', 'MM-PENDING-%')->exists(),
            RuntimeException::class,
            'Existing pre-August or demo history requires review before importing the opening roll. Use a clean database; this seeder does not delete financial records.',
        );

        $notifications = Notification::getFacadeRoot();
        Notification::fake();

        try {
            DB::transaction(function (): void {
                $this->seedMembers();
                $this->seedContributionPeriods();
                $this->seedAugustContributions();
            });
        } finally {
            Notification::swap($notifications);
        }
    }

    private function seedMembers(): void
    {
        foreach (self::MEMBERS as $index => $definition) {
            $memberNumber = sprintf('MN-%04d', $index + 1);
            $member = Member::query()->where('member_number', $memberNumber)->first();
            $user = $member->user ?? User::query()->firstOrNew(['email' => $definition['email']]);

            if (! $user->exists) {
                $user->forceFill(['password' => self::PASSWORD]);
            }

            $user->forceFill([
                'email' => $definition['email'],
                'name' => $definition['name'],
            ])->save();

            if ($member === null) {
                $member = Member::query()->create([
                    'member_number' => $memberNumber,
                    'user_id' => $user->id,
                    'full_name' => $definition['name'],
                    'position' => $definition['position'] ?? null,
                    'phone' => '',
                    'joined_at' => self::STARTED_ON,
                    'status' => MemberStatus::Active,
                ]);

                MembershipStatusHistory::query()->create([
                    'member_id' => $member->id,
                    'from_status' => null,
                    'to_status' => MemberStatus::Active,
                    'reason' => 'Opening membership roll for August 2026.',
                    'effective_date' => self::STARTED_ON,
                ]);

                if (($definition['position'] ?? null) instanceof ClubPosition) {
                    PositionHolding::query()->create([
                        'member_id' => $member->id,
                        'position' => $definition['position'],
                        'held_from' => self::STARTED_ON,
                        'transfer_reason' => 'Opening office holder.',
                    ]);
                }

                $user->assignRole(($definition['role'] ?? ClubRole::Member)->value);
            } else {
                $member->update(['full_name' => $definition['name'], 'user_id' => $user->id]);
            }
        }
    }

    private function seedContributionPeriods(): void
    {
        foreach ([8, 9, 10] as $month) {
            if (ContributionPeriod::query()->where('year', 2026)->where('month', $month)->exists()) {
                continue;
            }

            resolve(OpenContributionPeriod::class)->handle(2026, $month);
        }
    }

    private function seedAugustContributions(): void
    {
        $period = ContributionPeriod::query()->where('year', 2026)->where('month', 8)->firstOrFail();

        foreach (self::AUGUST_CONTRIBUTIONS as $sequence => $amount) {
            $reference = sprintf('OPENING-202608-MN-%04d', $sequence);

            if (Payment::query()->where('reference', $reference)->exists()) {
                continue;
            }

            $member = Member::query()->where('member_number', sprintf('MN-%04d', $sequence))->firstOrFail();
            $obligation = MemberObligation::query()->where('contribution_period_id', $period->id)
                ->where('member_id', $member->id)->firstOrFail();

            throw_if($obligation->amount_paid !== 0, RuntimeException::class, 'August already contains payments. Reconcile them before importing opening contributions.');

            $payment = Payment::query()->create([
                'member_id' => $member->id,
                'amount' => $amount,
                'unapplied_amount' => 0,
                'paid_on' => '2026-08-31',
                'method' => PaymentMethod::MobileMoney,
                'reference' => $reference,
                'status' => PaymentStatus::Verified,
                'notes' => 'August opening contribution imported from the report dated 1 September 2026 and member-owner corrections. August 31 is an accounting date, not a known transaction date. Reference is an import identifier, not a Mobile Money receipt. Trevor and James later cleared August. Individual transaction dates and verification officers were not supplied. Excludes the UGX 20,295 unallocated withdrawal-charge balance.',
            ]);

            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'member_obligation_id' => $obligation->id,
                'amount' => $amount,
            ]);

            $obligation->update([
                'amount_paid' => $amount,
                'status' => $amount === $obligation->amount ? ObligationStatus::Paid : ObligationStatus::PartiallyPaid,
            ]);

            resolve(RecordAuditEvent::class)->handle('payment.opening_balance_imported', $payment, null, null, $payment->toArray());
        }
    }
}
