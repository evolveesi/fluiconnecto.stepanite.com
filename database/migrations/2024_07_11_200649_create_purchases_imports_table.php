<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases_imports', function (Blueprint $table) {
            $table->id();
            $table->string('spplrTpin')->nullable();
            $table->string('spplrNm')->nullable();
            $table->string('spplrBhfId')->nullable();
            $table->string('spplrInvcNo')->nullable();
            $table->string('rcptTyCd')->nullable();
            $table->string('pmtTyCd')->nullable();
            $table->string('cfmDt')->nullable();
            $table->string('salesDt')->nullable();
            $table->string('stockRlsDt')->nullable();
            $table->string('totItemCnt')->nullable();
            $table->string('totTaxblAmt')->nullable();
            $table->string('totTaxAmt')->nullable();
            $table->string('totAmt')->nullable();
            $table->string('remark')->nullable();
            $table->string('itemSeq')->nullable();
            $table->string('itemCd')->nullable();
            $table->string('itemClsCd')->nullable();
            $table->string('itemNm')->nullable();
            $table->string('bcd')->nullable();
            $table->string('pkgUnitCd')->nullable();
            $table->string('pkg')->nullable();
            $table->string('qtyUnitCd')->nullable();
            $table->string('qty')->nullable();
            $table->string('prc')->nullable();
            $table->string('splyAmt')->nullable();
            $table->string('dcRt')->nullable();
            $table->string('dcAmt')->nullable();
            $table->string('vatCatCd')->nullable();
            $table->string('iplCatCd')->nullable();
            $table->string('tlCatCd')->nullable();
            $table->string('exciseTxCatCd')->nullable();
            $table->string('vatTaxblAmt')->nullable();
            $table->string('exciseTaxblAmt')->nullable();
            $table->string('iplTaxblAmt')->nullable();
            $table->string('tlTaxblAmt')->nullable();
            $table->string('taxblAmt')->nullable();
            $table->string('vatAmt')->nullable();
            $table->string('iplAmt')->nullable();
            $table->string('tlAmt')->nullable();
            $table->string('exciseTxAmt')->nullable();
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
        Schema::dropIfExists('purchases_imports');
    }
}
