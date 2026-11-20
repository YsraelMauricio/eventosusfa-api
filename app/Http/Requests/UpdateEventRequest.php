<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManage($this->route('event')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'min:20', 'max:2000'],
            'venue' => ['sometimes', 'required', 'string', 'max:120'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date', 'after:starts_at'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'status' => ['sometimes', 'required', 'in:draft,published,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'La descripción debe tener al menos 20 caracteres.',
            'category_id.exists' => 'La categoría indicada no existe.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
            'capacity.min' => 'El cupo debe ser al menos 1.',
        ];
    }
}
