<?php

namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

class WebScraperController extends Controller
{
    public $CONFIG_LIMIT = 50;

    public function SortToArray($item_ids, $item_info, $total_supply, $limit, $offset, $from_func){

        $collection_item_id = null;
        $rank = null;
        $score = 0;

        $property_name = null;
        $value = null;
        $scoreContribution = 0;
        $supply = null;

        $property_item = array();
        $collection_item = array();
        $sorting_array = array();

        for($i = 0; $i < count($item_ids); $i++){
            $total_score_value = 0;

            for($j = 0; $j < count($item_ids[$i]); $j++){
                if($item_ids[$i][$j] >= 0 && is_int($item_ids[$i][$j])){
                    if($item_info[$j]->type === "category"){
                        $data = $item_ids[$i][$j];
                        $index = $j;

                        $total_score_value = $total_score_value + number_format(($total_supply / ($item_info[$index]->pvs[$data][1])), 2, '.', '');
                    }
                }
            }

            $collection_item_id = $item_ids[$i][0];
            $rank = "pending";
            $score = $total_score_value;

            for($j = 0; $j < count($item_ids[$i]); $j++){
                if($item_ids[$i][$j] >= 0 && is_int($item_ids[$i][$j])){
                    if($item_info[$j]->type === "category"){
                        $data = $item_ids[$i][$j];
                        $index = $j;

                        $property_name = $item_info[$index]->name;
                        $value = $item_info[$index]->pvs[$data][0];
                        $scoreContribution = number_format((((double)$total_supply) / ((double)$item_info[$index]->pvs[$data][1])), 2, '.', '');
                        $supply = $item_info[$index]->pvs[$data][1];

                        array_push(
                            $property_item,
                            array(
                                "property_name" => $property_name,
                                "value" => $value,
                                "scoreContribution" => (double)$scoreContribution,
                                "supply" => $supply
                            )
                        );
                    }
                }
            }

            array_push(
                $collection_item,
                array(
                    "score" => $score,
                    "data" => array(
                        "id" => $collection_item_id,
                        "score" => $score,
                        "rank" => $rank,
                        "properties" => $property_item
                    )
                )
            );

            unset($property_item);
            $property_item = array();
        }

        arsort($collection_item);
        $inc = 0;
        $set = false;
        foreach($collection_item as $key => $item){
            $inc++;
            if($from_func){
                if(!$set){
                    $set = ($inc - 1) === ($offset > 0 ? ($offset - 1) : 0) ? true : false;
                }
                if($set && ($inc - 1) <= (count(range($offset, $limit > 0 ? ($limit - 1) : 0)) > ($this->CONFIG_LIMIT - 1) ? ($this->CONFIG_LIMIT - 1) : ($limit - 1))){
                    array_push(
                        $sorting_array,
                        array(
                            "id" => $item['data']['id'],
                            "score" => $item['data']['score'],
                            "rank" => $inc,
                            "properties" => $item['data']['properties']
                        )
                    );
                }
            }else{
                array_push(
                    $sorting_array,
                    array(
                        "id" => $item['data']['id'],
                        "score" => $item['data']['score'],
                        "rank" => $inc,
                        "properties" => $item['data']['properties']
                    )
                );
            }
        }
        return $sorting_array;
    }

    public function GetOneCollectionItem($collection, $item_id){
        $data = null;
        $collectionName = null;
        $collectionSize = 0;
        $propertyCount = 0;
        $from_func = false;
        $collection_item_id = null;

        $property_count = 0;

        $collection_item = array();
        $request_data = array();

        try{
            $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $collection;
            $collection_json_data = file_get_contents($collection_api_url);
            $collection_response_data = json_decode($collection_json_data);

            try{
                $item_api_url = 'https://projects.rarity.tools/static/data/' .  $collection . '.json';
                $item_json_data = file_get_contents($item_api_url);
            }catch(Exception $jsonerror){
                $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $collection . '.json';
                $item_json_data = file_get_contents($item_api_url);
            }

            $item_response_data = json_decode($item_json_data);
            $item_ids = $item_response_data->items;
            $item_info = $item_response_data->basePropDefs;

            $total_supply = $collection_response_data->stats->total_supply;

            for($i = 0; $i < count($item_info); $i++){
                if($item_info[$i]->type === "category"){
                    $property_count++;
                }
            }

            $collectionName = $collection_response_data->slug;
            $collectionSize = count($item_ids);
            $propertyCount = $property_count;

            $collection_item = $this->SortToArray($item_ids, $item_info, $total_supply, $limit = null, $offset = null, $from_func);

            foreach($collection_item as $key => $item){
                $collection_item_id = $item['id'];
                if($collection_item_id === $item_id){
                    array_push(
                        $request_data,
                        array(
                            "id" => $item['id'],
                            "score" => $item['score'],
                            "rank" => $item['rank'],
                            "properties" => $item['properties']
                        )
                    );
                }
            }

            $data = array(
                "collectionName" => $collectionName,
                "collectionSize" => $collectionSize,
                "propertyCount" => $propertyCount,
                "items" => $request_data
            );

        }catch(Exception $err){
            return response()->json(
                [
                    "message" => $err->getMessage(),
                    "status" => false
                ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
            );
        }
        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    public function GetScrapeData(Request $request_data, $request){
        $offset = array_key_exists("offset", $request_data->all()) ? $request_data->offset : 0;
        $limit = array_key_exists("limit", $request_data->all()) ? $request_data->limit : $this->CONFIG_LIMIT;
        $data = null;
        $collectionName = null;
        $collectionSize = 0;
        $propertyCount = 0;

        $from_func = true;

        $property_count = 0;
        $sorting_array = array();
        try{

            $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $request;
            $collection_json_data = file_get_contents($collection_api_url);
            $collection_response_data = json_decode($collection_json_data);

            try{
                $item_api_url = 'https://projects.rarity.tools/static/data/' .  $request . '.json';
                $item_json_data = file_get_contents($item_api_url);
            }catch(Exception $jsonerror){
                $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $request . '.json';
                $item_json_data = file_get_contents($item_api_url);
            }

            $item_json_data = file_get_contents($item_api_url);
            $item_response_data = json_decode($item_json_data);
            $item_ids = $item_response_data->items;
            $item_info = $item_response_data->basePropDefs;

            $total_supply = $collection_response_data->stats->total_supply;

            for($i = 0; $i < count($item_info); $i++){
                if($item_info[$i]->type === "category"){
                    $property_count++;
                }
            }

            $collectionName = $collection_response_data->slug;
            $collectionSize = count($item_ids);
            $propertyCount = $property_count;

            $sorting_array = $this->SortToArray($item_ids, $item_info, $total_supply, $limit, $offset, $from_func);

            $data = array(
                "collectionName" => $collectionName,
                "collectionSize" => $collectionSize,
                "propertyCount" => $propertyCount,
                "items" => $sorting_array
            );

        }catch(Exception $err){
            return response()->json(
                [
                    "message" => $err->getMessage(),
                    "status" => false
                ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
            );
        }
        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    public function SingleAsset($address, $id){
        try{

            $url = 'https://api.opensea.io/api/v1/assets?asset_contract_address=' . $address . '&token_ids=' . $id;
            $content = file_get_contents($url);
            $response = json_decode($content)->assets[0];

        }catch(Exception $err){
            return response()->json(
                [
                    "message" => $err->getMessage(),
                    "status" => false
                ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
            );
        }

        return response()->json($response,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
