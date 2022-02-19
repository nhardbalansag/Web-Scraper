<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::get('/', function () {
    // return view('welcome');
    return abort(403);
});

Route::get('/test', [WebScraperController::class, 'UpcomingCollection']);
Route::prefix('v1')->group(function () {
    Route::prefix('collection')->group(function () {
        Route::get('/{slug}', [WebScraperController::class, 'GetScrapeData']);
        Route::get('/{collection}/{id}', [WebScraperController::class, 'GetOneCollectionItem']);
        Route::get('/', [WebScraperController::class, 'SingleAsset']);
    });
});
