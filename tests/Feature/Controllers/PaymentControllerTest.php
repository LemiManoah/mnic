<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lists payments with their evidence for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $payment = Payment::factory()->create();

    PaymentEvidence::factory()->create([
        'payment_id' => $payment->id,
        'original_name' => 'receipt.pdf',
    ]);

    $response = $this->actingAs($actor->user)->get(route('payment.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('payment/index')
            ->has('payments.data', 1)
            ->where('payments.data.0.can_review', false)
            ->has('payments.data.0.evidence', 1)
            ->where('payments.data.0.evidence.0.original_name', 'receipt.pdf')
            ->has('members')
            ->has('methodOptions', count(PaymentMethod::cases())));
});

it('offers review actions to a financial verifier', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    Payment::factory()->create();

    $response = $this->actingAs($actor->user)->get(route('payment.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('payments.data.0.can_review', true));
});

it('does not offer review actions on a payment the verifier recorded', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    Payment::factory()->create(['recorded_by_member_id' => $actor->id]);

    $response = $this->actingAs($actor->user)->get(route('payment.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('payments.data.0.can_review', false));
});

it('allows a treasurer to record a payment for another member', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $member->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $member->id])->contribution_period_id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => 'MM-1234',
    ]);

    $response->assertRedirectToRoute('payment.index');

    expect(Payment::query()->where('external_reference', 'MM-1234')->first()?->recorded_by_member_id)
        ->toBe($actor->id);
});

it('stores evidence uploaded with a payment', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Treasurer);
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $member->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $member->id])->contribution_period_id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => 'MM-5678',
        'evidence' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirectToRoute('payment.index');

    expect(Payment::query()->where('external_reference', 'MM-5678')->first()?->evidence)->toHaveCount(1);
});

it('lets a plain member record a payment for themselves', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $actor->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $actor->id])->contribution_period_id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::Cash->value,
        'external_reference' => 'CS-0001',
    ]);

    $response->assertRedirectToRoute('payment.index');
});

it('stops a plain member recording a payment for somebody else', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $other = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $other->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $other->id])->contribution_period_id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::Cash->value,
        'external_reference' => 'CS-0002',
    ]);

    $response->assertSessionHasErrors('member_id');

    expect(Payment::query()->count())->toBe(0);
});

it('rejects a duplicate transaction reference', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $member = Member::factory()->create();

    Payment::factory()->create(['external_reference' => 'MM-DUP']);

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $member->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $member->id])->contribution_period_id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => 'MM-DUP',
    ]);

    $response->assertSessionHasErrors('external_reference');
});

it('rejects a zero or negative amount', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('payment.store'), [
        'member_id' => $member->id,
        'contribution_period_id' => MemberObligation::factory()->create(['member_id' => $member->id])->contribution_period_id,
        'amount' => 0,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => 'MM-ZERO',
    ]);

    $response->assertSessionHasErrors('amount');
});
