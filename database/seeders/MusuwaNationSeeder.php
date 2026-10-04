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
use App\Models\OpeningWithdrawalFee;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PositionHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

final class MusuwaNationSeeder extends Seeder
{
    private const string PASSWORD = 'password';

    private const string STARTED_ON = '2026-08-01';

    /** @var array<int, string> */
    private const array MEMBER_NUMBERS = [
        1 => '001', 2 => '004', 3 => '003', 4 => '015', 5 => '011',
        6 => '016', 7 => '019', 8 => '010', 9 => '009', 10 => '006',
        11 => '007', 12 => '002', 13 => '012', 14 => '020', 15 => '017',
        16 => '008', 17 => '014', 18 => '018', 19 => '013', 20 => '005',
    ];

    /** @var array<int, int> */
    private const array AUGUST_CONTRIBUTIONS = [
        1 => 60000, 2 => 60000, 3 => 60000, 4 => 60000, 5 => 60000,
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

    /** @var array<int, Member> */
    private array $membersBySequence = [];

    /** @var array<int, string> */
    private array $originalNumbers = [];

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
                $this->seedAugustWithdrawalFees();
            });
        } finally {
            Notification::swap($notifications);
        }
    }

    private function seedMembers(): void
    {
        $this->membersBySequence = [];
        $this->originalNumbers = [];

        foreach (self::MEMBERS as $index => $definition) {
            $names = [$definition['name']];

            if ($definition['name'] === 'Ssekweyama Fredrick') {
                $names[] = 'Fredrick Ssekweyama';
            }

            $matches = Member::query()->with('user')
                ->where(fn (Builder $query): Builder => $query
                    ->whereHas('user', fn (Builder $user): Builder => $user->where('email', $definition['email']))
                    ->orWhereIn('full_name', $names))
                ->lockForUpdate()->get();

            throw_if($matches->count() > 1, RuntimeException::class, 'Ambiguous member identity: '.$definition['name']);
            $member = $matches->first();

            if ($member !== null) {
                $this->membersBySequence[$index + 1] = $member;
                $this->originalNumbers[$index + 1] = $member->member_number;
            }
        }

        $memberIds = array_map(static fn (Member $member): string => $member->id, $this->membersBySequence);
        throw_if(
            Member::query()->whereIn('member_number', array_values(self::MEMBER_NUMBERS))->whereNotIn('id', $memberIds)->exists(),
            RuntimeException::class,
            'A requested member number belongs to an unrelated member. Resolve the conflict before importing.',
        );

        foreach ($this->membersBySequence as $sequence => $member) {
            if ($member->member_number !== self::MEMBER_NUMBERS[$sequence]) {
                $member->update(['member_number' => 'RENUMBER-'.$member->id]);
            }
        }

        foreach (self::MEMBERS as $index => $definition) {
            $sequence = $index + 1;
            $memberNumber = self::MEMBER_NUMBERS[$sequence];
            $member = $this->membersBySequence[$sequence] ?? null;
            $user = $member->user ?? User::query()->firstOrNew(['email' => $definition['email']]);

            if (! $user->exists) {
                $user->forceFill(['password' => self::PASSWORD]);
            }

            $user->forceFill(['email' => $definition['email'], 'name' => $definition['name']])->save();

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
                $originalNumber = $this->originalNumbers[$sequence] ?? throw new RuntimeException('Missing original member number during opening roll update.');
                $before = [...$member->toArray(), 'member_number' => $originalNumber];
                $member->update(['member_number' => $memberNumber, 'full_name' => $definition['name'], 'user_id' => $user->id]);
                if ($member->wasChanged(['member_number', 'full_name', 'user_id'])) {
                    resolve(RecordAuditEvent::class)->handle('member.opening_roll_updated', $member, null, $before, $member->toArray());
                }
            }

            $this->membersBySequence[$sequence] = $member;
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

            $member = $this->membersBySequence[$sequence];
            $existingPayment = Payment::query()->where('reference', $reference)->first();

            if ($existingPayment !== null) {
                throw_if($existingPayment->member_id !== $member->id, RuntimeException::class, 'An opening payment belongs to a different member. Review the import before continuing.');

                if (str_contains($existingPayment->notes ?? '', 'Excludes the UGX 20,295 unallocated withdrawal-charge balance.')) {
                    $existingPayment->update(['notes' => str_replace('The UGX 20,295 unallocated withdrawal fee is recorded separately as a club opening receipt.', 'The UGX 20,295 unallocated withdrawal fee is recorded separately as a club opening receipt.', $existingPayment->notes ?? '')]);
                }

                continue;
            }

            $obligation = MemberObligation::query()->where('contribution_period_id', $period->id)
                ->where('member_id', $member->id)->firstOrFail();

            throw_if($obligation->amount_paid !== 0, RuntimeException::class, 'August already contains payments. Reconcile them before importing opening contributions.');

            $payment = Payment::query()->create([
                'member_id' => $member->id,
                'contribution_period_id' => $period->id,
                'amount' => $amount,
                'unapplied_amount' => 0,
                'paid_on' => '2026-08-31',
                'method' => PaymentMethod::MobileMoney,
                'reference' => $reference,
                'status' => PaymentStatus::Verified,
                'notes' => 'August opening contribution imported from the report dated 1 September 2026 and member-owner corrections. August 31 is an accounting date, not a known transaction date. Reference is an import identifier, not a Mobile Money receipt. Trevor, James, and Namara Honest later cleared August. Individual transaction dates and verification officers were not supplied. The UGX 20,295 unallocated withdrawal fee is recorded separately as a club opening receipt.',
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

    private function seedAugustWithdrawalFees(): void
    {
        $period = ContributionPeriod::query()->where('year', 2026)->where('month', 8)->firstOrFail();
        $receipt = OpeningWithdrawalFee::query()->firstOrCreate(
            ['reference' => 'OPENING-202608-WITHDRAWAL-FEES'],
            [
                'contribution_period_id' => $period->id,
                'amount' => 20295,
                'paid_on' => '2026-08-31',
                'notes' => 'Aggregate withdrawal fee received through Mobile Money by Lubega James, from the report dated 1 September 2026. Individual member allocations and actual payment dates were not supplied. August 31 is an accounting date. This is money received, not an expense. No additional fees are assumed for Trevor, James, or Namara Honest later clearing August.',
            ],
        );

        throw_if($receipt->amount !== 20295 || $receipt->contribution_period_id !== $period->id, RuntimeException::class, 'The August opening withdrawal fee differs from the report. Review it before importing.');

        if ($receipt->wasRecentlyCreated) {
            resolve(RecordAuditEvent::class)->handle('withdrawal_fee.opening_balance_imported', $receipt, null, null, $receipt->toArray());
        }
    }
}
