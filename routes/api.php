<?php

use App\Http\Controllers\Api\SocialLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/social-links', [SocialLinkController::class, 'index']);
