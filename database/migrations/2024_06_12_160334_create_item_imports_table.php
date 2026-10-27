<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_imports', function (Blueprint $table) {
            $table->id();
            $table->string("taskCd")->nullable();
            $table->string("dclDe")->nullable();
            $table->string("itemSeq")->nullable();
            $table->string("dclNo")->nullable();
            $table->string("hsCd")->nullable();
            $table->string("itemNm")->nullable();
            $table->string("imptItemsttsCd")->nullable();
            $table->string("orgnNatCd")->nullable();
            $table->string("exptNatCd")->nullable();
            $table->string("pkg")->nullable();
            $table->string("qtyUnitCd")->nullable();
            $table->string("totWt")->nullable();
            $table->string("netWt")->nullable();
            $table->string("spplrNm")->nullable();
            $table->string("agntNm")->nullable();
            $table->string("invcFcurAmt")->nullable();
            $table->string("invcFcurCd")->nullable();
            $table->string("invcFcurExcrt")->nullable();
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
        Schema::dropIfExists('item_imports');
    }
}
