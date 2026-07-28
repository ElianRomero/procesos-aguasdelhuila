<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceSimpleImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('isContratacion') === true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'extensions:xlsx,xls,csv',
                'max:20480',
            ],
            'fecha_limite' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona el archivo que deseas previsualizar.',
            'file.mimes' => 'El archivo debe ser XLSX, XLS o CSV.',
            'file.extensions' => 'La extension del archivo debe ser xlsx, xls o csv.',
            'file.max' => 'El archivo no puede superar 20 MB.',
            'fecha_limite.required' => 'Selecciona la fecha limite de pago.',
            'fecha_limite.date_format' => 'La fecha limite no tiene un formato valido.',
            'fecha_limite.after_or_equal' => 'La fecha limite no puede ser anterior a hoy.',
        ];
    }
}
