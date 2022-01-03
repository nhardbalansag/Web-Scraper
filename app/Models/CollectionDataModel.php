<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionDataModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'collection_item_id'
    ];
}
