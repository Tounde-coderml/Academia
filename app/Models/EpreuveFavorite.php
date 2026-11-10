<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpreuveFavorite extends Model
{
    protected $fillable = ['user_id', 'epreuve_id'];

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
}
