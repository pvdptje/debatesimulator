<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Debate;
use Illuminate\Database\Seeder;

class DebateSeeder extends Seeder
{
    public function run(): void
    {
        $debate = Debate::create([
            'topic'  => 'Is remote work better than office work?',
            'status' => 'setup',
        ]);

        Agent::create([
            'debate_id'  => $debate->id,
            'name'       => 'Dr. Sarah Chen',
            'role'       => 'Organizational Psychologist',
            'stance'     => 'Remote work is objectively superior and office culture is a relic of industrial-age control',
            'color'      => '#3B82F6',
            'turn_order' => 0,
            'provider'   => 'anthropic',
            'model'      => 'claude-haiku-4-5',
            'created_at' => now(),
        ]);

        Agent::create([
            'debate_id'  => $debate->id,
            'name'       => 'Marcus Holt',
            'role'       => 'CEO & Entrepreneur',
            'stance'     => 'Office work drives innovation and collaboration that remote work can never replicate',
            'color'      => '#EF4444',
            'turn_order' => 1,
            'provider'   => 'anthropic',
            'model'      => 'claude-haiku-4-5',
            'created_at' => now(),
        ]);
    }
}
