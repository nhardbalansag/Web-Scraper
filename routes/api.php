<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\scrapers\WebScraperController;

Route::prefix('v1')->group(function () {
    Route::prefix('collection')->group(function () {
        Route::get('/{slug}', [WebScraperController::class, 'GetScrapeData']);
        Route::get('/{collection}/{id}', [WebScraperController::class, 'GetOneCollectionItem']);
    });

    Route::prefix('db')->group(function () {
        //insert all collection to db
        Route::get('store/{slug}', [WebScraperController::class, 'StoreScrape']);

        /*
            url params: db?offset=0&limit=10&sortby=id&direction=asc
            offset : (starting)
            limit : (where to end)
            sortby : (collection_item_id, rank, id)
            order : (asc or desc)
        */
        Route::get('get/{collection}', [WebScraperController::class, 'GetLimitDBCollection']);

        /*
            url params: db?offset=0&limit=10&sortby=id&order=asc
            offset : (starting)
            limit : (where to end)
            sortby : (collection_item_id, rank, id)
            order : (asc or desc)
        */
        Route::get('get/overall-rarity-score/{collection}', [WebScraperController::class, 'GetOverallRarityScore']);
    });

    Route::prefix('asset')->group(function () {
        Route::get('/{address}/{id}', [WebScraperController::class, 'SingleAsset']);
    });

    Route::prefix('upcoming-collection')->group(function () {
        Route::get('/test', [WebScraperController::class, 'UpcomingCollection']);
    });

    Route::prefix('etherscan')->group(function () {

        /*
            purpose : Get a list of 'Normal' Transactions By Address
            url params: list-normal-transactions-address?module=account&action=txlist&address=0xEA674fdDe714fd979de3EdF0F56AA9716B898ec8&page=1&offset=10&sort=asc&apikey=8VGIEB9DZYAA5V981MV1K89GD91U9VDNV5
            module :
            action :
            address :
            page :
            offset :
            sort :
            apikey :
        */
        Route::get('get/list-normal-transactions-address', [WebScraperController::class, 'listNormalTransactionsAddress']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
