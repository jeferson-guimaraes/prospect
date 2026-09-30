<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os dados necessários para gerar o diagnóstico de um prospect com IA.
 */
class GerarDiagnosticoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255', 'required_without:instagram'],
            'instagram' => ['nullable', 'string', 'max:255', 'required_without:site'],
            'contato_responsavel' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'canal_contato' => ['nullable', 'string', 'max:255'],
            'prospect_id' => [
                'nullable',
                'integer',
                Rule::exists('prospeccao', 'id')->where('usuario_id', $this->user()?->id),
            ],
            'timelines' => ['nullable', 'array'],
            'timelines.*.observacao' => ['nullable', 'string'],
            'timelines.*.data_observacao' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site.required_without' => 'Informe o site ou o Instagram para gerar o diagnóstico.',
            'instagram.required_without' => 'Informe o site ou o Instagram para gerar o diagnóstico.',
        ];
    }
}
