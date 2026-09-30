<?php

namespace Tests\Unit;

use App\Services\ProspectWebContextService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProspectWebContextServiceTest extends TestCase
{
    public function test_extrai_titulo_descricao_headings_e_texto_do_site(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response(
                '<html><head><title>Empresa Teste</title><meta name="description" content="Agência de turismo"><script>var x = 1;</script></head><body><h1>Bem-vindo</h1><h2>Pacotes</h2><p>Operações manuais.</p></body></html>',
            ),
        ]);

        $result = (new ProspectWebContextService)->fetch('empresa.com.br', null);

        $this->assertNull($result['site_error']);
        $this->assertStringContainsString('Título: Empresa Teste', $result['site_content']);
        $this->assertStringContainsString('Descrição: Agência de turismo', $result['site_content']);
        $this->assertStringContainsString('Títulos: Bem-vindo | Pacotes', $result['site_content']);
        $this->assertStringContainsString('Operações manuais.', $result['site_content']);
        $this->assertStringNotContainsString('var x = 1', $result['site_content']);
        $this->assertNull($result['instagram_content']);
        $this->assertNull($result['instagram_error']);
    }

    public function test_normaliza_handle_do_instagram(): void
    {
        Http::fake([
            'https://www.instagram.com/*' => Http::response('<html><head><title>Perfil</title></head><body>x</body></html>'),
        ]);

        $result = (new ProspectWebContextService)->fetch(null, '@empresa');

        $this->assertStringContainsString('Título: Perfil', $result['instagram_content']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.instagram.com/empresa/');
    }

    public function test_segue_redirecionamento_validando_o_destino(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response('', 301, ['Location' => 'https://www.empresa.com.br/']),
            'https://www.empresa.com.br/' => Http::response('<html><head><title>Destino</title></head><body>ok</body></html>'),
        ]);

        $result = (new ProspectWebContextService)->fetch('empresa.com.br', null);

        $this->assertNull($result['site_error']);
        $this->assertStringContainsString('Título: Destino', $result['site_content']);
    }

    public function test_bloqueia_redirecionamento_para_endereco_interno(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response('', 302, ['Location' => 'http://127.0.0.1:8000/admin']),
        ]);

        $result = (new ProspectWebContextService)->fetch('empresa.com.br', null);

        $this->assertNull($result['site_content']);
        $this->assertStringContainsString('Endereço não permitido', $result['site_error']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '127.0.0.1'));
    }

    public function test_bloqueia_enderecos_locais_e_de_rede_interna(): void
    {
        Http::fake();

        $service = new ProspectWebContextService;

        foreach (['localhost', 'http://10.0.0.5', 'http://169.254.169.254/latest/meta-data', 'app.internal'] as $site) {
            $result = $service->fetch($site, null);

            $this->assertNull($result['site_content'], $site);
            $this->assertStringContainsString('Endereço não permitido', (string) $result['site_error'], $site);
        }

        Http::assertNothingSent();
    }

    public function test_registra_erro_http_sem_interromper(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response('Not found', 404),
            'https://www.instagram.com/*' => Http::response('<html><head><title>Perfil</title></head><body>x</body></html>'),
        ]);

        $result = (new ProspectWebContextService)->fetch('empresa.com.br', 'empresa');

        $this->assertNull($result['site_content']);
        $this->assertStringContainsString('HTTP 404', $result['site_error']);
        $this->assertStringContainsString('Título: Perfil', $result['instagram_content']);
    }
}
