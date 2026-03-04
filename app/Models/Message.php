<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'debate_id', 'agent_id', 'round', 'content',
        'is_kill_switch', 'verdict', 'kill_switch_reason',
    ];

    protected $casts = [
        'is_kill_switch' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }
}
