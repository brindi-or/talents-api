<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Oeuvre extends Model
{
    use HasFactory;

    protected $table = 'oeuvres';

    protected $fillable = ['talent_id', 'chemin', 'degrade', 'legende', 'ordre'];

    protected function casts(): array
    {
        return ['ordre' => 'integer'];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * URL publique de la photo, ou null s'il n'y en a pas encore.
     * Le front affiche alors le degrade a la place.
     */
    public function url(): ?string
    {
        return $this->chemin ? Storage::disk('public')->url($this->chemin) : null;
    }
}
