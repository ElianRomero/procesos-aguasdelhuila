<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceSimpleImportConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('isContratacion') === true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'No se encontro el lote que deseas confirmar.',
            'token.uuid' => 'El identificador del lote no es valido.',
        ];
    }
}
