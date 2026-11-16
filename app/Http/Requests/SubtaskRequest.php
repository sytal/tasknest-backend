<?php

namespace App\Http\Requests;

use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;

class SubtaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:'.Limits::SUBTASK_MAX],
            'is_done' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
