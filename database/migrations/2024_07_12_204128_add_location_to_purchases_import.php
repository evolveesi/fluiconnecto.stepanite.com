<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationToPurchasesImport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases_imports', function (Blueprint $table) {
            $table->string("location")->nullable();
            $table->string("termsCode")->nullable();
            $table->string("PurchaseOrderNumber")->nullable();
            $table->string("PurchaseOrderLineSequence")->nullable();
            $table->string("ItemSeqKey")->nullable();
            $table->string("vendorAccountSet")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases_import', function (Blueprint $table) {
            //
        });
    }
}
