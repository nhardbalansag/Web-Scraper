<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionItemModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_item_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('collection_item_id');
            $table->integer('score');
            $table->integer('rank');

            $table->integer('collection_id')->unsigned();
            $table->foreign('collection_id')
            ->references('id')
            ->on('collection_models')
            ->onDelete('cascade')
            ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_item_models');
    }
}
