<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    public $timestamps = false;

    protected $fillable = ['debate_id', 'name', 'role', 'stance', 'color', 'turn_order', 'provider', 'model'];

    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        return strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice($words, 0, 2))));
    }
}
