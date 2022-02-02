<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::prefix('v1')->group(function () {
    Route::prefix('collection')->group(function () {
        Route::get('/{slug}', [WebScraperController::class, 'GetScrapeData']);
        Route::get('/{collection}/{id}', [WebScraperController::class, 'GetOneCollectionItem']);
    });

    Route::prefix('asset')->group(function () {
        Route::get('/{address}/{id}', [WebScraperController::class, 'SingleAsset']);
    });

    Route::prefix('upcoming-collection')->group(function () {
        Route::get('/test', [WebScraperController::class, 'UpcomingCollection']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
