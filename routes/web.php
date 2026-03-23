<?php

use App\Http\Controllers\ExportController;
use App\Livewire\DetailResult;
use App\Livewire\JobResults;
use App\Livewire\Result;
use App\Livewire\Search;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', Search::class)->name('search');
Route::get('/result', Result::class)->name('result');
Route::get('/result/job/{id}', JobResults::class)->name('result.job');
Route::get('/result/{id}', DetailResult::class)->name('detail-result');
Route::get('/settings', Settings::class)->name('settings');

Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
Route::get('/export/excel', [ExportController::class, 'excel'])->name('export.excel');
