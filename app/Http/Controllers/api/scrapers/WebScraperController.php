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
        // "https://projects.rarity.tools/static/config/cryptopunks.json" //for config
        // $collection_api_url = 'https://api.opensea.io/api/v1/assets?asset_contract_address=0xb47e3cd837ddf8e4c57f05d70ab865de6e193bbb&limit=20&token_ids=8348';
        // $collection_json_data = file_get_contents($collection_api_url);
        // $collection_response_data = json_decode($collection_json_data);

        $config_data = "https://projects.rarity.tools/static/config/" . $request . ".json";
        $config_data_json_data = file_get_contents($config_data);
        $config_data_response_data = json_decode($config_data_json_data);

        // dd($config_data_response_data);

        $asset_contract_address = $config_data_response_data->contracts[0]->contract;
        $api_opensea_io_url = "https://api.opensea.io/api/v1/assets?asset_contract_address=" . $asset_contract_address . "&limit=20";
        $api_opensea_io_url_json_data = file_get_contents($api_opensea_io_url);
        $api_opensea_io_url_response_data = json_decode($api_opensea_io_url_json_data);
        dd($api_opensea_io_url_response_data);

        // $request_collection = 'cryptopunks';

        $exist = $this->CheckExist($request);

        if(!$exist){

            DB::beginTransaction();
            try{
                $collection_api_url = 'https://collections.rarity.tools/collectionDetails/' . $request;
                $collection_json_data = file_get_contents($collection_api_url);
                $collection_response_data = json_decode($collection_json_data);

                $collectionName = $collection_response_data->slug;
                $total_supply = $collection_response_data->stats->total_supply;
                $collection_id = null;
                $item_api_url = 'https://projects.rarity.tools/static/staticdata/' .  $request . '.json';

                $item_json_data = file_get_contents($item_api_url);
                $item_response_data = json_decode($item_json_data);
                $item_ids = $item_response_data->items;

                $item_info = $item_response_data->basePropDefs;
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

                    $CollectionItemModel = new CollectionItemModel;
                    $CollectionItemModel->collection_item_id = $item_ids[$i][0];
                    $CollectionItemModel->collection_id = $collection_id;
                    $CollectionItemModel->score = $total_score_value;
                    $CollectionItemModel->rank = ($i + 1);
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

                                $total_score_value = $total_score_value + number_format(($total_supply / $item_info[$index]->pvs[$data][1]), 2, '.', '');
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
                dd($data);
            }catch(Exception $err){
                DB::rollback();
                dd($err->getMessage());
            }
        }else{
            $data = $this->GetData($request);
            dd($data);
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
}
