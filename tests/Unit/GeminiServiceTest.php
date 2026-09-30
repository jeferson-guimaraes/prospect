<?php

namespace Tests\Unit;

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiInvalidResponseException;
use App\Services\GeminiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.gemini.api_key', 'test-api-key');
        Config::set('services.gemini.model', 'gemini-test');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function fakeGemini(array $payload): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response($payload),
        ]);
    }

    public function test_envia_prompt_e_decodifica_json_da_resposta(): void
    {
        $this->fakeGemini([
            'candidates' => [
                ['content' => ['parts' => [['text' => json_encode(['lead_score' => 80])]]]],
            ],
        ]);

        $result = (new GeminiService)->generateJson('instrucao', 'prompt');

        $this->assertSame(['lead_score' => 80], $result);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/models/gemini-test:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-api-key')
                && $request['systemInstruction']['parts'][0]['text'] === 'instrucao'
                && $request['contents'][0]['parts'][0]['text'] === 'prompt'
                && $request['generationConfig']['responseMimeType'] === 'application/json';
        });
    }

    public function test_falha_sem_chave_configurada(): void
    {
        Config::set('services.gemini.api_key', null);
        Http::fake();

        $this->expectException(GeminiApiException::class);

        (new GeminiService)->generateJson('instrucao', 'prompt');
    }

    public function test_falha_quando_api_retorna_erro_http(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key not valid'],
            ], 400),
        ]);

        $this->expectException(GeminiApiException::class);
        $this->expectExceptionMessage('HTTP 400');

        (new GeminiService)->generateJson('instrucao', 'prompt');
    }

    public function test_falha_quando_conexao_e_recusada(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->expectException(GeminiApiException::class);

        (new GeminiService)->generateJson('instrucao', 'prompt');
    }

    public function test_falha_quando_resposta_esta_vazia(): void
    {
        $this->fakeGemini(['candidates' => []]);

        $this->expectException(GeminiInvalidResponseException::class);

        (new GeminiService)->generateJson('instrucao', 'prompt');
    }

    public function test_falha_quando_texto_nao_e_json(): void
    {
        $this->fakeGemini([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'not valid json']]]],
            ],
        ]);

        $this->expectException(GeminiInvalidResponseException::class);

        (new GeminiService)->generateJson('instrucao', 'prompt');
    }
}
