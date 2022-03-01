<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('property_name');
            $table->string('value')->nullable();
            $table->integer('scoreContribution')->nullable();
            $table->integer('supply')->nullable();
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
        Schema::dropIfExists('properties_models');
    }
}
