<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSageItemCdToPurchasesImports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases_imports', function (Blueprint $table) {
            $table->string("sageItemCd")->nullable();
            $table->string("sageVendorCd")->nullable();
            $table->string("currency")->default("ZMW");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases_imports', function (Blueprint $table) {
            //
        });
    }
}
