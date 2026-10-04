<?php

declare(strict_types=1);

use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\ContributionPeriodStatus;
use App\Enums\MemberStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\ActionItem;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\ExternalAccount;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\MembershipStatusHistory;
use App\Models\OpeningWithdrawalFee;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ClubCashPosition;
use App\Services\MonthlyReportData;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MusuwaNationSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('seeds the supplied twenty members with their real login addresses', function (): void {
    $expected = [
        'Feta Jeff Owen' => 'fetaowen@gmail.com',
        'Namara Honest' => 'honestnamara42@gmail.com',
        'Tumwine John Esau' => 'johnesaut@gmail.com',
        'Odongkara Fred Ojok' => 'fredodongkara18@gmail.com',
        'Turyakira Trevor' => 'trevorturyakira78@gmail.com',
        'Rwothomio Paul' => 'rwothomiopaul0@gmail.com',
        'Ssekweyama Fredrick' => 'mubuukefredrick24@gmail.com',
        'Tumwijukye Conrad' => 'tumwijukyeconrad99@gmail.com',
        'Ariko Shaun Opio' => 'shaunopio44@gmail.com',
        'Nyero John' => 'johnnyero02@gmail.com',
        'Ishimwe Mark' => 'markishimwe7@gmail.com',
        'Luate Simon Jackson' => 'jacksonsimeon17@gmail.com',
        'Gimei Jude Tadeo' => 'gimeijude75@gmail.com',
        'Lubega James Benjamin' => 'jamesbenjaminmarvin@gmail.com',
        'Ndagije Ronald' => 'ndagijeronnie@gmail.com',
        'Ssegawa Kibombo' => 'ismailseis156@gmail.com',
        'Nuwagaba Michael Kanyima' => 'kmnuwagaba@gmail.com',
        'Musiimenta Alfred Marvin' => 'alfredomarvinez@gmail.com',
        'Kiwanuka Joseph' => 'josephkiwanuka871@gmail.com',
        'Lemi Manoah' => 'lemi.manoah@gmail.com',
    ];

    expect(User::query()->pluck('email', 'name')->all())->toEqual($expected)
        ->and(Member::query()->count())->toBe(20)
        ->and(MembershipStatusHistory::query()->count())->toBe(20);

    foreach (Member::query()->with('user')->get() as $member) {
        expect($member->status)->toBe(MemberStatus::Active)
            ->and($member->phone)->toBe('')
            ->and($member->joined_at->toDateString())->toBe('2026-08-01')
            ->and($member->user?->name)->toBe($member->full_name)
            ->and($member->user?->email_verified_at)->toBeNull()
            ->and(Hash::check('password', (string) $member->user?->password))->toBeTrue();
    }
});

it('retains the existing officer assignments and member numbers', function (): void {
    $expected = [
        'Ssekweyama Fredrick' => ClubPosition::Chairperson,
        'Tumwijukye Conrad' => ClubPosition::ViceChairperson,
        'Ariko Shaun Opio' => ClubPosition::GeneralSecretary,
        'Nuwagaba Michael Kanyima' => ClubPosition::AssistantGeneralSecretary,
        'Lemi Manoah' => ClubPosition::Treasurer,
        'Lubega James Benjamin' => ClubPosition::AssistantTreasurer,
        'Feta Jeff Owen' => ClubPosition::Mobilizer,
        'Tumwine John Esau' => ClubPosition::AssistantMobilizer,
        'Musiimenta Alfred Marvin' => ClubPosition::ChiefWhip,
        'Ishimwe Mark' => ClubPosition::AssistantChiefWhip,
    ];

    foreach ($expected as $name => $position) {
        expect(Member::query()->where('full_name', $name)->firstOrFail()->currentPosition())->toBe($position);
    }

    expect(Member::query()->where('full_name', 'Lemi Manoah')->firstOrFail()->member_number)->toBe('003')
        ->and(User::query()->where('email', 'lemi.manoah@gmail.com')->firstOrFail()->hasRole(ClubRole::Administrator))->toBeTrue()
        ->and(PositionHolding::query()->count())->toBe(10);
});

it('opens only August September and October 2026 with twenty obligations each', function (): void {
    expect(ContributionPeriod::query()->orderBy('month')->pluck('month')->all())->toBe([8, 9, 10])
        ->and(MemberObligation::query()->count())->toBe(60);

    foreach (ContributionPeriod::query()->get() as $period) {
        expect($period->year)->toBe(2026)
            ->and($period->status)->toBe(ContributionPeriodStatus::Open)
            ->and($period->amount)->toBe(60000)
            ->and(MemberObligation::query()->where('contribution_period_id', $period->id)->sum('amount'))->toBe(1200000);
    }
});

it('does not fabricate financial or governance history or send import notifications', function (): void {
    foreach ([Expense::class, ExternalAccount::class, Meeting::class, Proposal::class, ActionItem::class, PositionPoll::class] as $model) {
        expect($model::query()->count())->toBe(0);
    }

    Notification::assertNothingSent();
});

it('updates legacy identities without changing member ownership or passwords', function (): void {
    $member = Member::query()->where('member_number', '001')->firstOrFail();
    $user = $member->user;
    $user->forceFill(['name' => 'Fredrick Ssekweyama', 'email' => 'ssekweyama@gmail.com', 'password' => 'my-changed-password'])->save();
    $member->update(['full_name' => 'Fredrick Ssekweyama']);

    $this->seed(MusuwaNationSeeder::class);

    expect($member->fresh()->user_id)->toBe($user->id)
        ->and($user->fresh()->email)->toBe('mubuukefredrick24@gmail.com')
        ->and(Hash::check('my-changed-password', $user->fresh()->password))->toBeTrue()
        ->and(User::query()->count())->toBe(20);
});

it('is idempotent and preserves later membership and contribution changes', function (): void {
    $member = Member::query()->where('member_number', '003')->firstOrFail();
    $member->update(['phone' => '+256771234567', 'position' => null]);

    $obligation = MemberObligation::query()->whereHas('contributionPeriod', fn ($query) => $query->where('month', 9))->firstOrFail();
    $obligation->update(['amount_paid' => 15000]);

    $this->seed(DatabaseSeeder::class);

    expect(Member::query()->count())->toBe(20)
        ->and(User::query()->count())->toBe(20)
        ->and(MembershipStatusHistory::query()->count())->toBe(20)
        ->and(PositionHolding::query()->count())->toBe(10)
        ->and(ContributionPeriod::query()->count())->toBe(3)
        ->and(MemberObligation::query()->count())->toBe(60)
        ->and(Payment::query()->count())->toBe(17)
        ->and(PaymentAllocation::query()->count())->toBe(17)
        ->and($member->fresh()->phone)->toBe('+256771234567')
        ->and($member->fresh()->position)->toBeNull()
        ->and($obligation->fresh()->amount_paid)->toBe(15000);
});

it('imports the corrected August total without counting withdrawal charges as contributions', function (): void {
    $august = MemberObligation::query()->whereHas('contributionPeriod', fn ($query) => $query->where('month', 8))->get();

    expect(Payment::query()->count())->toBe(17)
        ->and(Payment::query()->sum('amount'))->toBe(1005000)
        ->and(Payment::query()->sum('unapplied_amount'))->toBe(0)
        ->and(PaymentAllocation::query()->sum('amount'))->toBe(1005000)
        ->and($august->sum('amount') - $august->sum('amount_paid'))->toBe(195000)
        ->and($august->where('status', ObligationStatus::Paid))->toHaveCount(16)
        ->and($august->where('status', ObligationStatus::Unpaid))->toHaveCount(3)
        ->and($august->where('status', ObligationStatus::PartiallyPaid))->toHaveCount(1);

    foreach (['Turyakira Trevor', 'Lubega James Benjamin', 'Ssegawa Kibombo', 'Namara Honest'] as $name) {
        $member = Member::query()->where('full_name', $name)->firstOrFail();
        expect($august->firstWhere('member_id', $member->id)?->amount_paid)->toBe(60000);
    }

    $shaun = Member::query()->where('full_name', 'Ariko Shaun Opio')->firstOrFail();
    expect($august->firstWhere('member_id', $shaun->id)?->outstanding())->toBe(15000)
        ->and(MemberObligation::query()->whereHas('contributionPeriod', fn ($query) => $query->whereIn('month', [9, 10]))->sum('amount_paid'))->toBe(0);

    foreach (Payment::query()->get() as $payment) {
        expect($payment->status)->toBe(PaymentStatus::Verified)
            ->and($payment->reviewed_by_member_id)->toBeNull()
            ->and($payment->reviewed_at)->toBeNull()
            ->and($payment->notes)->toContain('accounting date', '20,295');
    }
});

it('refuses to mix the opening import with old demo history', function (): void {
    ContributionPeriod::factory()->create(['year' => 2026, 'month' => 1]);

    expect(fn () => $this->seed(MusuwaNationSeeder::class))->toThrow(RuntimeException::class, 'Existing pre-August or demo history')
        ->and(Payment::query()->sum('amount'))->toBe(1005000);
});

it('keeps imported reversals from being silently reapplied on a rerun', function (): void {
    $payment = Payment::query()->firstOrFail();
    $payment->update(['status' => PaymentStatus::Reversed]);

    $this->seed(MusuwaNationSeeder::class);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Reversed)
        ->and(Payment::query()->count())->toBe(17);
});

it('allows a seeded member to sign in and requires email verification', function (): void {
    $user = User::query()->where('email', 'fetaowen@gmail.com')->firstOrFail();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

it('uses the supplied name tag numbers and preserves identities when renumbering', function (): void {
    $names = [
        'Ssekweyama Fredrick', 'Lubega James Benjamin', 'Lemi Manoah', 'Tumwijukye Conrad',
        'Tumwine John Esau', 'Feta Jeff Owen', 'Nuwagaba Michael Kanyima', 'Luate Simon Jackson',
        'Ndagije Ronald', 'Ssegawa Kibombo', 'Namara Honest', 'Odongkara Fred Ojok',
        'Rwothomio Paul', 'Turyakira Trevor', 'Nyero John', 'Musiimenta Alfred Marvin',
        'Ariko Shaun Opio', 'Gimei Jude Tadeo', 'Ishimwe Mark', 'Kiwanuka Joseph',
    ];
    $members = Member::query()->get()->keyBy('full_name');
    $paymentOwners = Payment::query()->pluck('member_id', 'reference')->all();
    $userIds = Member::query()->pluck('user_id', 'id')->all();

    foreach ($names as $index => $name) {
        expect($members[$name]->member_number)->toBe(sprintf('%03d', $index + 1));
        $members[$name]->update(['member_number' => sprintf('LEGACY-%03d', 20 - $index)]);
    }

    $this->seed(MusuwaNationSeeder::class);
    $this->seed(MusuwaNationSeeder::class);

    foreach ($names as $index => $name) {
        expect($members[$name]->fresh()->member_number)->toBe(sprintf('%03d', $index + 1));
    }

    expect(Member::query()->pluck('user_id', 'id')->all())->toEqual($userIds)
        ->and(Payment::query()->pluck('member_id', 'reference')->all())->toEqual($paymentOwners)
        ->and(Payment::query()->count())->toBe(17)
        ->and(OpeningWithdrawalFee::query()->count())->toBe(1);
});

it('imports the aggregate fee once without attributing it to a member', function (): void {
    $this->seed(MusuwaNationSeeder::class);
    $period = ContributionPeriod::query()->where('year', 2026)->where('month', 8)->firstOrFail();
    $report = resolve(MonthlyReportData::class)->for($period);

    expect(OpeningWithdrawalFee::query()->sole()->amount)->toBe(20295)
        ->and(Payment::query()->sum('withdrawal_fee_amount'))->toBe(0)
        ->and(PaymentAllocation::query()->sum('amount'))->toBe(1005000)
        ->and(resolve(ClubCashPosition::class)->verifiedInflows())->toBe(1025295)
        ->and($report['cash']['inflows'])->toBe(1025295)
        ->and($report['cash']['withdrawal_fees'])->toBe(20295)
        ->and($report['contributions']['collected'])->toBe(1005000)
        ->and($report['payments']->sum('amount'))->toBe(1025295);
});

it('refuses to take a requested number from an unrelated member', function (): void {
    $member = Member::query()->where('member_number', '001')->firstOrFail();
    $member->update(['member_number' => 'TEMP-001']);

    $outsider = Member::factory()->create(['member_number' => '001']);

    expect(fn () => $this->seed(MusuwaNationSeeder::class))->toThrow(RuntimeException::class, 'unrelated member')
        ->and($member->fresh()->member_number)->toBe('TEMP-001')
        ->and($outsider->fresh()->member_number)->toBe('001');
});

it('imports Namara Honest August payment once against member 011 without a fee', function (): void {
    $member = Member::query()->where('member_number', '011')->firstOrFail();
    $payment = Payment::query()->where('import_key', 'OPENING-202608-MN-0005')->sole();

    $this->seed(MusuwaNationSeeder::class);

    expect($member->full_name)->toBe('Namara Honest')
        ->and($payment->member_id)->toBe($member->id)
        ->and($payment->amount)->toBe(60000)
        ->and($payment->withdrawal_fee_amount)->toBe(0)
        ->and($payment->allocations()->sum('amount'))->toBe(60000)
        ->and(Payment::query()->where('import_key', $payment->import_key)->count())->toBe(1)
        ->and($payment->reference)->toMatch('/^MNIC-\\d{6}$/');
});
