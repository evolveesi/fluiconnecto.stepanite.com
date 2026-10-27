<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZRANoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('z_r_a_notices', function (Blueprint $table) {
            $table->id();
            $table->string("noticeNo")->nullable();
            $table->string("title")->nullable();
            $table->longText("cont")->nullable();
            $table->string("dtlUrl")->nullable();
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
        Schema::dropIfExists('z_r_a_notices');
    }
}
