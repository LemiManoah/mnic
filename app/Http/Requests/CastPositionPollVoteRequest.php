<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CastPositionPollVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $poll = $this->route('poll');

        return $poll instanceof PositionPoll
            && (bool) $this->user()?->can('vote', $poll);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $poll = $this->route('poll');

        return [
            'position_poll_candidate_id' => [
                'required',
                'string',
                Rule::exists(PositionPollCandidate::class, 'id')
                    ->where('position_poll_id', $poll instanceof PositionPoll ? $poll->id : ''),
            ],
        ];
    }
}
