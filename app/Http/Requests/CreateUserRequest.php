<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Self-registration request.
 *
 * Currently dormant: the `register` / `register.store` routes are commented
 * out in routes/web.php, so nothing resolves this request at runtime. It is
 * kept intact (and covered by tests/Unit/Requests/CreateUserRequestTest.php)
 * so re-enabling registration is just a matter of restoring the routes and
 * UserController::create/store.
 */
final class CreateUserRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }
}
