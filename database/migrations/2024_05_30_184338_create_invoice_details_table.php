<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->string('itemSeq')->nullable();
            $table->string('itemCd')->nullable();
            $table->string('itemClsCd')->nullable();
            $table->string('itemNm')->nullable();
            $table->string('bcd')->nullable();
            $table->string('pkgUnitCd')->nullable();
            $table->string('Pkg')->nullable();
            $table->string('qtyUnitCd')->nullable();
            $table->integer('qty')->nullable();
            $table->string('prc')->nullable();
            $table->double('splyAmt')->nullable();
            $table->string('dcRt')->nullable();
            $table->double('dcAmt')->nullable();
            $table->string('isrccCd')->nullable();
            $table->string('isrccNm')->nullable();
            $table->double('isrcAmt')->nullable();
            $table->string('vatCatCd')->nullable();
            $table->string('exciseTxCatCd')->nullable();
            $table->double('vatTaxblAmt')->nullable();
            $table->double('exciseTaxblAmt')->nullable();
            $table->double('tlTaxblAmt')->nullable();
            $table->double('iplTaxblAmt')->nullable();
            $table->double('iplAmt')->nullable();
            $table->double('tlAmt')->nullable();
            $table->double('vatAmt')->nullable();
            $table->double('exciseTxAmt')->nullable();
            $table->double('totAmt')->nullable();
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
        Schema::dropIfExists('invoice_details');
    }
}
