<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [WebScraperController::class, 'GetCollection']);
