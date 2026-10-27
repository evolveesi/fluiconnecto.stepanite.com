<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSearchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_searches', function (Blueprint $table) {
            $table->id();
            $table->string("custTpin")->nullable();
            $table->string("tpin")->nullable();
            $table->string("bhfId")->nullable();
            $table->string("custNo")->nullable();
            $table->string("custNm")->nullable();
            $table->longText("adrs")->nullable();
            $table->string("telNo")->nullable();
            $table->string("email")->nullable();
            $table->string("faxNo")->nullable();
            $table->string("useYn")->nullable();
            $table->string("remark")->nullable();
            $table->string("regrId")->nullable();
            $table->string("regrNm")->nullable();
            $table->string("modrId")->nullable();
            $table->string("modrNm")->nullable();
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
        Schema::dropIfExists('customer_searches');
    }
}
