<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::get('/', function () {
    // return view('welcome');
    return abort(403);
});

// Route::get('/test/{id}', [WebScraperController::class, 'GetCollection']);
