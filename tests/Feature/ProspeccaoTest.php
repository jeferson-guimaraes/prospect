<?php

namespace Tests\Feature;

use App\Enums\CanalContato;
use App\Enums\RetornoContato;
use App\Models\Prospeccao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspeccaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, mixed>
     */
    protected array $prospeccaoData;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // As páginas prospects/* ainda não foram portadas para este projeto.
        // Remover quando o frontend do módulo existir.
        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->prospeccaoData = [
            'nome' => 'Empresa XYZ',
            'possiveis_dores' => 'Dificuldade em gerenciar projetos',
            'contato_responsavel' => 'João Silva',
            'whatsapp' => '11987654321',
            'instagram' => '@empresa_xyz',
            'email' => 'contato@empresa-xyz.com',
            'data_contato' => '2024-06-01',
            'canal_contato' => CanalContato::WHATSAPP->value,
            'data_resposta' => '2024-06-05',
            'retorno' => RetornoContato::PENDENTE->value,
            'timelines' => [
                ['observacao' => 'Interessados em soluções de gerenciamento de projetos.'],
            ],
        ];
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        auth()->logout();

        $response = $this->get(route('prospects.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_usuario_pode_criar_prospeccao_valida(): void
    {
        $response = $this->post(route('prospects.store'), $this->prospeccaoData);

        $response->assertRedirect(route('prospects.index'));

        $this->assertDatabaseHas('prospeccao', [
            'usuario_id' => $this->user->id,
            'nome' => $this->prospeccaoData['nome'],
            'data_contato' => $this->prospeccaoData['data_contato'],
            'data_resposta' => $this->prospeccaoData['data_resposta'],
        ]);

        $prospeccao = Prospeccao::where('nome', $this->prospeccaoData['nome'])->first();
        $this->assertDatabaseHas('prospeccao_timelines', [
            'prospeccao_id' => $prospeccao->id,
            'observacao' => 'Interessados em soluções de gerenciamento de projetos.',
        ]);
    }

    public function test_prospeccao_criada_sem_retorno_usa_nao_contatado_como_padrao(): void
    {
        $data = $this->prospeccaoData;
        unset($data['retorno']);

        $response = $this->post(route('prospects.store'), $data);

        $response->assertRedirect(route('prospects.index'));

        $this->assertDatabaseHas('prospeccao', [
            'nome' => $data['nome'],
            'retorno' => RetornoContato::NAO_CONTATADO->value,
        ]);
    }

    public function test_usuario_nao_pode_criar_prospeccao_sem_nome(): void
    {
        $data = $this->prospeccaoData;
        unset($data['nome']);

        $response = $this->post(route('prospects.store'), $data);

        $response->assertSessionHasErrors('nome');
    }

    public function test_usuario_nao_pode_criar_prospeccao_sem_nenhum_contato(): void
    {
        $data = $this->prospeccaoData;
        unset($data['whatsapp'], $data['instagram'], $data['email']);

        $response = $this->post(route('prospects.store'), $data);

        $response->assertSessionHasErrors(['whatsapp', 'instagram', 'email']);
        $this->assertDatabaseCount('prospeccao', 0);
    }

    public function test_usuario_pode_atualizar_prospeccao(): void
    {
        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Empresa Original',
            'retorno' => 'Pendente',
        ]);

        $data = [
            'nome' => 'Empresa Atualizada',
            'retorno' => 'Positivo',
            'email' => 'atualizado@empresa.com',
            'timelines' => [
                ['observacao' => 'Agendado reunião para próxima semana.'],
            ],
        ];

        $response = $this->patch(route('prospects.update', $prospeccao->id), $data);

        $response->assertRedirect(route('prospects.index'));

        $this->assertDatabaseHas('prospeccao', [
            'id' => $prospeccao->id,
            'nome' => 'Empresa Atualizada',
            'retorno' => 'Positivo',
        ]);

        $this->assertDatabaseHas('prospeccao_timelines', [
            'prospeccao_id' => $prospeccao->id,
            'observacao' => 'Agendado reunião para próxima semana.',
        ]);
    }

    public function test_atualizacao_sincroniza_timelines_existentes(): void
    {
        $prospeccao = Prospeccao::factory()->create(['usuario_id' => $this->user->id]);
        $mantida = $prospeccao->timelines()->create(['observacao' => 'Primeiro contato']);
        $removida = $prospeccao->timelines()->create(['observacao' => 'Será removida']);

        $response = $this->patch(route('prospects.update', $prospeccao->id), [
            'email' => 'contato@empresa.com',
            'timelines' => [
                ['id' => $mantida->id, 'observacao' => 'Primeiro contato editado'],
                ['observacao' => 'Nova observação'],
            ],
        ]);

        $response->assertRedirect(route('prospects.index'));

        $this->assertDatabaseHas('prospeccao_timelines', [
            'id' => $mantida->id,
            'observacao' => 'Primeiro contato editado',
        ]);
        $this->assertDatabaseHas('prospeccao_timelines', [
            'prospeccao_id' => $prospeccao->id,
            'observacao' => 'Nova observação',
        ]);
        $this->assertDatabaseMissing('prospeccao_timelines', ['id' => $removida->id]);
    }

    public function test_atualizacao_rejeita_timeline_de_outro_prospect(): void
    {
        $prospeccao = Prospeccao::factory()->create(['usuario_id' => $this->user->id]);
        $outroProspect = Prospeccao::factory()->create(['usuario_id' => $this->user->id]);
        $timelineAlheia = $outroProspect->timelines()->create(['observacao' => 'De outro prospect']);

        $response = $this->patch(route('prospects.update', $prospeccao->id), [
            'email' => 'contato@empresa.com',
            'timelines' => [
                ['id' => $timelineAlheia->id, 'observacao' => 'Tentativa de edição'],
            ],
        ]);

        $response->assertSessionHasErrors('timelines.0.id');

        $this->assertDatabaseHas('prospeccao_timelines', [
            'id' => $timelineAlheia->id,
            'observacao' => 'De outro prospect',
        ]);
    }

    public function test_usuario_pode_deletar_prospeccao(): void
    {
        $data = $this->prospeccaoData;
        unset($data['timelines']);
        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            ...$data,
        ]);

        $response = $this->delete(route('prospects.destroy', $prospeccao->id));

        $response->assertRedirect(route('prospects.index'));

        $this->assertDatabaseMissing('prospeccao', [
            'id' => $prospeccao->id,
        ]);
    }

    public function test_listagem_filtra_por_faixa_de_lead_score(): void
    {
        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Lead Alto',
            'lead_score' => 85,
        ]);

        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Lead Medio',
            'lead_score' => 55,
        ]);

        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Lead Baixo',
            'lead_score' => 25,
        ]);

        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Sem Score',
            'lead_score' => null,
        ]);

        $response = $this->get(route('prospects.index', ['lead_score_tier' => 'high']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->has('prospects.data', 1)
            ->where('prospects.data.0.nome', 'Lead Alto')
        );

        $response = $this->get(route('prospects.index', ['lead_score_tier' => 'none']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->has('prospects.data', 1)
            ->where('prospects.data.0.nome', 'Sem Score')
        );
    }

    public function test_listagem_ordenada_por_lead_score(): void
    {
        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Lead Baixo',
            'lead_score' => 20,
        ]);

        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Lead Alto',
            'lead_score' => 90,
        ]);

        $response = $this->get(route('prospects.index', [
            'sort_field' => 'lead_score',
            'sort_direction' => 'desc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->where('prospects.data.0.nome', 'Lead Alto')
            ->where('prospects.data.1.nome', 'Lead Baixo')
        );
    }

    public function test_index_restaura_filtros_da_sessao_ao_voltar(): void
    {
        session([
            'prospects.index_filters' => [
                'search' => 'Empresa Filtrada',
                'sort_field' => 'nome',
            ],
        ]);

        $response = $this->get(route('prospects.index'));

        $response->assertRedirect(route('prospects.index', [
            'search' => 'Empresa Filtrada',
            'sort_field' => 'nome',
        ]));
    }

    public function test_store_redireciona_mantendo_filtros_da_sessao(): void
    {
        session([
            'prospects.index_filters' => [
                'search' => 'Empresa XYZ',
                'retorno' => 'Pendente',
            ],
        ]);

        $response = $this->post(route('prospects.store'), $this->prospeccaoData);

        $response->assertRedirect(route('prospects.index', [
            'search' => 'Empresa XYZ',
            'retorno' => 'Pendente',
        ]));
    }

    public function test_index_limpa_filtros_da_sessao_com_reset_filters(): void
    {
        session([
            'prospects.index_filters' => [
                'search' => 'Empresa Filtrada',
                'sort_field' => 'nome',
            ],
        ]);

        $response = $this->get(route('prospects.index', ['reset_filters' => 1]));

        $response->assertRedirect(route('prospects.index'));
        $this->assertNull(session('prospects.index_filters'));

        $response = $this->get(route('prospects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->where('filters', []),
        );
    }

    public function test_index_mescla_filtros_da_sessao_com_query_parcial(): void
    {
        session([
            'prospects.index_filters' => [
                'search' => 'Empresa Filtrada',
                'retorno' => 'Pendente',
            ],
        ]);

        $response = $this->get(route('prospects.index', ['page' => 2]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->where('filters.search', 'Empresa Filtrada')
            ->where('filters.retorno', 'Pendente')
            ->where('filters.page', '2')
        );
    }

    public function test_update_redireciona_mantendo_filtros_da_sessao(): void
    {
        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Empresa Original',
            'retorno' => 'Pendente',
        ]);

        session([
            'prospects.index_filters' => [
                'search' => 'Empresa Original',
                'sort_field' => 'nome',
            ],
        ]);

        $response = $this->patch(route('prospects.update', $prospeccao->id), [
            'nome' => 'Empresa Atualizada',
            'retorno' => 'Positivo',
            'email' => 'atualizado@empresa.com',
        ]);

        $response->assertRedirect(route('prospects.index', [
            'search' => 'Empresa Original',
            'sort_field' => 'nome',
        ]));
    }

    public function test_destroy_redireciona_mantendo_filtros_da_sessao(): void
    {
        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
        ]);

        session([
            'prospects.index_filters' => [
                'retorno' => 'Pendente',
            ],
        ]);

        $response = $this->delete(route('prospects.destroy', $prospeccao->id));

        $response->assertRedirect(route('prospects.index', [
            'retorno' => 'Pendente',
        ]));
    }

    public function test_listagem_mostra_apenas_prospects_do_usuario(): void
    {
        $outroUsuario = User::factory()->create();

        Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
            'nome' => 'Meu Prospect',
        ]);

        Prospeccao::factory()->create([
            'usuario_id' => $outroUsuario->id,
            'nome' => 'Prospect de Outro Usuário',
        ]);

        $response = $this->get(route('prospects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/index')
            ->has('prospects.data', 1)
            ->where('prospects.data.0.nome', 'Meu Prospect')
        );
    }

    public function test_usuario_pode_abrir_edicao_do_proprio_prospect(): void
    {
        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $this->user->id,
        ]);
        $prospeccao->timelines()->create(['observacao' => 'Primeiro contato']);

        $response = $this->get(route('prospects.edit', $prospeccao->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('prospects/edit')
            ->where('prospect.id', $prospeccao->id)
            ->has('prospect.timelines', 1)
        );
    }

    public function test_usuario_nao_pode_editar_prospect_de_outro_usuario(): void
    {
        $outroUsuario = User::factory()->create();

        $prospeccao = Prospeccao::factory()->create([
            'usuario_id' => $outroUsuario->id,
        ]);

        $this->get(route('prospects.edit', $prospeccao->id))->assertNotFound();

        $this->patch(route('prospects.update', $prospeccao->id), ['nome' => 'Invadido'])->assertNotFound();

        $this->delete(route('prospects.destroy', $prospeccao->id))->assertNotFound();

        $this->assertDatabaseHas('prospeccao', [
            'id' => $prospeccao->id,
            'nome' => $prospeccao->nome,
        ]);
    }
}
