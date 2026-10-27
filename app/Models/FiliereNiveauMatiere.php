<?php

namespace App\Models;

use Database\Factories\FiliereNiveauMatiereFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiliereNiveauMatiere extends Model
{
    /** @use HasFactory<FiliereNiveauMatiereFactory> */
    use HasFactory;

    protected $table = 'filiere_niveau_matiere';

    /** @var list<string> */
    protected $fillable = [
        'filiere_id',
        'niveau_id',
        'matiere_id',
    ];

    /**
     * @return BelongsTo<Filiere, $this>
     */
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    /**
     * @return BelongsTo<Niveau, $this>
     */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    /**
     * @return BelongsTo<Matiere, $this>
     */
    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }
}
