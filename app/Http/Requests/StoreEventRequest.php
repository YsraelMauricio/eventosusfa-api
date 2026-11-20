<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOrganizer() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'venue' => ['required', 'string', 'max:120'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'description.min' => 'La descripción debe tener al menos 20 caracteres.',
            'category_id.exists' => 'La categoría indicada no existe.',
            'starts_at.after' => 'La fecha de inicio debe ser futura.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
            'capacity.min' => 'El cupo debe ser al menos 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descripción',
            'venue' => 'lugar',
            'category_id' => 'categoría',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'capacity' => 'cupo',
        ];
    }
}
