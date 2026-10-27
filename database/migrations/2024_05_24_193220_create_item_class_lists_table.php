<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemClassListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_class_lists', function (Blueprint $table) {
            $table->id();
            // "itemClsCd": "15000000",
            $table->string("itemClsCd")->nullable();
            // "itemClsNm": "Fuels and Fuel Additives and Lubricants and Anti corrosive Materials",
            $table->string("itemClsNm")->nullable();
            // "itemClsLvl": 1,
            $table->string("itemClsLvl")->nullable();
            // "taxTyCd": null,
            $table->string("taxTyCd")->nullable();
            // "mjrTgYn": null,
            $table->string("mjrTgYn")->nullable();
            // "useYn": "Y"
            $table->string("useYn")->nullable();
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
        Schema::dropIfExists('item_class_lists');
    }
}
