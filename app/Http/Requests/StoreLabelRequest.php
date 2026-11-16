<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.Limits::LABEL_MAX,
                Rule::unique('labels', 'name')->where('project_id', $this->route('project')->id)],
            'color' => ['required', Rule::in(Label::PALETTE)],
        ];
    }
}
