<?php

namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\HttpClient\HttpClient;

class WebScraperController extends Controller
{
    public function GetCollection(){
        $client = new Client(HttpClient::create(['timeout' => 10000]));
        // $client = new Client(HttpClient::create(['timeout' => 60]));
        // $url =  "https://rarity.tools/cryptopunks";
        // $page = $client->request('GET', $url);

        // $collectionSize = $page->filter("div");

        // $collectionSize->each(function($item){
        //     dump($item->filter('div')->text());
        // });
        $client->setServerParameter('accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
        $client->setServerParameter('accept-encoding', 'gzip, deflate, br');
        $client->setServerParameter('accept-language', 'en-GB,en-US;q=0.9,en;q=0.8');
        $client->setServerParameter('upgrade-insecure-requests', '1');
        $client->setServerParameter('user-agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36');
        $client->setServerParameter('connection', 'keep-alive');

        $crawler = $client->request('GET', 'https://rarity.tools/cryptopunks');

        $collectionSize = $crawler->filter("div");

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
        // $collectionName = $page->filter("h1")->text();
        // $collectionSize = $page->filter("div");
        // $propertyCount = null;
        // $collectionItem = null;

        array_push(
            $properties,
            array(
                "property_name" => 'test property name 1',
                "value" => 2,
                "scoreContribution" => 21,
                "supply" => 345
            ),
            array(
                "property_name" => 'est property name 2',
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
            "collectionName" => "test",
            "collectionSize" => "test",
            "propertyCount" => "test",
            "items" => $collections
        );

        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
