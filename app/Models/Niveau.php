<?php

namespace App\Models;

use Database\Factories\NiveauFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'sort_order', 'is_active'])]
class Niveau extends Model
{
    /** @use HasFactory<NiveauFactory> */
    use HasFactory;

    /**
     * @return HasMany<FiliereNiveauMatiere, $this>
     */
    public function associations(): HasMany
    {
        return $this->hasMany(FiliereNiveauMatiere::class);
    }

    public function isUsed(): bool
    {
        return $this->associations()->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
