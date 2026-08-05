<?php

declare(strict_types=1);

use App\Http\Requests\CreateUserRequest;

/**
 * Self-registration is currently disabled (the register routes are commented
 * out in routes/web.php), so this request is not reachable over HTTP. This
 * test pins its validation contract so the rules still hold whenever
 * registration is switched back on.
 */
it('defines the registration validation rules', function (): void {
    $rules = (new CreateUserRequest)->rules();

    expect($rules)->toHaveKeys(['name', 'email', 'password'])
        ->and($rules['name'])->toContain('required', 'string', 'max:255')
        ->and($rules['email'])->toContain('required', 'string', 'lowercase', 'email', 'max:255')
        ->and($rules['password'])->toContain('required', 'confirmed');
});
