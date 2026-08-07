<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\ApproveExpense;
use App\Actions\OpenContributionPeriod;
use App\Actions\RecordExpensePayment;
use App\Actions\RequestExpense;
use App\Actions\VerifyExpense;
use App\Actions\VerifyPayment;
use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExternalAccountType;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\ExternalAccount;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;
use App\Models\PositionHolding;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Seeds Musuwa Nation's founding membership and a year-to-date trading history.
 *
 * The club started on 1 January 2026, so this also opens every contribution
 * period since then and settles most of them, giving the dashboards, ledgers
 * and monthly reports something real to show.
 *
 * Contact details are deliberate placeholders — sequential phone numbers and
 * addresses on the reserved `.test` domain, which can never receive mail.
 * Replace them with real details before the pilot; see mnic.md §3.1.
 */
final class MusuwaNationSeeder extends Seeder
{
    private const string PASSWORD = 'password';

    private const string FOUNDED_ON = '2026-01-01';

    /**
     * Members in three in arrears, so the arrears figures are not all zero.
     */
    private const int MEMBERS_IN_ARREARS = 3;

    /**
     * The full active roll. `position` is the office the member currently
     * holds, and `role` is the permission set that office needs — see
     * App\Enums\ClubPosition for why those are separate.
     *
     * @var list<array{name: string, position?: ClubPosition, role?: ClubRole, email?: string}>
     */
    private const array MEMBERS = [
        ['name' => 'Fredrick Ssekweyama', 'position' => ClubPosition::Chairperson, 'role' => ClubRole::InterimChairperson],
        ['name' => 'Conrad Tumwijukye', 'position' => ClubPosition::ViceChairperson, 'role' => ClubRole::InterimChairperson],
        // Also the application administrator, so somebody can manage accounts
        // and settings from day one.
        ['name' => 'Lemi Manoah', 'position' => ClubPosition::Treasurer, 'role' => ClubRole::Administrator, 'email' => 'lemi@gmail.com'],
        ['name' => 'John Collin Nyero'],
        ['name' => 'Honest Namara'],
        // The whips hold the verification role, which keeps the treasurer from
        // both recording and verifying the same payment.
        ['name' => 'Alfred Musimenta', 'position' => ClubPosition::ChiefWhip, 'role' => ClubRole::FinancialVerifier],
        ['name' => 'Mark Ishimwe', 'position' => ClubPosition::AssistantChiefWhip, 'role' => ClubRole::FinancialVerifier],
        ['name' => 'Ismail Ssegawa'],
        ['name' => 'Ronald Ndagije'],
        ['name' => 'Feta Jeff Owen', 'position' => ClubPosition::Mobilizer],
        ['name' => 'Michael Nuwagaba', 'position' => ClubPosition::AssistantGeneralSecretary, 'role' => ClubRole::Secretary],
        ['name' => 'James Benjamin Lubega', 'position' => ClubPosition::AssistantTreasurer, 'role' => ClubRole::Treasurer],
        ['name' => 'Fred Odongkara'],
        ['name' => 'Joseph Kiwanuka'],
        ['name' => 'Shaun Ariko', 'position' => ClubPosition::GeneralSecretary, 'role' => ClubRole::Secretary],
        ['name' => 'Simon Jackson Luate'],
        ['name' => 'Trevor Turyakira'],
        ['name' => 'Gimei Jude Tadeo'],
        ['name' => 'Paul Rwothomio'],
        ['name' => 'John Esau Tumwine', 'position' => ClubPosition::AssistantMobilizer],
    ];

    public function run(): void
    {
        $this->seedMembers();
        $this->seedContributionHistory();
        $this->seedExpense();
    }

    private function seedMembers(): void
    {
        foreach (self::MEMBERS as $index => $definition) {
            $sequence = $index + 1;

            $user = User::query()->firstOrCreate(
                ['email' => $definition['email'] ?? $this->emailFor($definition['name'], $sequence)],
                [
                    'name' => $definition['name'],
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                ],
            );

            $member = Member::query()->firstOrCreate(
                ['member_number' => sprintf('MN-%04d', $sequence)],
                [
                    'user_id' => $user->id,
                    'full_name' => $definition['name'],
                    'position' => $definition['position'] ?? null,
                    'phone' => sprintf('+2567000000%02d', $sequence),
                    'joined_at' => self::FOUNDED_ON,
                    'status' => MemberStatus::Active,
                ],
            );

            MembershipStatusHistory::query()->firstOrCreate(
                ['member_id' => $member->id, 'to_status' => MemberStatus::Active],
                [
                    'from_status' => null,
                    'reason' => 'Founding member of Musuwa Nation.',
                    'effective_date' => self::FOUNDED_ON,
                ],
            );

            if (($definition['position'] ?? null) instanceof ClubPosition) {
                PositionHolding::query()->firstOrCreate(
                    [
                        'member_id' => $member->id,
                        'position' => $definition['position']->value,
                        'held_to' => null,
                    ],
                    [
                        'held_from' => self::FOUNDED_ON,
                        'transfer_reason' => 'Founding office holder.',
                    ],
                );
            }

            $user->syncRoles([($definition['role'] ?? ClubRole::Member)->value]);
        }
    }

    /**
     * Opens every month from the founding date to today, and settles all of
     * them except the current one — leaving a few members behind on the most
     * recent closed month so arrears are visible.
     */
    private function seedContributionHistory(): void
    {
        $treasurer = $this->memberAt(ClubPosition::AssistantTreasurer);
        $verifier = $this->memberAt(ClubPosition::ChiefWhip);

        if (! $treasurer instanceof Member || ! $verifier instanceof Member) {
            return;
        }

        $openPeriod = resolve(OpenContributionPeriod::class);
        $verifyPayment = resolve(VerifyPayment::class);

        $start = CarbonImmutable::parse(self::FOUNDED_ON);
        $today = CarbonImmutable::now();

        // Everybody except the last few pays on time; those few fall behind.
        $payers = Member::query()
            ->orderBy('member_number')
            ->get()
            ->slice(0, max(count(self::MEMBERS) - self::MEMBERS_IN_ARREARS, 0));

        for ($cursor = $start; $cursor->lessThanOrEqualTo($today); $cursor = $cursor->addMonth()) {
            $existing = ContributionPeriod::query()
                ->where('year', $cursor->year)
                ->where('month', $cursor->month)
                ->first();

            // Re-running the seeder must not blow up on periods that already
            // exist, and must not double up the payments against them.
            if ($existing !== null) {
                continue;
            }

            $period = $openPeriod->handle($cursor->year, $cursor->month, $treasurer);

            // The current month stays open and unpaid.
            if ($cursor->isSameMonth($today)) {
                continue;
            }

            foreach ($payers as $offset => $member) {
                $payment = Payment::query()->create([
                    'member_id' => $member->id,
                    'amount' => $period->amount,
                    'unapplied_amount' => 0,
                    'paid_on' => $cursor->addDays(2)->toDateString(),
                    'method' => PaymentMethod::MobileMoney,
                    'reference' => sprintf('MM-%04d%02d-%03d', $cursor->year, $cursor->month, $offset + 1),
                    'status' => PaymentStatus::Submitted,
                    'recorded_by_member_id' => $treasurer->id,
                ]);

                $verifyPayment->handle($payment, $verifier);
            }
        }
    }

    /**
     * One expense taken all the way through request, approval, payment and
     * verification, so the expense screens are not empty.
     */
    private function seedExpense(): void
    {
        $secretary = $this->memberAt(ClubPosition::GeneralSecretary);
        $chair = $this->memberAt(ClubPosition::Chairperson);
        $verifier = $this->memberAt(ClubPosition::AssistantChiefWhip);

        if (! $secretary instanceof Member || ! $chair instanceof Member || ! $verifier instanceof Member) {
            return;
        }

        $account = ExternalAccount::query()->firstOrCreate(
            ['name' => 'Musuwa Nation Collection Account'],
            [
                'type' => ExternalAccountType::MobileMoney,
                'institution' => 'MTN Mobile Money',
                'masked_identifier' => '****4021',
                'is_active' => true,
            ],
        );

        if (Expense::query()->where('reference', 'EXP-0001')->exists()) {
            return;
        }

        $expense = resolve(RequestExpense::class)->handle([
            'reference' => 'EXP-0001',
            'purpose' => 'Club registration and stamp duty',
            'category' => ExpenseCategory::Statutory->value,
            'payee' => 'URSB',
            'amount' => 150000,
            'incurred_on' => '2026-02-10',
        ], $secretary);

        resolve(ApproveExpense::class)->handle($expense, $chair);

        resolve(RecordExpensePayment::class)
            ->handle($expense, $account->id, 'MM-REG-0001', '2026-02-12', $secretary);

        resolve(VerifyExpense::class)->handle($expense->fresh(), $verifier);
    }

    private function memberAt(ClubPosition $position): ?Member
    {
        return Member::query()->firstWhere('position', $position->value);
    }

    /**
     * A stable, obviously-fake address on the reserved `.test` TLD.
     */
    private function emailFor(string $fullName, int $sequence): string
    {
        $slug = str($fullName)->lower()->replaceMatches('/[^a-z]+/', '.')->trim('.')->value();

        return sprintf('%s.%02d@musuwanation.test', $slug, $sequence);
    }
}
