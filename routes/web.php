<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::get('/', function () {
    // return view('welcome');
    return abort(403);
});

// Route::prefix('v1')->group(function () {
//     Route::prefix('collection')->group(function () {

//         //insert all collection to db
//         Route::get('store/{slug}', [WebScraperController::class, 'StoreScrape']);

//         //collection?offset=0&limit=10&sortby=id&direction=asc
//         Route::get('get/{collection}', [WebScraperController::class, 'GetLimitDBCollection']);

//         //collection?offset=0&limit=10&sortby=id&direction=asc
//         Route::get('get/overall-rarity-score/{collection}', [WebScraperController::class, 'GetOverallRarityScore']);

//         Route::get('/{slug}', [WebScraperController::class, 'GetScrapeData']);
//         // Route::get('/{collection}/{id}', [WebScraperController::class, 'GetOneCollectionItem']);
//         // Route::get('/', [WebScraperController::class, 'SingleAsset']);
//     });

//     Route::prefix('etherscan')->group(function () {
//         //Get a list of 'Normal' Transactions By Address
//         Route::get('get/listNormalTransactionsAddress', [WebScraperController::class, 'listNormalTransactionsAddress']);
//     });

//     Route::prefix('upcoming-collection')->group(function () {
//         Route::get('/get', [WebScraperController::class, 'UpcomingCollection']);
//     });
// });
