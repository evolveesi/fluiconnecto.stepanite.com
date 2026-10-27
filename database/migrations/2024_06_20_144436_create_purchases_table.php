<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->unique();
            $table->enum('status', ['Pending', 'Interfaced'])->default('Pending');
            $table->string('resultCd')->default('');
            $table->string('resultMsg')->default('');
            $table->string('resultDt')->default('');
            $table->string('rcptNo')->default('');
            $table->string('intrlData')->default('');
            $table->string('rcptSign')->default('');
            $table->string('vsdcRcptPbctDate')->default('');
            $table->string('sdcId')->default('');
            $table->string('mrcNo')->default('');
            $table->string('qrCodeUrl')->default('');
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
        Schema::dropIfExists('purchases');
    }
}
