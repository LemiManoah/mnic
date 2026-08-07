<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\ApproveExpense;
use App\Actions\CastPositionPollVote;
use App\Actions\CastVote;
use App\Actions\ClosePositionPollVoting;
use App\Actions\CloseProposalVoting;
use App\Actions\ConfirmMinutes;
use App\Actions\CreateActionItem;
use App\Actions\CreatePositionPoll;
use App\Actions\NominatePositionPollCandidate;
use App\Actions\OpenContributionPeriod;
use App\Actions\OpenPositionPollVoting;
use App\Actions\OpenProposalVoting;
use App\Actions\PublishMinutes;
use App\Actions\RecordExpensePayment;
use App\Actions\RecordMeetingAttendance;
use App\Actions\RejectExpense;
use App\Actions\RejectPayment;
use App\Actions\RequestExpense;
use App\Actions\RequestPaymentReversal;
use App\Actions\ScheduleMeeting;
use App\Actions\UpdateActionItemStatus;
use App\Actions\VerifyExpense;
use App\Actions\VerifyPayment;
use App\Enums\ActionItemStatus;
use App\Enums\AttendanceStatus;
use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExternalAccountType;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\ExternalAccount;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use App\Models\Proposal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Musuwa Nation's founding membership and a year-to-date trading history.
 *
 * The club started on 1 January 2026, so this also opens every contribution
 * period since then and settles most of them, giving the dashboards, ledgers
 * and monthly reports something real to show.
 *
 * Contact details are deliberate placeholders — sequential phone numbers and
 * Gmail-style login addresses generated from the member's surname.
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
        $this->seedPendingPayments();
        $this->seedExpenses();
        $this->seedGovernance();
        $this->seedElections();
    }

    private function seedMembers(): void
    {
        foreach (self::MEMBERS as $index => $definition) {
            $sequence = $index + 1;
            $email = $definition['email'] ?? $this->emailFor($definition['name']);

            $user = User::query()->firstWhere('email', $email)
                ?? User::query()->firstWhere('name', $definition['name'])
                ?? new User;

            $user->forceFill(
                [
                    'email' => $email,
                    'name' => $definition['name'],
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                ],
            );
            $user->save();

            $member = Member::query()->updateOrCreate(
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
            ->limit(max(count(self::MEMBERS) - self::MEMBERS_IN_ARREARS, 0))
            ->get();

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

            // One transaction per month rather than one per payment. On SQLite
            // that is the difference between a few hundred lock acquisitions
            // and a handful.
            DB::transaction(function () use ($payers, $period, $cursor, $treasurer, $verifier, $verifyPayment): void {
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
            });
        }
    }

    /**
     * Payments sitting in every reviewable state, so the verify, reject and
     * reversal controls on the payments screen all have something to act on.
     * Without these the buttons never render and the screen looks read-only.
     */
    private function seedPendingPayments(): void
    {
        $treasurer = $this->memberAt(ClubPosition::AssistantTreasurer);
        $verifier = $this->memberAt(ClubPosition::ChiefWhip);

        if (! $treasurer instanceof Member || ! $verifier instanceof Member) {
            return;
        }

        if (Payment::query()->where('reference', 'MM-PENDING-001')->exists()) {
            return;
        }

        $current = ContributionPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (! $current instanceof ContributionPeriod) {
            return;
        }

        // Three awaiting a second officer's decision. The treasurer recorded
        // them, so the treasurer cannot be the one who verifies — that is the
        // maker-checker rule the screen is there to enforce.
        foreach (range(1, 3) as $offset => $sequence) {
            Payment::query()->create([
                'member_id' => $this->memberNumbered($sequence)->id,
                'amount' => $current->amount,
                'unapplied_amount' => 0,
                'paid_on' => CarbonImmutable::now()->subDays($offset + 1)->toDateString(),
                'method' => PaymentMethod::MobileMoney,
                'reference' => sprintf('MM-PENDING-%03d', $offset + 1),
                'status' => PaymentStatus::Submitted,
                'recorded_by_member_id' => $treasurer->id,
            ]);
        }

        // One already rejected, so the rejection reason has somewhere to show.
        $rejected = Payment::query()->create([
            'member_id' => $this->memberNumbered(4)->id,
            'amount' => $current->amount,
            'unapplied_amount' => 0,
            'paid_on' => CarbonImmutable::now()->subDays(6)->toDateString(),
            'method' => PaymentMethod::Cash,
            'reference' => 'MM-PENDING-004',
            'status' => PaymentStatus::Submitted,
            'recorded_by_member_id' => $treasurer->id,
        ]);

        resolve(RejectPayment::class)
            ->handle($rejected, $verifier, 'No deposit slip attached; please resubmit with evidence.');

        // One verified and then put up for reversal, waiting on a second
        // officer to approve or decline it.
        $toReverse = Payment::query()->create([
            'member_id' => $this->memberNumbered(5)->id,
            'amount' => $current->amount,
            'unapplied_amount' => 0,
            'paid_on' => CarbonImmutable::now()->subDays(8)->toDateString(),
            'method' => PaymentMethod::BankTransfer,
            'reference' => 'MM-PENDING-005',
            'status' => PaymentStatus::Submitted,
            'recorded_by_member_id' => $treasurer->id,
        ]);

        resolve(VerifyPayment::class)->handle($toReverse, $verifier);

        resolve(RequestPaymentReversal::class)->handle(
            $toReverse->fresh() ?? $toReverse,
            $verifier,
            'Paid twice in the same week — the earlier reference already covers this month.',
        );
    }

    /**
     * Expenses spread across the whole approval chain, so every stage of the
     * expense screen has a row and each action button is reachable.
     */
    private function seedExpenses(): void
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

        ExternalAccount::query()->firstOrCreate(
            ['name' => 'Musuwa Nation Savings Account'],
            [
                'type' => ExternalAccountType::Bank,
                'institution' => 'Stanbic Bank Uganda',
                'masked_identifier' => '****8834',
                'is_active' => true,
            ],
        );

        if (Expense::query()->where('reference', 'EXP-0001')->exists()) {
            return;
        }

        $request = resolve(RequestExpense::class);

        // Fully settled: requested, approved, paid and verified.
        $settled = $request->handle([
            'reference' => 'EXP-0001',
            'purpose' => 'Club registration and stamp duty',
            'category' => ExpenseCategory::Statutory->value,
            'payee' => 'URSB',
            'amount' => 150000,
            'incurred_on' => '2026-02-10',
        ], $secretary);

        resolve(ApproveExpense::class)->handle($settled, $chair);
        resolve(RecordExpensePayment::class)
            ->handle($settled, $account->id, 'MM-REG-0001', '2026-02-12', $secretary);
        resolve(VerifyExpense::class)->handle($settled->fresh() ?? $settled, $verifier);

        // Paid but not yet verified, so the verify button has something to do.
        $awaitingVerification = $request->handle([
            'reference' => 'EXP-0002',
            'purpose' => 'Printing of member passbooks',
            'category' => ExpenseCategory::Other->value,
            'payee' => 'Kampala Print Works',
            'amount' => 85000,
            'incurred_on' => '2026-04-03',
        ], $secretary);

        resolve(ApproveExpense::class)->handle($awaitingVerification, $chair);
        resolve(RecordExpensePayment::class)
            ->handle($awaitingVerification, $account->id, 'MM-PRT-0002', '2026-04-05', $secretary);

        // Approved but unpaid, so the record-payment button has something to do.
        $awaitingPayment = $request->handle([
            'reference' => 'EXP-0003',
            'purpose' => 'Venue hire for the half-year general meeting',
            'category' => ExpenseCategory::Meeting->value,
            'payee' => 'Ntinda Community Hall',
            'amount' => 120000,
            'incurred_on' => '2026-06-20',
        ], $secretary);

        resolve(ApproveExpense::class)->handle($awaitingPayment, $chair);

        // Still awaiting approval, so approve and reject both show.
        $request->handle([
            'reference' => 'EXP-0004',
            'purpose' => 'Bank charges and statement fees',
            'category' => ExpenseCategory::BankCharges->value,
            'payee' => 'Stanbic Bank Uganda',
            'amount' => 32000,
            'incurred_on' => CarbonImmutable::now()->subDays(4)->toDateString(),
        ], $secretary);

        // Rejected, so the rejection reason has somewhere to render.
        $rejected = $request->handle([
            'reference' => 'EXP-0005',
            'purpose' => 'Team lunch after the quarterly meeting',
            'category' => ExpenseCategory::Welfare->value,
            'payee' => 'Cafe Javas',
            'amount' => 240000,
            'incurred_on' => '2026-05-18',
        ], $secretary);

        resolve(RejectExpense::class)
            ->handle($rejected, $chair, 'Not budgeted for this quarter; bring it to the next meeting.');
    }

    /**
     * Meetings, minutes, proposals and action items, so the governance screens
     * show a full cycle rather than empty tables.
     */
    private function seedGovernance(): void
    {
        $secretary = $this->memberAt(ClubPosition::GeneralSecretary);
        $chair = $this->memberAt(ClubPosition::Chairperson);

        if (! $secretary instanceof Member || ! $chair instanceof Member) {
            return;
        }

        if (Meeting::query()->where('reference', 'MTG-2026-001')->exists()) {
            return;
        }

        $members = Member::query()->orderBy('member_number')->get();
        $scheduleMeeting = resolve(ScheduleMeeting::class);

        // A past meeting with attendance and confirmed minutes.
        $held = $scheduleMeeting->handle([
            'reference' => 'MTG-2026-001',
            'title' => 'Inaugural general meeting',
            'scheduled_for' => '2026-01-15 18:00:00',
            'location' => 'Ntinda Community Hall',
            'agenda' => "1. Adoption of the constitution\n2. Confirmation of interim officers\n3. Contribution amount and due dates",
        ], $secretary);

        // Most turn up, a couple send apologies, the rest are absent.
        $attendance = [];

        foreach ($members as $index => $member) {
            $attendance[$member->id] = match (true) {
                $index >= 18 => AttendanceStatus::Absent->value,
                $index >= 16 => AttendanceStatus::Apologies->value,
                default => AttendanceStatus::Present->value,
            };
        }

        resolve(RecordMeetingAttendance::class)->handle($held, $attendance, $secretary);

        $minute = resolve(PublishMinutes::class)->handle(
            $held,
            "The chairperson opened the meeting at 18:05.\n\n".
            "1. The draft constitution was read and adopted without amendment.\n".
            "2. Interim officers were confirmed to serve until the first election.\n".
            "3. The monthly contribution was set at UGX 60,000, due by the 5th with a grace period to the 10th.\n\n".
            'The meeting closed at 19:40.',
            $secretary,
        );

        resolve(ConfirmMinutes::class)->handle($minute, $chair);

        // An upcoming meeting, so the dashboard's next-meeting card fills in.
        $scheduleMeeting->handle([
            'reference' => 'MTG-2026-002',
            'title' => 'Half-year review',
            'scheduled_for' => CarbonImmutable::now()->addWeeks(2)->setTime(18, 0)->toDateTimeString(),
            'location' => 'Ntinda Community Hall',
            'agenda' => "1. Treasurer's half-year report\n2. Land acquisition proposal\n3. Any other business",
        ], $secretary);

        $this->seedProposals($held, $chair, $secretary, $members);
        $this->seedActionItems($held, $secretary);
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    private function seedProposals(Meeting $meeting, Member $chair, Member $secretary, Collection $members): void
    {
        // A motion that passed. Individual votes only become visible once a
        // proposal is closed, so this is what populates the votes table.
        $passed = Proposal::query()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Adopt UGX 60,000 as the monthly contribution',
            'description' => 'Set the standing monthly contribution at UGX 60,000 per member, due by the 5th of each month with a grace period to the 10th.',
            'status' => ProposalStatus::Draft,
            'created_by_member_id' => $chair->id,
        ]);

        resolve(OpenProposalVoting::class)
            ->handle($passed, CarbonImmutable::now()->addWeek()->toDateTimeString(), $secretary);

        $castVote = resolve(CastVote::class);

        foreach ($members->take(16) as $index => $member) {
            $castVote->handle($passed, $member, match (true) {
                $index >= 14 => VoteChoice::Abstain,
                $index >= 12 => VoteChoice::Against,
                default => VoteChoice::For,
            });
        }

        resolve(CloseProposalVoting::class)->handle($passed->fresh() ?? $passed, $secretary);

        // An open motion, so members actually have something to vote on.
        $open = Proposal::query()->create([
            'title' => 'Acquire a plot in Wakiso',
            'description' => 'Commit up to UGX 12,000,000 from club funds towards a residential plot in Wakiso, subject to a valuation report and a clean land search.',
            'status' => ProposalStatus::Draft,
            'created_by_member_id' => $chair->id,
        ]);

        resolve(OpenProposalVoting::class)
            ->handle($open, CarbonImmutable::now()->addWeeks(2)->toDateTimeString(), $secretary);

        // A partial turnout, so the result is genuinely still undecided.
        foreach ($members->take(5) as $index => $member) {
            $castVote->handle($open, $member, $index >= 4 ? VoteChoice::Against : VoteChoice::For);
        }

        // A draft, so the open-voting control has something to act on.
        Proposal::query()->create([
            'title' => 'Introduce a joining fee for new members',
            'description' => 'Charge a one-off joining fee of UGX 100,000 for members admitted after the founding cohort.',
            'status' => ProposalStatus::Draft,
            'created_by_member_id' => $chair->id,
        ]);
    }

    private function seedActionItems(Meeting $meeting, Member $secretary): void
    {
        $create = resolve(CreateActionItem::class);
        $updateStatus = resolve(UpdateActionItemStatus::class);

        $completed = $create->handle([
            'meeting_id' => $meeting->id,
            'title' => 'Open the club mobile money account',
            'description' => 'Register a dedicated MTN Mobile Money account in the club name.',
            'owner_member_id' => $this->memberNumbered(3)->id,
            'due_on' => '2026-01-31',
        ], $secretary);

        $updateStatus->handle($completed, ActionItemStatus::Completed, $secretary);

        $inProgress = $create->handle([
            'meeting_id' => $meeting->id,
            'title' => 'Collect land search reports for three Wakiso plots',
            'description' => 'Obtain title searches so the acquisition proposal can be costed properly.',
            'owner_member_id' => $this->memberNumbered(10)->id,
            'due_on' => CarbonImmutable::now()->addWeeks(3)->toDateString(),
        ], $secretary);

        $updateStatus->handle($inProgress, ActionItemStatus::InProgress, $secretary);

        $blocked = $create->handle([
            'meeting_id' => $meeting->id,
            'title' => 'Draft the welfare policy',
            'description' => 'Set out what the club contributes towards bereavement and medical emergencies.',
            'owner_member_id' => $this->memberNumbered(15)->id,
            'due_on' => CarbonImmutable::now()->addMonth()->toDateString(),
        ], $secretary);

        $updateStatus->handle($blocked, ActionItemStatus::Blocked, $secretary);

        // Left open and already past its due date, so an overdue row is visible.
        $create->handle([
            'meeting_id' => $meeting->id,
            'title' => 'Circulate the signed constitution to all members',
            'description' => 'Share a scanned copy with every member and file the original.',
            'owner_member_id' => $this->memberNumbered(6)->id,
            'due_on' => CarbonImmutable::now()->subWeeks(2)->toDateString(),
        ], $secretary);
    }

    /**
     * One election in each state, so the Elections screen shows a decided race,
     * a live ballot members can vote in, and a draft still taking nominations.
     */
    private function seedElections(): void
    {
        $secretary = $this->memberAt(ClubPosition::GeneralSecretary);

        if (! $secretary instanceof Member) {
            return;
        }

        if (PositionPoll::query()->exists()) {
            return;
        }

        $members = Member::query()->orderBy('member_number')->get();

        $createPoll = resolve(CreatePositionPoll::class);
        $nominate = resolve(NominatePositionPollCandidate::class);
        $openVoting = resolve(OpenPositionPollVoting::class);
        $castVote = resolve(CastPositionPollVote::class);

        // A live race, with a real contest between the sitting mobilizer and a
        // challenger. Nominating returns the candidate row, so the ballot is
        // built from those objects rather than re-querying and guessing order.
        $live = $createPoll->handle(
            ClubPosition::Mobilizer,
            'Mobilizer election 2026',
            'The mobilizer drives turnout at meetings and follows up on arrears.',
            $secretary,
        );

        $sittingMobilizer = $nominate->handle(
            $live,
            $this->memberNumbered(10),
            'I will call every member personally before each meeting.',
            $secretary,
        );

        $mobilizerChallenger = $nominate->handle(
            $live,
            $this->memberNumbered(17),
            'Attendance has slipped. I will publish a monthly turnout table.',
            $secretary,
        );

        $openVoting->handle($live, CarbonImmutable::now()->addWeek()->toDateTimeString(), $secretary);

        // A partial turnout, so the race is genuinely still undecided and the
        // remaining members still have a vote to cast in the UI.
        foreach ($members->take(7) as $index => $member) {
            $castVote->handle(
                $live,
                $member,
                $index >= 4 ? $mobilizerChallenger : $sittingMobilizer,
            );
        }

        // A settled race, so the results view, the vote counts and the outcome
        // note all have content. The sitting whip is returned: the founding
        // roster is the club's own list, so seeded data must not quietly move
        // somebody out of the office they were named to.
        $decided = $createPoll->handle(
            ClubPosition::AssistantChiefWhip,
            'Assistant Chief Whip confirmation',
            'Confirming the founding appointment for a full term.',
            $secretary,
        );

        $sittingWhip = $nominate->handle(
            $decided,
            $this->memberNumbered(7),
            'I will keep the register and enforce the standing orders.',
            $secretary,
        );

        $whipChallenger = $nominate->handle(
            $decided,
            $this->memberNumbered(13),
            'I will publish a discipline summary after every meeting.',
            $secretary,
        );

        $openVoting->handle($decided, CarbonImmutable::now()->addDay()->toDateTimeString(), $secretary);

        foreach ($members->take(14) as $index => $member) {
            $castVote->handle($decided, $member, $index >= 9 ? $whipChallenger : $sittingWhip);
        }

        resolve(ClosePositionPollVoting::class)->handle($decided->fresh() ?? $decided, $secretary);

        // A draft still taking nominations, so the nominate control is reachable.
        $draft = $createPoll->handle(
            ClubPosition::ViceChairperson,
            'Vice Chairperson election 2027',
            'Opens for voting once nominations close at the half-year meeting.',
            $secretary,
        );

        $nominate->handle($draft, $this->memberNumbered(2), 'Standing for a second term.', $secretary);
    }

    private function memberAt(ClubPosition $position): ?Member
    {
        return Member::query()->firstWhere('position', $position->value);
    }

    /**
     * Look a member up by their place on the roll above, one-indexed.
     *
     * Picking by member number rather than by array offset means the seeded
     * candidates and owners stay pointed at the same people if the roster is
     * ever reordered.
     */
    private function memberNumbered(int $sequence): Member
    {
        return Member::query()
            ->where('member_number', sprintf('MN-%04d', $sequence))
            ->firstOrFail();
    }

    /**
     * A simple login address based on the final word in the member's name.
     */
    private function emailFor(string $fullName): string
    {
        $surname = str($fullName)->lower()->replaceMatches('/[^a-z]+/', ' ')->trim()->explode(' ')->last();

        return sprintf('%s@gmail.com', $surname);
    }
}
