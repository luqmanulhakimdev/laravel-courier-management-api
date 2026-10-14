<?php

namespace App\Http\Requests;

use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';
        $courier = $this->route('courier');
        $id = $courier instanceof Courier ? $courier->id : $courier;

        return [
            'name' => [$required, 'string', 'max:150'],
            'phone' => [$required, 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('couriers', 'email')->ignore($id)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'level' => [$required, 'integer', 'between:1,5'],
            'registered_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $key) {
                $validator->errors()->add($key, 'This field is not allowed.');
            }
        }];
    }
}
