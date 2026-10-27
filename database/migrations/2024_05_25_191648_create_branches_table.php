<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string("tin")->nullable();
            $table->string("bhfId")->nullable();
            $table->string("bhfNm")->nullable();
            $table->string("bhfSttsCd")->nullable();
            $table->string("prvncNm")->nullable();
            $table->string("dstrtNm")->nullable();
            $table->string("sctrNm")->nullable();
            $table->string("locDesc")->nullable();
            $table->string("mgrNm")->nullable();
            $table->string("mgrTelNo")->nullable();
            $table->string("mgrEmail")->nullable();
            $table->string("hqYn")->nullable();
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
        Schema::dropIfExists('branches');
    }
}
