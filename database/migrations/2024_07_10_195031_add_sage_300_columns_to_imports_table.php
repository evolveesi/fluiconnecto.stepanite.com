<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSage300ColumnsToImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_imports', function (Blueprint $table) {
            $table->string("payment_type")->nullable();
            $table->string("location")->nullable();
            $table->string("terms_code")->nullable();
            $table->string("vendor_account_set")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_imports', function (Blueprint $table) {
            //
        });
    }
}
