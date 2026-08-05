<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\MembershipStatusHistory;

it('belongs to a member', function (): void {
    $member = Member::factory()->create();
    $history = MembershipStatusHistory::factory()->create(['member_id' => $member->id]);

    expect($history->member->is($member))->toBeTrue();
});
