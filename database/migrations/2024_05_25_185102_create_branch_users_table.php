<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_users', function (Blueprint $table) {
            $table->id();
            $table->string("userId")->nullable();
            $table->string("userNm")->nullable();
            $table->string("adrs")->nullable();
            $table->enum("useYn",["Y","N"])->default("Y");
            $table->string("regrNm")->default("Admin");
            $table->string("regrId")->default("Admin");
            $table->string("modrNm")->default("Admin");
            $table->string("modrId")->default("Admin");
            $table->string("resultCd")->nullable();
            $table->string("resultMsg")->nullable();
            $table->string("resultDts")->nullable();
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
        Schema::dropIfExists('branch_users');
    }
}
