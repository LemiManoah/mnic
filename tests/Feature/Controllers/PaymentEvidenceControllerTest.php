<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Support\Facades\Storage;

function evidenceFor(Payment $payment): PaymentEvidence
{
    Storage::disk('local')->put('payment-evidence/receipt.pdf', 'file-contents');

    return PaymentEvidence::factory()->create([
        'payment_id' => $payment->id,
        'path' => 'payment-evidence/receipt.pdf',
        'original_name' => 'receipt.pdf',
    ]);
}

it('lets a treasurer download payment evidence', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Treasurer);
    $payment = Payment::factory()->create();
    $evidence = evidenceFor($payment);

    $response = $this->actingAs($actor->user)
        ->get(route('payment-evidence.show', ['payment' => $payment, 'evidence' => $evidence]));

    $response->assertOk();
});

it('lets a member download evidence for their own payment', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Member);
    $payment = Payment::factory()->create(['member_id' => $actor->id]);
    $evidence = evidenceFor($payment);

    $response = $this->actingAs($actor->user)
        ->get(route('payment-evidence.show', ['payment' => $payment, 'evidence' => $evidence]));

    $response->assertOk();
});

it('stops a member downloading evidence for somebody else', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Member);
    $payment = Payment::factory()->create(['member_id' => Member::factory()->create()->id]);
    $evidence = evidenceFor($payment);

    $response = $this->actingAs($actor->user)
        ->get(route('payment-evidence.show', ['payment' => $payment, 'evidence' => $evidence]));

    $response->assertForbidden();
});

it('does not resolve evidence belonging to a different payment', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Treasurer);
    $payment = Payment::factory()->create();
    $otherPayment = Payment::factory()->create();
    $evidence = evidenceFor($otherPayment);

    $response = $this->actingAs($actor->user)
        ->get(route('payment-evidence.show', ['payment' => $payment, 'evidence' => $evidence]));

    $response->assertNotFound();
});
