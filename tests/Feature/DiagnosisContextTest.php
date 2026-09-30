<?php

namespace Tests\Feature;

use App\Services\DiagnosisContextService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DiagnosisContextTest extends TestCase
{
    protected string $customPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customPath = storage_path('framework/testing/diagnosis/company-context.md');
        File::delete($this->customPath);

        Config::set('diagnosis.company_context_path', $this->customPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->customPath));

        parent::tearDown();
    }

    public function test_usa_o_exemplo_quando_nao_ha_contexto_personalizado(): void
    {
        $service = app(DiagnosisContextService::class);

        $instruction = $service->systemInstruction();

        $this->assertFalse($service->usesCustomCompanyContext());
        $this->assertStringNotContainsString('{{contexto_da_empresa}}', $instruction);
        $this->assertStringContainsString('# Contexto da empresa', $instruction);
        $this->assertStringContainsString('"recommended_solutions"', $instruction);
        $this->assertStringContainsString('Regras de pontuação', $instruction);
    }

    public function test_usa_o_contexto_personalizado_quando_o_arquivo_existe(): void
    {
        File::ensureDirectoryExists(dirname($this->customPath));
        File::put($this->customPath, "# Minha Empresa\n\nVendemos consultoria para clínicas.");

        $service = app(DiagnosisContextService::class);

        $instruction = $service->systemInstruction();

        $this->assertTrue($service->usesCustomCompanyContext());
        $this->assertStringContainsString('Vendemos consultoria para clínicas.', $instruction);
        $this->assertStringNotContainsString('Somos uma consultoria de tecnologia', $instruction);
    }

    public function test_comando_publica_o_exemplo_para_edicao(): void
    {
        $this->artisan('diagnostico:publicar-contexto')
            ->assertSuccessful();

        $this->assertFileExists($this->customPath);
        $this->assertFileEquals(config('diagnosis.company_context_example_path'), $this->customPath);
    }

    public function test_comando_nao_sobrescreve_sem_force(): void
    {
        File::ensureDirectoryExists(dirname($this->customPath));
        File::put($this->customPath, 'personalizado');

        $this->artisan('diagnostico:publicar-contexto')
            ->assertFailed();

        $this->assertSame('personalizado', File::get($this->customPath));

        $this->artisan('diagnostico:publicar-contexto', ['--force' => true])
            ->assertSuccessful();

        $this->assertFileEquals(config('diagnosis.company_context_example_path'), $this->customPath);
    }
}
