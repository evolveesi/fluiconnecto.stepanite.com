<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemCompositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_compositions', function (Blueprint $table) {
            $table->id();
            $table->string("itemCd");
            $table->string("cpstItemCd")->nullable();
            $table->string("cpstQty")->nullable();
            $table->enum("status",["Interfaced","Pending"])->default("Pending");
            $table->longText("message")->nullable();
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
        Schema::dropIfExists('item_compositions');
    }
}
