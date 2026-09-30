<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Monta a instrução de sistema do diagnóstico com IA.
 *
 * Combina o prompt base do repositório com o contexto da empresa, que pode
 * ser personalizado por instalação através de um arquivo Markdown externo.
 */
class DiagnosisContextService
{
    private const string PLACEHOLDER = '{{contexto_da_empresa}}';

    /**
     * Retorna a instrução de sistema completa, com o contexto da empresa incorporado.
     */
    public function systemInstruction(): string
    {
        $base = $this->read($this->basePromptPath());

        if (! str_contains($base, self::PLACEHOLDER)) {
            throw new RuntimeException(
                'O prompt base do diagnóstico não contém o marcador '.self::PLACEHOLDER.'.',
            );
        }

        return str_replace(self::PLACEHOLDER, trim($this->companyContext()), $base);
    }

    /**
     * Retorna o contexto da empresa: o arquivo personalizado, se existir, ou o exemplo.
     */
    public function companyContext(): string
    {
        return $this->read($this->usesCustomCompanyContext() ? $this->companyContextPath() : $this->companyContextExamplePath());
    }

    /**
     * Indica se a instalação possui um contexto da empresa personalizado.
     */
    public function usesCustomCompanyContext(): bool
    {
        return File::isFile($this->companyContextPath());
    }

    public function companyContextPath(): string
    {
        return (string) config('diagnosis.company_context_path');
    }

    public function companyContextExamplePath(): string
    {
        return (string) config('diagnosis.company_context_example_path');
    }

    public function basePromptPath(): string
    {
        return (string) config('diagnosis.base_prompt_path');
    }

    private function read(string $path): string
    {
        if (! File::isFile($path)) {
            throw new RuntimeException("Arquivo de prompt não encontrado: {$path}");
        }

        return File::get($path);
    }
}
