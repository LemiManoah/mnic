<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Gives a member a login.
 *
 * This is the practical half of the onboarding gap: rather than waiting for an
 * email invitation flow, an officer creates the account and hands the password
 * over directly. The generated password is returned once and never stored in
 * readable form.
 */
final readonly class CreateUserForMember
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @return array{user: User, password: string}
     */
    public function handle(
        Member $member,
        string $email,
        string $roleName,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): array {
        throw_if(
            $member->user_id !== null,
            InvalidArgumentException::class,
            'This member already has a login.',
        );

        $password = Str::password(12, symbols: false);

        return DB::transaction(function () use ($member, $email, $roleName, $password, $actor, $ipAddress): array {
            $user = User::query()->create([
                'name' => $member->full_name,
                'email' => $email,
                'password' => $password,
                // The officer hands the credentials over in person, so there is
                // nothing to verify by email.
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$roleName]);

            $member->update(['user_id' => $user->id]);

            $this->recordAuditEvent->handle(
                'member.login_created',
                $member,
                $actor,
                null,
                ['email' => $email, 'role' => $roleName],
                $ipAddress,
            );

            return ['user' => $user, 'password' => $password];
        });
    }
}
