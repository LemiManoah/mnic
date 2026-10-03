<?php

declare(strict_types=1);

use App\Models\Member;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PioneerMemberSeeder;

it('marks the requested pioneers on a fresh club seed', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Member::query()->where('is_pioneer', true)->orderBy('full_name')->pluck('full_name')->all())->toBe([
        'Feta Jeff Owen',
        'Lemi Manoah',
        'Luate Simon Jackson',
        'Lubega James Benjamin',
        'Ndagije Ronald',
        'Nuwagaba Michael Kanyima',
        'Ssekweyama Fredrick',
        'Tumwine John Esau',
    ]);
});

it('updates existing legacy names without changing other membership data', function (): void {
    $pioneer = Member::factory()->create(['full_name' => 'James Benjamin Lubega']);
    $other = Member::factory()->create(['full_name' => 'Unrelated Member']);

    $this->seed(PioneerMemberSeeder::class);
    $this->seed(PioneerMemberSeeder::class);

    expect($pioneer->fresh()->is_pioneer)->toBeTrue()
        ->and($pioneer->fresh()->phone)->toBe($pioneer->phone)
        ->and($other->fresh()->is_pioneer)->toBeFalse()
        ->and(Member::query()->count())->toBe(2);
});
