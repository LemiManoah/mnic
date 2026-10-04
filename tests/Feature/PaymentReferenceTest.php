<?php

declare(strict_types=1);

use App\Actions\RecordPayment;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Payment;

it('generates short MNIC payment references and retains an optional provider reference', function (): void {
    $member = Member::factory()->create();
    $attributes = [
        'member_id' => $member->id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
    ];

    $first = resolve(RecordPayment::class)->handle([...$attributes, 'external_reference' => 'MOBILE-987654321']);
    $second = resolve(RecordPayment::class)->handle($attributes);

    expect($first->reference)->toBe('MNIC-000001')
        ->and($first->external_reference)->toBe('MOBILE-987654321')
        ->and($second->reference)->toBe('MNIC-000002')
        ->and($second->external_reference)->toBeNull();
});

it('does not accept a caller supplied payment reference', function (): void {
    $member = Member::factory()->create();

    $payment = resolve(RecordPayment::class)->handle([
        'member_id' => $member->id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::Cash->value,
        'reference' => 'OPENING-202608-MN-0001',
    ]);

    expect($payment->reference)->toBe('MNIC-000001')
        ->and(Payment::query()->where('reference', 'OPENING-202608-MN-0001')->exists())->toBeFalse();
});
