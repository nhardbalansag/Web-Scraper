<?php

namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Goutte\Client;

class WebScraperController extends Controller
{
    public function GetCollection(){
        $client = new Client();
        $url =  "http://student-kiosk.test/";
        $page = $client->request('GET', $url);

        dd($page->filter("h1")->text());
    }


    public function GetCollectionAPI(){
        $client = new Client();
        $url =  "http://student-kiosk.test/";
        $page = $client->request('GET', $url);
        $data = $page->filter("h1")->text();

        $collections = array();

        array_push(
            $collections,
            array(
                "id" => 1,
                "score" => 84.74,
                "rank" => 7092,
                "properties" => array(
                    "property_name" => 'attributeCount',
                    "value" => 2,
                    "scoreContribution" => 21,
                    "supply" => 345
                )
            ),
            array(
                "id" => 1,
                "score" => 84.74,
                "rank" => 7092,
                "properties" => array(
                    "property_name" => 'attributeCount',
                    "value" => 2,
                    "scoreContribution" => 21,
                    "supply" => 345
                )
            )
        );

        $data = array(
            "collectionName" => "test",
            "collectionSize" => "test",
            "propertyCount" => "test",
            "items" => $collections
        );

        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
