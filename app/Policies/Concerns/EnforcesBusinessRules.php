<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

/**
 * Marks a policy that decides on more than permissions.
 *
 * Most policies answer one question: does this account hold the permission?
 * An administrator can be waved past those safely, because they hold every
 * permission anyway.
 *
 * Policies carrying this marker also answer questions about *state* — is the
 * payment still awaiting review, is the month locked, did this member already
 * vote, is this the person who recorded it. Those answers must not change just
 * because an administrator is asking. Waving them past would show buttons for
 * actions the Action layer then refuses, turning a clean "no" into a 500.
 *
 * If you write a policy method that inspects a status, a timestamp or who did
 * something, implement this interface.
 */
interface EnforcesBusinessRules
{
    //
}
