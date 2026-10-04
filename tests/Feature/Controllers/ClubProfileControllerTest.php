<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\ClubProfile;

it('shows the club profile and offers a member download', function (): void {
    $member = memberWithRole(ClubRole::Member);

    $this->actingAs($member->user)
        ->get(route('club-profile.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('club-profile/index')
            ->where('profile.cover.tagline', 'Building Wealth. Developing Men. Creating Legacy.')
            ->where('canUpdate', false));

    $this->actingAs($member->user)
        ->get(route('club-profile.download'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename=mnic-club-profile.pdf');
});

it('allows administrators to update the club profile', function (): void {
    $administrator = memberWithRole(ClubRole::Administrator);
    $profile = ClubProfile::query()->findOrFail(1);
    $content = $profile->content;
    $content['purpose']['vision'] = 'An updated club vision.';

    $this->actingAs($administrator->user)
        ->put(route('club-profile.update'), ['profile' => $content])
        ->assertRedirectToRoute('club-profile.index');

    expect($profile->fresh()->content['purpose']['vision'])->toBe('An updated club vision.');
});

it('prevents ordinary members from editing the club profile', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $profile = ClubProfile::query()->findOrFail(1);
    $content = $profile->content;
    $content['purpose']['vision'] = 'An unauthorised change.';

    $this->actingAs($member->user)
        ->put(route('club-profile.update'), ['profile' => $content])
        ->assertForbidden();

    expect($profile->fresh()->content['purpose']['vision'])->not->toBe('An unauthorised change.');
});
