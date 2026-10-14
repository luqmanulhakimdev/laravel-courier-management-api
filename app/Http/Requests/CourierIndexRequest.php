<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CourierIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('level'))) {
            $this->merge(['level' => array_map('trim', explode(',', $this->input('level')))]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:150'],
            'level' => ['sometimes', 'array', 'min:1'],
            'level.*' => ['required', 'integer', 'between:1,5'],
            'sort_by' => ['sometimes', 'in:registered_at'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = ['search', 'level', 'sort_by', 'per_page', 'page'];
            foreach (array_diff(array_keys($this->query()), $allowed) as $key) {
                $validator->errors()->add($key, 'The '.$key.' parameter is not supported.');
            }
        }];
    }
}
