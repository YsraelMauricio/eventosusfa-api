<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:starts_at,-starts_at'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.exists' => 'La categoría indicada no existe.',
            'to.after_or_equal' => 'La fecha final debe ser posterior o igual a la inicial.',
            'sort.in' => 'El orden solo admite starts_at o -starts_at.',
            'per_page.max' => 'El tamaño de página no puede superar 50.',
        ];
    }
}
