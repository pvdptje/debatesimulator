<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debate extends Model
{
    protected $fillable = ['topic', 'status', 'current_round', 'min_rounds', 'mode'];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class)->orderBy('turn_order');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }
}
