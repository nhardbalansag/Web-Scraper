<?php
namespace App\Http\Controllers\api\scrapers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Abraham\TwitterOAuth\TwitterOAuth;
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
            $limit = $limit > $this->CONFIG_LIMIT ? $this->CONFIG_LIMIT : $limit;
            foreach($collection_item as $key => $item){
                $inc++;
                if($from_func){
                    if(!$set){
                        $set = ($inc - 1) === ($offset > 0 ? ($offset - 1) : 0) ? true : false;
                    }
                    if($set && $limit >= 0){
                        array_push(
                            $sorting_array,
                            array(
                                "id" => $item['data']['id'],
                                "score" => $item['data']['score'],
                                "rank" => $inc,
                                "properties" => $item['data']['properties']
                            )
                        );
                        $limit--;
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

        public function UpcomingCollection(){
            try{
                $json = file_get_contents('https://api.sheety.co/9b0f8dd0e1d482e54c7fdf1a0f1c64a0/apiTest/sheet1');

                $data = json_decode($json);
                $return_data_arr = array();
                $twitterFollower = 0;
                $discordFollower = 0;
                $cover = null;
                $twitterlink = null;
                foreach($data->sheet1 as $index => $element){

                    try{
                        $discordname = !empty($element->discord) ? explode('/', $element->discord)[count(explode('/', $element->discord)) - 1] : null;
                        if(!empty($discordname)){
                            $discrod_get_url = file_get_contents('https://discord.com/api/v9/invites/' . $discordname . '?with_counts=true&with_expiration=true');
                            $discordJSON = json_decode($discrod_get_url);
                            $discordFollower = $discordJSON->approximate_member_count;
                        }
                    }catch(Exception $err){
                        $discrod_get_url = null;
                    }

                    if(!empty($element->twitter) && substr_count($element->twitter, '/') === 3){
                        $connection = new TwitterOAuth(
                            "gDg8mP5kVf70FiYIhYdmaPFbo",
                            "yYRMMi42UGb3163Y6s0zVxRqXpX1T9acDaYSTQuWvO8JuBQ5xb",
                            "1488519798397239297-XQWLl6dpDMPBESCEs9OQY6SWLykyXb",
                            "TKKPLeX6olRhKu7ue7jD6FWJhY53hEtJeqf7TsrYru2aX"
                        );
                        $Twitter_response = $connection->get('statuses/user_timeline', ['screen_name' => explode('/', $element->twitter)[3]]);
                        if(is_array($Twitter_response)){
                            $twitterFollower = $Twitter_response[0]->user->followers_count;
                            $cover = $Twitter_response[0]->user->profile_image_url_https;
                            $temp_link = null;
                            for($i = 0; $i < (count(explode('.', $cover)) - 1); $i++){
                                if($i !== (count(explode('.', $cover)) - 1)){
                                    $temp_link = $temp_link . explode('.', $cover)[$i] . '.';
                                }
                            }

                            $t_user = explode('/', $temp_link)[count(explode('/', $temp_link)) - 1];
                            $t_user = explode('_', $t_user)[0];
                            $t_user = $t_user . "_bigger.jpg";
                            $base = null;
                            for($i = 0; $i < (count(explode('/', $temp_link)) - 1); $i++){
                                if($i !== (count(explode('/', $temp_link)) - 1)){
                                    $base = $base . explode('/', $temp_link)[$i] . '/';
                                }
                            }
                            $twitterlink = (string)$element->twitter;
                            $cover = $base . $t_user;
                        }
                    }

                    $supply = (string)$element->supply;
                    $supply_data = "";
                    for($i = 0; $i < strlen($supply); $i++){
                        if($supply[$i] !== ","){
                            $supply_data = $supply_data . $supply[$i];
                        }
                    }

                    $previews = array();
                    $prev_data_condition = array();

                    foreach($element as $index_data => $element_data){
                        if(str_contains($element_data, 'https://drive.google.com')){
                            array_push($prev_data_condition, $element_data);
                        }
                    }

                    $base = "https://drive.google.com/uc?id=";
                    $link_data = "";
                    foreach($prev_data_condition as $index_data => $item){
                        if(!empty($item) && $item !== " " && substr_count($item, '/') === 6){
                            $link_data = explode('/', $item)[5];
                            array_push($previews, $base . $link_data);
                            $link_data = "";
                        }
                    }

                    $releaseDate = Carbon::createFromFormat('Y-m-d H:i:s', '2022-02-12 00:00:00')->timestamp;

                    $presalePrice = empty($element->presalePrice) ? null : ($element->presalePrice === " " ? null : $element->presalePrice);

                    $return_data = array(
                        "id" => (int)$element->id,
                        "name" => (string)$element->name,
                        "description" => (string)$element->description,
                        "platform" => (string)$element->platform,
                        "mintPrice" => (string)$element->mintPrice,
                        "presalePrice" => $presalePrice,
                        "supply" => (int)$supply_data,
                        "releaseDate" => (int)$releaseDate,
                        "twitter" => $twitterlink,
                        "discord" => (string)$element->discord,
                        "website" => (string)$element->website,
                        "cover" => $cover ? $cover : ($previews ? $previews[0] : null),
                        "preview" => $previews,
                        "twitterFollower" => $twitterFollower,
                        "discordFollower" => $discordFollower
                    );

                    array_push(
                        $return_data_arr,
                        $return_data
                    );
                }
            }catch(Exception $mainErr){
                return response()->json(
                    [
                        "message" => $mainErr->getMessage(),
                        "status" => false
                    ],  500, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT
                );
            }
            return response()->json($return_data_arr,  200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        }
    }

