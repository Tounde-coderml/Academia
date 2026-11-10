<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpreuveActivity extends Model
{
    protected $fillable = ['user_id', 'epreuve_id', 'action', 'occurred_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Epreuve, $this> */
    public function epreuve(): BelongsTo
    {
        return $this->belongsTo(Epreuve::class);
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
