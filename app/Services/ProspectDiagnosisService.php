<?php

namespace App\Services;

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiInvalidResponseException;
use App\Models\Prospeccao;
use Illuminate\Support\Arr;

/**
 * Orquestra a geração de diagnóstico de prospects via Gemini.
 *
 * Coleta contexto web, monta o prompt a partir do contexto configurado e
 * persiste os campos gerados quando o prospect já existe no banco de dados.
 */
class ProspectDiagnosisService
{
    public function __construct(
        private GeminiService $geminiService,
        private ProspectWebContextService $webContextService,
        private DiagnosisContextService $contextService,
    ) {}

    /**
     * Gera o diagnóstico operacional de um prospect a partir dos dados informados.
     *
     * Quando `prospect_id` está presente, os campos gerados são persistidos
     * imediatamente na prospecção correspondente.
     *
     * @param  array<string, mixed>  $data  Dados do prospect e contexto para análise.
     * @return array{
     *     possiveis_dores: string,
     *     oportunidades_identificadas: string,
     *     perguntas_para_descoberta: string,
     *     sugestao_primeiro_contato: string,
     *     lead_score: ?int
     * }
     *
     * @throws GeminiApiException
     * @throws GeminiInvalidResponseException
     */
    public function generate(array $data): array
    {
        $webContext = $this->webContextService->fetch(
            $data['site'] ?? null,
            $data['instagram'] ?? null,
        );

        $response = $this->geminiService->generateJson(
            $this->contextService->systemInstruction(),
            $this->buildUserPrompt($data, $webContext),
        );

        $result = [
            'possiveis_dores' => $this->formatPainPoints((array) ($response['pain_points'] ?? [])),
            'oportunidades_identificadas' => $this->formatOpportunities($response),
            'perguntas_para_descoberta' => $this->formatDiscoveryQuestions((array) ($response['discovery_questions'] ?? [])),
            'sugestao_primeiro_contato' => $this->formatFirstContactSuggestion(
                is_array($response['first_contact_suggestion'] ?? null)
                    ? $response['first_contact_suggestion']
                    : []
            ),
            'lead_score' => $this->parseLeadScore($response['lead_score'] ?? null),
        ];

        if (! empty($data['prospect_id'])) {
            Prospeccao::query()
                ->forCurrentUser()
                ->whereKey($data['prospect_id'])
                ->firstOrFail()
                ->update($result);
        }

        return $result;
    }

    /**
     * Monta o prompt do usuário com os dados do prospect e o conteúdo web coletado.
     *
     * @param  array<string, mixed>  $data
     * @param  array{
     *     site_content: ?string,
     *     site_error: ?string,
     *     instagram_content: ?string,
     *     instagram_error: ?string
     * }  $webContext
     */
    private function buildUserPrompt(array $data, array $webContext): string
    {
        $lines = [
            'Analise o seguinte prospect e gere o diagnóstico em JSON conforme o formato especificado.',
            '',
            '## Dados do Prospect',
            'Nome: '.($data['nome'] ?? 'Não informado'),
        ];

        if (! empty($data['contato_responsavel'])) {
            $lines[] = 'Contato responsável: '.$data['contato_responsavel'];
            $lines[] = 'Use o primeiro nome do contato responsável na saudação da mensagem de primeiro contato, se fizer sentido.';
        }

        foreach (['whatsapp' => 'WhatsApp', 'site' => 'Site', 'instagram' => 'Instagram', 'email' => 'E-mail', 'canal_contato' => 'Canal de contato'] as $key => $label) {
            if (! empty($data[$key])) {
                $lines[] = "{$label}: ".$data[$key];
            }
        }

        $availableChannels = array_filter([
            ! empty($data['whatsapp']) ? 'WhatsApp' : null,
            ! empty($data['instagram']) ? 'Instagram' : null,
            ! empty($data['email']) ? 'E-mail' : null,
        ]);

        $lines[] = 'Canais de contato disponíveis: '.(
            $availableChannels !== []
                ? implode(', ', $availableChannels)
                : 'Nenhum canal informado'
        );

        if (! empty($data['timelines']) && is_array($data['timelines'])) {
            $observations = array_filter(array_map('strval', Arr::pluck($data['timelines'], 'observacao')));

            if ($observations !== []) {
                $lines[] = '';
                $lines[] = '## Observações da timeline';
                foreach ($observations as $observation) {
                    $lines[] = '- '.$observation;
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Conteúdo extraído do site';
        $lines[] = $webContext['site_content']
            ?? ($webContext['site_error'] !== null ? 'Indisponível: '.$webContext['site_error'] : 'Site não informado.');

        $lines[] = '';
        $lines[] = '## Conteúdo extraído do Instagram';
        $lines[] = $webContext['instagram_content']
            ?? ($webContext['instagram_error'] !== null ? 'Indisponível: '.$webContext['instagram_error'] : 'Instagram não informado.');

        return implode("\n", $lines);
    }

    /**
     * Converte e valida o lead score retornado pelo Gemini (0 a 100).
     */
    private function parseLeadScore(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) max(0, min(100, (int) round((float) $value)));
    }

    /**
     * Formata a sugestão de primeiro contato.
     *
     * @param  array<string, mixed>  $suggestion
     */
    private function formatFirstContactSuggestion(array $suggestion): string
    {
        $channel = trim((string) ($suggestion['recommended_channel'] ?? ''));
        $message = trim((string) ($suggestion['message'] ?? ''));
        $rationale = trim((string) ($suggestion['rationale'] ?? ''));

        $lines = [];

        if ($channel !== '') {
            $lines[] = 'Canal recomendado: '.$channel;
            $lines[] = '';
        }

        if ($message !== '') {
            $lines[] = 'Mensagem sugerida:';
            $lines[] = $message;
            $lines[] = '';
        }

        if ($rationale !== '') {
            $lines[] = 'Por que este canal:';
            $lines[] = $rationale;
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Formata os pain points do Gemini como hipóteses operacionais.
     *
     * @param  array<int, mixed>  $painPoints
     */
    private function formatPainPoints(array $painPoints): string
    {
        $lines = [];

        foreach ($this->stringItems($painPoints) as $point) {
            $lines[] = 'Hipótese: '.$this->stripHypothesisPrefix($point);
        }

        return implode("\n", $lines);
    }

    /**
     * Remove o prefixo "Hipótese:" do início do texto, se presente.
     */
    private function stripHypothesisPrefix(string $point): string
    {
        return preg_replace('/^hipótese:\s*/iu', '', trim($point)) ?? trim($point);
    }

    /**
     * Formata as oportunidades identificadas em seções com bullet points.
     *
     * @param  array<string, mixed>  $response
     */
    private function formatOpportunities(array $response): string
    {
        $sections = [
            'Automação' => $response['automation_opportunities'] ?? [],
            'Centralização de informações' => $response['information_centralization_opportunities'] ?? [],
            'Gargalos de crescimento' => $response['growth_bottlenecks'] ?? [],
            'Soluções recomendadas' => $response['recommended_solutions'] ?? [],
        ];

        $lines = [];

        foreach ($sections as $title => $items) {
            $items = $this->stringItems((array) $items);
            if ($items === []) {
                continue;
            }

            $lines[] = $title.':';
            foreach ($items as $item) {
                $lines[] = '- '.$item;
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Formata as perguntas de descoberta como lista numerada.
     *
     * @param  array<int, mixed>  $questions
     */
    private function formatDiscoveryQuestions(array $questions): string
    {
        $lines = [];

        foreach ($this->stringItems($questions) as $index => $question) {
            $lines[] = ($index + 1).'. '.$question;
        }

        return implode("\n", $lines);
    }

    /**
     * Converte os itens em strings não vazias, descartando o restante.
     *
     * @param  array<int|string, mixed>  $items
     * @return list<string>
     */
    private function stringItems(array $items): array
    {
        $strings = [];

        foreach ($items as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $strings[] = trim((string) $item);
            }
        }

        return $strings;
    }
}
