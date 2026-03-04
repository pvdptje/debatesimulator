<?php

use App\Livewire\DebateHistory;
use App\Livewire\DebateRoom;
use App\Livewire\DebateSetup;
use Illuminate\Support\Facades\Route;

Route::get('/', DebateSetup::class)->name('home');
Route::get('/debate/{debate}', DebateRoom::class)->name('debate.room');
Route::get('/history', DebateHistory::class)->name('debate.history');
