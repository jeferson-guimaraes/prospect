<?php

namespace App\Services;

use App\Enums\RetornoContato;
use App\Models\Prospeccao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ProspeccaoService
{
    /**
     * List prospects with pagination, sorting and filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Prospeccao>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Prospeccao::query()->forCurrentUser();

        // Filtro por busca de texto
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('contato_responsavel', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('site', 'like', "%{$search}%");
            });
        }

        // Filtro por datas
        if (! empty($filters['data_cadastro_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['data_cadastro_inicio']);
        }
        if (! empty($filters['data_cadastro_fim'])) {
            $query->whereDate('created_at', '<=', $filters['data_cadastro_fim']);
        }

        if (! empty($filters['data_contato_inicio'])) {
            $query->whereDate('data_contato', '>=', $filters['data_contato_inicio']);
        }
        if (! empty($filters['data_contato_fim'])) {
            $query->whereDate('data_contato', '<=', $filters['data_contato_fim']);
        }

        if (! empty($filters['data_resposta_inicio'])) {
            $query->whereDate('data_resposta', '>=', $filters['data_resposta_inicio']);
        }
        if (! empty($filters['data_resposta_fim'])) {
            $query->whereDate('data_resposta', '<=', $filters['data_resposta_fim']);
        }

        // Filtro por status (retorno)
        if (! empty($filters['retorno'])) {
            $query->where('retorno', $filters['retorno']);
        }

        // Filtro por faixa de lead score
        if (! empty($filters['lead_score_tier'])) {
            match ($filters['lead_score_tier']) {
                'high' => $query->where('lead_score', '>=', 70),
                'medium' => $query->whereBetween('lead_score', [40, 69]),
                'low' => $query->where('lead_score', '<', 40)->whereNotNull('lead_score'),
                'none' => $query->whereNull('lead_score'),
                default => null,
            };
        }

        // Ordenação dinâmica
        $allowedSortFields = ['created_at', 'nome', 'data_contato', 'retorno', 'canal_contato', 'lead_score'];
        $sortField = in_array($filters['sort_field'] ?? '', $allowedSortFields, true)
            ? $filters['sort_field']
            : 'created_at';
        $sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->with('timelines')->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Create a new prospect.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Prospeccao
    {
        $data['usuario_id'] = Auth::id();
        $data['retorno'] = $data['retorno'] ?? RetornoContato::NAO_CONTATADO;

        $prospeccao = Prospeccao::create($data);

        if (! empty($data['timelines'])) {
            foreach ($data['timelines'] as $timeline) {
                if (! empty($timeline['observacao'])) {
                    $prospeccao->timelines()->create([
                        'observacao' => $timeline['observacao'],
                        'data_observacao' => $timeline['data_observacao'] ?? now(),
                    ]);
                }
            }
        }

        return $prospeccao;
    }

    /**
     * Update an existing prospect.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Prospeccao $prospeccao, array $data): bool
    {
        $updated = $prospeccao->update($data);

        if ($updated && isset($data['timelines'])) {
            // Sincronização simples: remove o que não veio e atualiza/cria o restante
            $incomingIds = array_filter(Arr::pluck($data['timelines'], 'id'));
            $prospeccao->timelines()->whereNotIn('id', $incomingIds)->delete();

            foreach ($data['timelines'] as $timeline) {
                if (! empty($timeline['observacao'])) {
                    if (! empty($timeline['id'])) {
                        $prospeccao->timelines()->whereKey($timeline['id'])->first()?->update([
                            'observacao' => $timeline['observacao'],
                            'data_observacao' => $timeline['data_observacao'] ?? now(),
                        ]);
                    } else {
                        $prospeccao->timelines()->create([
                            'observacao' => $timeline['observacao'],
                            'data_observacao' => $timeline['data_observacao'] ?? now(),
                        ]);
                    }
                }
            }
        }

        return $updated;
    }

    /**
     * Delete a prospect.
     */
    public function delete(Prospeccao $prospeccao): bool
    {
        return (bool) $prospeccao->delete();
    }
}
