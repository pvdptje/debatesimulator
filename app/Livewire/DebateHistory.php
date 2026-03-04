<?php

namespace App\Livewire;

use App\Models\Debate;
use Livewire\Component;

class DebateHistory extends Component
{
    public function render()
    {
        $debates = Debate::withCount(['agents', 'messages'])
            ->latest()
            ->get();

        return view('livewire.debate-history', [
            'debates' => $debates,
        ])->layout('layouts.app');
    }
}
