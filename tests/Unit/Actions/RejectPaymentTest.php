<?php

declare(strict_types=1);

use App\Actions\RejectPayment;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Payment;

it('rejects a submitted payment with a reason', function (): void {
    $payment = Payment::factory()->create();
    $verifier = Member::factory()->create();

    $rejected = resolve(RejectPayment::class)->handle($payment, $verifier, 'Evidence does not match', '10.0.0.1');

    expect($rejected->status)->toBe(PaymentStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('Evidence does not match')
        ->and($rejected->reviewed_by_member_id)->toBe($verifier->id);

    expect(AuditLog::query()->where('auditable_id', $payment->id)->where('event', 'payment.rejected')->exists())
        ->toBeTrue();
});

it('refuses to reject a payment recorded by the same member', function (): void {
    $recorder = Member::factory()->create();
    $payment = Payment::factory()->create(['recorded_by_member_id' => $recorder->id]);

    resolve(RejectPayment::class)->handle($payment, $recorder, 'Nope');
})->throws(InvalidArgumentException::class);

it('refuses to reject a payment that is not submitted', function (): void {
    $payment = Payment::factory()->verified()->create();

    resolve(RejectPayment::class)->handle($payment, Member::factory()->create(), 'Too late');
})->throws(InvalidArgumentException::class);
