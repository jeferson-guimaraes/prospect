<?php

namespace Tests\Feature;

use App\Models\Prospeccao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProspectDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Config::set('services.gemini.api_key', 'test-api-key');
        Config::set('services.gemini.model', 'gemini-test');
    }

    /**
     * @return array<string, mixed>
     */
    protected function geminiResponse(): array
    {
        return [
            'lead_score' => 75,
            'ideal_customer_profile' => true,
            'pain_points' => [
                'Processos manuais de agendamento',
                'Falta de visibilidade operacional',
            ],
            'automation_opportunities' => [
                'Automatizar confirmação de agendamentos',
            ],
            'information_centralization_opportunities' => [
                'Centralizar dados de clientes',
            ],
            'growth_bottlenecks' => [
                'Escalabilidade limitada por planilhas',
            ],
            'recommended_solutions' => [
                'Portal administrativo customizado',
            ],
            'discovery_questions' => [
                'Como os agendamentos são gerenciados hoje?',
                'Quais processos ainda dependem de planilhas?',
            ],
            'first_contact_suggestion' => [
                'recommended_channel' => 'WhatsApp',
                'message' => "Olá, João! Tudo bem?\n\nEstava conhecendo a empresa de vocês e percebi que, no turismo, coordenar agendamentos e confirmações parece exigir bastante atenção da equipe.\n\nFiquei curioso, no dia a dia, vocês sentem que perdem tempo conferindo coisas manualmente ou tendo que repetir a mesma informação em lugares diferentes?",
                'rationale' => 'WhatsApp é o canal mais direto para um primeiro contato consultivo.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $geminiResponse
     */
    protected function fakeGeminiAndSite(?array $geminiResponse = null): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response(
                '<html><head><title>Empresa Teste</title><meta name="description" content="Agência de turismo"></head><body><h1>Bem-vindo</h1><p>Operações manuais.</p></body></html>',
            ),
            'https://www.instagram.com/*' => Http::response(
                '<html><head><title>Nova Empresa (@novaempresa)</title></head><body>Perfil</body></html>',
            ),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($geminiResponse ?? $this->geminiResponse())],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_gera_diagnostico_com_sucesso_na_edicao(): void
    {
        $this->fakeGeminiAndSite();

        $prospect = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
            'whatsapp' => '11999998888',
            'contato_responsavel' => 'João Silva',
            'prospect_id' => $prospect->id,
            'timelines' => [
                ['observacao' => 'Primeiro contato feito por indicação.'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'possiveis_dores',
                'oportunidades_identificadas',
                'perguntas_para_descoberta',
                'sugestao_primeiro_contato',
                'lead_score',
            ]);

        $this->assertSame(75, $response->json('lead_score'));
        $this->assertStringContainsString('Canal recomendado: WhatsApp', $response->json('sugestao_primeiro_contato'));
        $this->assertStringContainsString('Olá, João! Tudo bem?', $response->json('sugestao_primeiro_contato'));
        $this->assertStringContainsString('Fiquei curioso', $response->json('sugestao_primeiro_contato'));
        $this->assertStringContainsString('Hipótese: Processos manuais de agendamento', $response->json('possiveis_dores'));
        $this->assertStringContainsString("Automação:\n- Automatizar confirmação de agendamentos", $response->json('oportunidades_identificadas'));
        $this->assertStringContainsString("Soluções recomendadas:\n- Portal administrativo customizado", $response->json('oportunidades_identificadas'));
        $this->assertStringContainsString('1. Como os agendamentos são gerenciados hoje?', $response->json('perguntas_para_descoberta'));

        $this->assertDatabaseHas('prospeccao', [
            'id' => $prospect->id,
            'possiveis_dores' => $response->json('possiveis_dores'),
            'oportunidades_identificadas' => $response->json('oportunidades_identificadas'),
            'perguntas_para_descoberta' => $response->json('perguntas_para_descoberta'),
            'sugestao_primeiro_contato' => $response->json('sugestao_primeiro_contato'),
            'lead_score' => 75,
        ]);
    }

    public function test_prompt_inclui_dados_do_prospect_e_conteudo_do_site(): void
    {
        $this->fakeGeminiAndSite();

        $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
            'contato_responsavel' => 'João Silva',
            'timelines' => [
                ['observacao' => 'Primeiro contato feito por indicação.'],
            ],
        ])->assertOk();

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $system = $request['systemInstruction']['parts'][0]['text'];
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($system, '# Contexto da empresa')
                && str_contains($system, '"recommended_solutions"')
                && str_contains($prompt, 'Nome: Empresa Teste')
                && str_contains($prompt, 'Contato responsável: João Silva')
                && str_contains($prompt, '- Primeiro contato feito por indicação.')
                && str_contains($prompt, 'Título: Empresa Teste')
                && str_contains($prompt, 'Descrição: Agência de turismo')
                && str_contains($prompt, 'Instagram não informado.');
        });
    }

    public function test_gera_diagnostico_na_criacao_sem_persistir(): void
    {
        $this->fakeGeminiAndSite();

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Nova Empresa',
            'instagram' => '@novaempresa',
        ]);

        $response->assertOk()
            ->assertJsonPath('lead_score', 75)
            ->assertJsonPath('sugestao_primeiro_contato', fn (string $value) => str_contains($value, 'WhatsApp'));

        $this->assertDatabaseCount('prospeccao', 0);
    }

    public function test_nao_duplica_prefixo_hipotese_quando_gemini_ja_retorna(): void
    {
        $this->fakeGeminiAndSite([
            ...$this->geminiResponse(),
            'pain_points' => [
                'Hipótese: Dificuldade na gestão de múltiplos serviços',
            ],
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response->assertOk();
        $this->assertSame(
            'Hipótese: Dificuldade na gestão de múltiplos serviços',
            $response->json('possiveis_dores'),
        );
    }

    public function test_lead_score_acima_da_faixa_e_limitado_a_100(): void
    {
        $this->fakeGeminiAndSite([...$this->geminiResponse(), 'lead_score' => '150']);

        $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ])->assertOk()->assertJsonPath('lead_score', 100);
    }

    public function test_lead_score_nao_numerico_vira_nulo(): void
    {
        $this->fakeGeminiAndSite([...$this->geminiResponse(), 'lead_score' => 'indefinido']);

        $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ])->assertOk()->assertJsonPath('lead_score', null);
    }

    public function test_falha_sem_site_e_instagram(): void
    {
        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Sem Fontes',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['site', 'instagram']);
    }

    public function test_falha_quando_gemini_retorna_erro(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response('<html><body>ok</body></html>'),
            'https://generativelanguage.googleapis.com/*' => Http::response('Service unavailable', 503),
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message']);
        $this->assertStringContainsString('HTTP 503', $response->json('message'));
    }

    public function test_falha_quando_gemini_retorna_json_invalido(): void
    {
        Http::fake([
            'https://empresa.com.br' => Http::response('<html><body>ok</body></html>'),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'not valid json']]]],
                ],
            ]),
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_falha_com_mensagem_clara_sem_chave_da_api(): void
    {
        Config::set('services.gemini.api_key', null);
        Http::fake([
            'https://empresa.com.br' => Http::response('<html><body>ok</body></html>'),
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'A chave da API do Gemini não está configurada.');
    }

    public function test_requer_autenticacao(): void
    {
        Auth::logout();

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa Teste',
            'site' => 'empresa.com.br',
        ]);

        $response->assertUnauthorized();
    }

    public function test_nao_pode_gerar_diagnostico_para_prospect_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();

        $prospect = Prospeccao::factory()->create([
            'usuario_id' => $outroUsuario->id,
            'nome' => 'Empresa de Outro Usuário',
            'site' => 'empresa.com.br',
        ]);

        $response = $this->postJson(route('prospects.diagnostico'), [
            'nome' => 'Empresa de Outro Usuário',
            'site' => 'empresa.com.br',
            'prospect_id' => $prospect->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['prospect_id']);

        $this->assertNull($prospect->fresh()->lead_score);
    }
}
