<?php

namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CollectionModel;
use App\Models\CollectionItemModel;
use App\Models\CollectionDataModel;
use App\Models\PropertiesModel;
use DB;
use Exception;

class WebScraperController extends Controller
{
    public function GetCollection($request){
        $data = null;
        $collectionName = null;
        $collectionSize = 0;
        $propertyCount = 0;

        $collection_item_id = null;
        $rank = null;
        $score = 0;

        $property_name = null;
        $value = null;
        $scoreContribution = 0;
        $supply = null;

        $property_count = 0;

        $collection_item = array();
        $property_item = array();
        $sorting_array = array();

        try{
            $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $request;
            $collection_json_data = file_get_contents($collection_api_url);
            $collection_response_data = json_decode($collection_json_data);

            $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $request . '.json';

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

            foreach($collection_item as $key => $item){
                array_push(
                    $sorting_array,
                    array(
                        "id" => $item['data']['id'],
                        "score" => $item['data']['score'],
                        "rank" => ($inc + 1),
                        "properties" => $item['data']['properties']
                    )
                );
            }

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
                    "status" => "Not Found"
                ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
            );
        }
    }

    public function GetCollectionAPI($request){

        $exist = $this->CheckExist($request);

        if(!$exist){
            DB::beginTransaction();
            try{

                $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $request;
                $collection_json_data = file_get_contents($collection_api_url);
                $collection_response_data = json_decode($collection_json_data);

                $collectionName = $collection_response_data->slug;
                $collection_id = null;

                $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $request . '.json';

                $item_json_data = file_get_contents($item_api_url);
                $item_response_data = json_decode($item_json_data);
                $item_ids = $item_response_data->items;
                $item_info = $item_response_data->basePropDefs;

                $total_supply = $collection_response_data->stats->total_supply;

                $property_count = 0;
                for($i = 0; $i < count($item_info); $i++){
                    if($item_info[$i]->type === "category"){
                        $property_count++;
                    }
                }

                $collection = new CollectionModel;
                $collection->collectionName = $collectionName;
                $collection->collectionSize = count($item_ids);
                $collection->propertyCount = $property_count;
                $collection->save();

                $collection_id = $collection->id;

                for($i = 0; $i < count($item_ids); $i++){
                    $total_score_value = 0;

                    for($j = 0; $j < count($item_ids[$i]); $j++){
                        if($item_ids[$i][$j] >= 0 && is_int($item_ids[$i][$j])){
                            if($item_info[$j]->type === "category"){
                                $data = $item_ids[$i][$j];
                                $index = $j;

                                $total_score_value = $total_score_value + number_format(($total_supply / $item_info[$index]->pvs[$data][1]), 2, '.', '');
                            }
                        }
                    }

                    $CollectionItemModel = new CollectionItemModel;
                    $CollectionItemModel->collection_item_id = $item_ids[$i][0];
                    $CollectionItemModel->collection_id = $collection_id;
                    $CollectionItemModel->rank = ($i + 1);
                    $CollectionItemModel->score = $total_score_value; // total score is the addition of all scores per traits
                    $CollectionItemModel->save();
                    for($j = 0; $j < count($item_ids[$i]); $j++){
                        if($item_ids[$i][$j] >= 0 && is_int($item_ids[$i][$j])){
                            if($item_info[$j]->type === "category"){
                                $data = $item_ids[$i][$j];
                                $index = $j;

                                $PropertiesModel = new PropertiesModel;
                                $PropertiesModel->property_name = $item_info[$index]->name;
                                $PropertiesModel->value = $item_info[$index]->pvs[$data][0];
                                $PropertiesModel->scoreContribution = number_format(($total_supply / $item_info[$index]->pvs[$data][1]), 2, '.', '');
                                $PropertiesModel->supply = $item_info[$index]->pvs[$data][1];
                                $PropertiesModel->collection_item_id = $CollectionItemModel->id;
                                $PropertiesModel->save();
                            }
                        }
                    }

                    $CollectionDataModel = new CollectionDataModel;
                    $CollectionDataModel->collection_id = $collection_id;
                    $CollectionDataModel->collection_item_id = $CollectionItemModel->id;
                    $CollectionDataModel->save();
                }

                DB::commit();
                $data = $this->GetData($request);

            }catch(Exception $err){
                DB::rollback();
                return response()->json(
                    [
                        "message" => $err->getMessage(),
                        "status" => "Not Found"
                    ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
                );
            }
        }else{
            $data = $this->GetData($request);
        }
        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    public function GetData($request){
        $collections = array();
        $properties = array();

        $exist = $this->CheckExist($request);

        $collection_item = DB::table('collection_item_models')
                        ->join('collection_models', 'collection_models.id', '=', 'collection_item_models.collection_id')
                        ->select('collection_item_models.*')
                        ->where('collection_models.id', $exist->id)
                        ->get();

        foreach($collection_item as $index => $data_collection_item){
            $properties_models = DB::table('properties_models')
                        ->join('collection_item_models', 'collection_item_models.id', '=', 'properties_models.collection_item_id')
                        ->join('collection_models', 'collection_models.id', '=', 'collection_item_models.collection_id')
                        ->select('properties_models.*', 'collection_item_models.score', 'collection_item_models.rank' , 'collection_item_models.collection_item_id')
                        ->where('collection_models.id', $exist->id)
                        ->where('properties_models.collection_item_id', $data_collection_item->id)
                        ->get();

            foreach($properties_models as $index => $data){
                array_push(
                    $properties,
                    array(
                        "property_name" => $data->property_name,
                        "value" => $data->value,
                        "scoreContribution" => $data->scoreContribution,
                        "supply" => $data->supply
                    )
                );
            }

            array_push(
                $collections,
                array(
                    "id" => $data->collection_item_id,
                    "score" => $data->score,
                    "rank" => "pending",
                    "properties" => $properties
                )
            );

            unset($properties);
            $properties = array();
        }

        $data = array(
            "collectionName" => $exist->collectionName,
            "collectionSize" => $exist->collectionSize,
            "propertyCount" => $exist->propertyCount,
            "items" => $collections
        );

        return $data;
    }

    public function CheckExist($request){
        $data = DB::table('collection_models')
                ->where('collectionName', $request)
                ->first();

        return $data;
    }

    public function GetScrapeData($request){

        $data = null;
        $collectionName = null;
        $collectionSize = 0;
        $propertyCount = 0;

        $collection_item_id = null;
        $rank = null;
        $score = 0;

        $property_name = null;
        $value = null;
        $scoreContribution = 0;
        $supply = null;

        $property_count = 0;

        $collection_item = array();
        $property_item = array();
        $sorting_array = array();

        try{
            $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $request;
            $collection_json_data = file_get_contents($collection_api_url);
            $collection_response_data = json_decode($collection_json_data);

            $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $request . '.json';

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

            foreach($collection_item as $key => $item){
                array_push(
                    $sorting_array,
                    array(
                        "id" => $item['data']['id'],
                        "score" => $item['data']['score'],
                        "rank" => ($inc + 1),
                        "properties" => $item['data']['properties']
                    )
                );
            }

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
                    "status" => "Not Found"
                ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
            );
        }
        return response()->json($data,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
}
