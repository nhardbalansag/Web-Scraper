<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;


Route::get('/test-api/{slug}', [WebScraperController::class, 'GetCollectionAPI']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
