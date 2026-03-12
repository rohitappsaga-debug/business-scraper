<?php

use App\Livewire\DetailResult;
use App\Livewire\Result;
use App\Livewire\Search;
use Illuminate\Support\Facades\Route;

Route::get('/', Search::class)->name('search');
Route::get('/result', Result::class)->name('result');
Route::get('/result/{id}', DetailResult::class)->name('detail-result');
