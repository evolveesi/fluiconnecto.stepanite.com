<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string("Tpin")->nullable();
            $table->string("bhfId")->nullable();
            $table->string("custNo")->nullable();
            $table->string("custTpin")->nullable();
            $table->string("custNm")->nullable();
            $table->string("taxprSttsCd")->nullable();
            $table->string("prvncNm")->nullable();
            $table->string("dstrtNm")->nullable();
            $table->string("sctrNm")->nullable();
            $table->longText("Adrs")->nullable();
            $table->string("Email")->nullable();
            $table->string("faxNo")->nullable();
            $table->string("useYn")->nullable();
            $table->string("remark")->nullable();
            $table->string("regrNm")->nullable();
            $table->string("regrId")->nullable();
            $table->string("modrNm")->nullable();
            $table->string("modrId")->nullable();
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
        Schema::dropIfExists('customers');
    }
}
