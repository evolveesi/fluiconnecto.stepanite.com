<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string("itemCd");
            $table->string("itemClsCd");
            $table->string("pkgUnitCd");
            $table->string("sarTyCd");
            $table->string("qty");
            $table->string("splyAmt");
            $table->string("taxblAmt");
            $table->string("vatCatCd");
            $table->string("taxAmt");
            $table->string("totAmt");
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
        Schema::dropIfExists('stock_items');
    }
}
