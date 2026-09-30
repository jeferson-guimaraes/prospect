<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contexto da empresa
    |--------------------------------------------------------------------------
    |
    | Arquivo Markdown que descreve a empresa que usa o sistema: o que ela
    | vende, perfil de cliente ideal, sinais de compra e temas a evitar. Ele é
    | incorporado ao prompt do diagnóstico com IA. Se o arquivo não existir,
    | o exemplo em resources/prompts/diagnosis/company-context.example.md é
    | usado. Gere uma cópia editável com `php artisan diagnostico:publicar-contexto`.
    |
    */

    'company_context_path' => env(
        'DIAGNOSIS_COMPANY_CONTEXT_PATH',
        storage_path('app/private/diagnosis/company-context.md'),
    ),

    'company_context_example_path' => resource_path('prompts/diagnosis/company-context.example.md'),

    'base_prompt_path' => resource_path('prompts/diagnosis/base.md'),

];
