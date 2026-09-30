<?php

namespace App\Http\Requests;

use App\Enums\CanalContato;
use App\Enums\RetornoContato;
use App\Models\Prospeccao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProspeccaoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prospect = $this->route('prospect');

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'possiveis_dores' => ['nullable', 'string'],
            'oportunidades_identificadas' => ['nullable', 'string'],
            'perguntas_para_descoberta' => ['nullable', 'string'],
            'sugestao_primeiro_contato' => ['nullable', 'string'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'contato_responsavel' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255', 'required_without_all:instagram,email'],
            'instagram' => ['nullable', 'string', 'max:255', 'required_without_all:whatsapp,email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without_all:whatsapp,instagram'],
            'site' => ['nullable', 'string', 'max:255'],
            'data_contato' => ['nullable', 'date'],
            'canal_contato' => ['nullable', new Enum(CanalContato::class)],
            'data_resposta' => ['nullable', 'date'],
            'retorno' => ['sometimes', 'required', new Enum(RetornoContato::class)],
            'timelines' => ['nullable', 'array'],
            'timelines.*.id' => [
                'nullable',
                'integer',
                Rule::exists('prospeccao_timelines', 'id')->where(
                    'prospeccao_id',
                    $prospect instanceof Prospeccao ? $prospect->id : null,
                ),
            ],
            'timelines.*.observacao' => ['required', 'string'],
            'timelines.*.data_observacao' => ['nullable', 'date'],
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
            'whatsapp.required_without_all' => 'Pelo menos um dos campos de contato (WhatsApp, Instagram ou E-mail) deve ser preenchido.',
            'instagram.required_without_all' => 'Pelo menos um dos campos de contato (WhatsApp, Instagram ou E-mail) deve ser preenchido.',
            'email.required_without_all' => 'Pelo menos um dos campos de contato (WhatsApp, Instagram ou E-mail) deve ser preenchido.',
        ];
    }
}
