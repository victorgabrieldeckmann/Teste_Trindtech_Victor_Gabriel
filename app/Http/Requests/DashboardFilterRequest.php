<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date') ?? config('sgs.start_date'),
            'end_date' => $this->input('end_date') ?? config('sgs.end_date'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.config('sgs.start_date'),
                'before_or_equal:'.config('sgs.end_date'),
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:'.config('sgs.end_date'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'indicator_id.integer' => 'O indicador selecionado é inválido.',
            'indicator_id.exists' => 'O indicador selecionado não está mais disponível.',
            'start_date.required' => 'Informe a data inicial.',
            'start_date.date_format' => 'Informe uma data inicial válida.',
            'start_date.after_or_equal' => 'A data inicial deve estar dentro do período da consulta.',
            'start_date.before_or_equal' => 'A data inicial deve estar dentro do período da consulta.',
            'end_date.required' => 'Informe a data final.',
            'end_date.date_format' => 'Informe uma data final válida.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'end_date.before_or_equal' => 'A data final deve estar dentro do período da consulta.',
        ];
    }
}
