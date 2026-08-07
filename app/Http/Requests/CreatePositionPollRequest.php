<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClubPosition;
use App\Models\PositionPoll;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreatePositionPollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', PositionPoll::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', new Enum(ClubPosition::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
