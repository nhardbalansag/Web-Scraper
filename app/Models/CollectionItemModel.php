<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionItemModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_item_id',
        'score',
        'collection_id'
    ];
}
