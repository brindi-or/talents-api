<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avis extends Model
{
    use HasFactory;

    // Sans cela Eloquent chercherait la table « avises ».
    protected $table = 'avis';

    protected $fillable = ['talent_id', 'auteur', 'texte', 'etoiles'];

    protected function casts(): array
    {
        return ['etoiles' => 'integer'];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
