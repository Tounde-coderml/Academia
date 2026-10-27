<?php

namespace App\Models;

use Database\Factories\MatiereFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'is_active'])]
class Matiere extends Model
{
    /** @use HasFactory<MatiereFactory> */
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
            'is_active' => 'boolean',
        ];
    }
}
