<?php

namespace App\Models;

use Database\Factories\EpreuveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Epreuve extends Model
{
    /** @use HasFactory<EpreuveFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'filiere_id', 'niveau_id', 'matiere_id', 'title', 'academic_year',
        'semester', 'type', 'status', 'rejection_reason', 'submitted_at', 'published_at',
        'file_path', 'original_filename', 'mime_type', 'file_size', 'file_sha256',
    ];

    protected $hidden = ['file_path', 'file_sha256'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Filiere, $this> */
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    /** @return BelongsTo<Niveau, $this> */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    /** @return BelongsTo<Matiere, $this> */
    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'file_size' => 'integer',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
