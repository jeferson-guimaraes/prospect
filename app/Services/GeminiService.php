<?php

namespace App\Services;

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiInvalidResponseException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Encapsula a comunicação HTTP com a API do Google Gemini.
 */
class GeminiService
{
    private const string BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Envia um prompt ao Gemini e retorna a resposta decodificada como array associativo.
     *
     * A requisição utiliza `responseMimeType: application/json` para garantir
     * que o modelo retorne JSON estruturado.
     *
     * @param  string  $systemInstruction  Instruções de sistema enviadas ao modelo.
     * @param  string  $userPrompt  Conteúdo do prompt do usuário com o contexto da análise.
     * @return array<string, mixed>
     *
     * @throws GeminiApiException Quando a chave da API não está configurada ou a requisição HTTP falha.
     * @throws GeminiInvalidResponseException Quando a resposta está vazia ou não é um JSON válido.
     */
    public function generateJson(string $systemInstruction, string $userPrompt): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');
        $timeout = (int) config('services.gemini.timeout');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new GeminiApiException('A chave da API do Gemini não está configurada.');
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post(self::BASE_URL."/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new GeminiApiException('Não foi possível conectar à API do Gemini: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? $response->body();

            throw new GeminiApiException("Falha na comunicação com a API do Gemini (HTTP {$response->status()}): {$detail}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new GeminiInvalidResponseException('A API do Gemini retornou uma resposta vazia.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new GeminiInvalidResponseException('A API do Gemini retornou JSON inválido.');
        }

        return $decoded;
    }
}
