<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionDataModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_data_models', function (Blueprint $table) {
            $table->increments('id');

             // foreign
             $table->integer('collection_id')->unsigned();
             $table->foreign('collection_id')
             ->references('id')
             ->on('collection_models')
             ->onDelete('cascade')
             ->onUpdate('cascade');

              // foreign
            $table->integer('collection_item_id')->unsigned();
            $table->foreign('collection_item_id')
            ->references('id')
            ->on('collection_item_models')
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
        Schema::dropIfExists('collection_data_models');
    }
}
