<?php

declare(strict_types=1);

use App\Actions\RecordPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('records a submitted payment', function (): void {
    $member = Member::factory()->create();
    $actor = Member::factory()->create();

    $payment = resolve(RecordPayment::class)->handle([
        'member_id' => $member->id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => 'MM-0001',
    ], $actor, null, '127.0.0.1');

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->status)->toBe(PaymentStatus::Submitted)
        ->and($payment->reference)->toMatch('/^MNIC-\\d{6}$/')
        ->and($payment->external_reference)->toBe('MM-0001')
        ->and($payment->unapplied_amount)->toBe(0)
        ->and($payment->recorded_by_member_id)->toBe($actor->id);

    expect(AuditLog::query()->where('auditable_id', $payment->id)->where('event', 'payment.recorded')->exists())
        ->toBeTrue();
});

it('stores uploaded evidence on the private disk', function (): void {
    Storage::fake('local');

    $member = Member::factory()->create();
    $actor = Member::factory()->create();

    $payment = resolve(RecordPayment::class)->handle([
        'member_id' => $member->id,
        'amount' => 60000,
        'paid_on' => now()->toDateString(),
        'method' => PaymentMethod::BankTransfer->value,
        'external_reference' => 'BT-0001',
    ], $actor, UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'), '127.0.0.1');

    $evidence = PaymentEvidence::query()->where('payment_id', $payment->id)->first();

    expect($evidence)->not->toBeNull()
        ->and($evidence?->original_name)->toBe('receipt.pdf')
        ->and($evidence?->mime_type)->toBe('application/pdf')
        ->and($evidence?->uploaded_by_member_id)->toBe($actor->id);

    Storage::disk('local')->assertExists((string) $evidence?->path);
});
