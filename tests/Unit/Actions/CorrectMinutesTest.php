<?php

declare(strict_types=1);

use App\Actions\ConfirmMinutes;
use App\Actions\CorrectMinutes;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Minute;

it('supersedes confirmed minutes with a new version rather than editing them', function (): void {
    $actor = Member::factory()->create();
    $meeting = Meeting::factory()->create();

    $original = Minute::factory()->create([
        'meeting_id' => $meeting->id,
        'version' => 1,
        'body' => 'The treasurer reported UGX 500,000 collected.',
    ]);

    resolve(ConfirmMinutes::class)->handle($original, Member::factory()->create());

    $correction = resolve(CorrectMinutes::class)->handle(
        $meeting,
        'The treasurer reported UGX 550,000 collected.',
        'The figure was transcribed wrongly.',
        $actor,
        '127.0.0.1',
    );

    expect($correction->version)->toBe(2)
        ->and($correction->corrects_minute_id)->toBe($original->id)
        ->and($correction->correction_reason)->toBe('The figure was transcribed wrongly.')
        ->and($correction->isCorrection())->toBeTrue()
        // A correction is not official until it is confirmed in its own right.
        ->and($correction->isConfirmed())->toBeFalse();

    // The confirmed version survives untouched — that is the whole point.
    expect($original->fresh()?->body)->toBe('The treasurer reported UGX 500,000 collected.')
        ->and($original->fresh()?->isConfirmed())->toBeTrue();

    expect(AuditLog::query()
        ->where('auditable_id', $correction->id)
        ->where('event', 'minutes.corrected')
        ->exists())->toBeTrue();
});

it('refuses to correct minutes that were never confirmed', function (): void {
    $meeting = Meeting::factory()->create();

    Minute::factory()->create(['meeting_id' => $meeting->id, 'version' => 1]);

    resolve(CorrectMinutes::class)->handle($meeting, 'Revised.', 'Because.');
})->throws(InvalidArgumentException::class);

it('refuses to correct a meeting with no minutes at all', function (): void {
    resolve(CorrectMinutes::class)
        ->handle(Meeting::factory()->create(), 'Revised.', 'Because.');
})->throws(InvalidArgumentException::class);
