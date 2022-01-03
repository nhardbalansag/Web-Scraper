<?php

namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Goutte\Client;

class WebScraperController extends Controller
{
    public function GetCollection(){
        $client = new Client();
        $url =  "https://rarity.tools/cryptopunks";
        $page = $client->request('GET', $url);

        $collectionSize = $page->filter("div");

        $collectionSize->each(function($item){
            dump($item->filter('div')->text());
        });
    }

    public function GetCollectionAPI(){
        $client = new Client();
        $url =  "https://rarity.tools/cryptopunks";
        $page = $client->request('GET', $url);

        $collections = array();
        $properties = array();

        // get collection details
        $collectionName = $page->filter("h1")->text();
        $collectionSize = $page->filter("div");
        $propertyCount = null;
        $collectionItem = null;

        array_push(
            $properties,
            array(
                "property_name" => 'attributeCount',
                "value" => 2,
                "scoreContribution" => 21,
                "supply" => 345
            ),
            array(
                "property_name" => 'attributeCount',
                "value" => 2,
                "scoreContribution" => 21,
                "supply" => 345
            )
        );

        array_push(
            $collections,
            array(
                "id" => 1,
                "score" => 84.74,
                "rank" => 7092,
                "properties" => $properties
            )
        );

        $data = array(
            "collectionName" => $collectionName,
            "collectionSize" => "test",
            "propertyCount" => "test",
            "items" => $collections
        );

        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
