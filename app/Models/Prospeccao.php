<?php

namespace App\Models;

use App\Enums\CanalContato;
use App\Enums\RetornoContato;
use Database\Factories\ProspeccaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $nome
 * @property string|null $possiveis_dores
 * @property string|null $oportunidades_identificadas
 * @property string|null $perguntas_para_descoberta
 * @property string|null $sugestao_primeiro_contato
 * @property int|null $lead_score
 * @property string|null $contato_responsavel
 * @property string|null $whatsapp
 * @property string|null $instagram
 * @property string|null $email
 * @property string|null $site
 * @property Carbon|null $data_contato
 * @property CanalContato|null $canal_contato
 * @property Carbon|null $data_resposta
 * @property RetornoContato $retorno
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('prospeccao')]
#[Fillable([
    'usuario_id',
    'nome',
    'possiveis_dores',
    'oportunidades_identificadas',
    'perguntas_para_descoberta',
    'sugestao_primeiro_contato',
    'lead_score',
    'contato_responsavel',
    'whatsapp',
    'instagram',
    'email',
    'site',
    'data_contato',
    'canal_contato',
    'data_resposta',
    'retorno',
    'created_at',
])]
class Prospeccao extends Model
{
    /** @use HasFactory<ProspeccaoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return HasMany<ProspeccaoTimeline, $this>
     */
    public function timelines(): HasMany
    {
        return $this->hasMany(ProspeccaoTimeline::class, 'prospeccao_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        return $query->where('usuario_id', Auth::id());
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        return static::query()
            ->forCurrentUser()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_contato' => 'date:Y-m-d',
            'data_resposta' => 'date:Y-m-d',
            'lead_score' => 'integer',
            'canal_contato' => CanalContato::class,
            'retorno' => RetornoContato::class,
        ];
    }
}
