<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $prospeccao_id
 * @property string $observacao
 * @property Carbon $data_observacao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('prospeccao_timelines')]
#[Fillable(['prospeccao_id', 'observacao', 'data_observacao'])]
class ProspeccaoTimeline extends Model
{
    /**
     * @return BelongsTo<Prospeccao, $this>
     */
    public function prospeccao(): BelongsTo
    {
        return $this->belongsTo(Prospeccao::class, 'prospeccao_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_observacao' => 'datetime',
        ];
    }
}
