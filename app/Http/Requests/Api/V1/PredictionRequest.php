<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PredictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y'))],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'limit' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
        ];
    }
}
