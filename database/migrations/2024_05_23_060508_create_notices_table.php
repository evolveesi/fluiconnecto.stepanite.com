<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string("resultCd")->nullable();
            $table->string("resultMsg")->nullable();
            $table->string("resultDt")->nullable();
            $table->string("noticeNo");
            $table->string("title");
            $table->string("cont");
            $table->string("dtlUrl");
            $table->string("regrNm")->nullable();
            $table->string("regDt")->nullable();
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
        Schema::dropIfExists('notices');
    }
}
