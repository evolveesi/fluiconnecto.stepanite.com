<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tpin')->nullable();
            $table->unsignedBigInteger('bhfId')->nullable();
            $table->string('orgIncNo',25)->nullable();
            $table->string('cisInvcNo',25)->nullable();
            $table->unsignedBigInteger('custTpin')->nullable();
            $table->unsignedBigInteger('custNm')->nullable();
            $table->string('salesTyCd',25)->nullable();
            $table->string('rcptTyCd',25)->nullable();
            $table->string('pmtTyCd',25)->nullable();
            $table->string('salesSttsCd',25)->nullable();
            $table->string('cfmDt',25)->nullable();
            $table->string('salesDt',25)->nullable();
            $table->string('stockRlsDt',25)->nullable();
            $table->string('cnclReqDt',25)->nullable();
            $table->string('cnclDt',25)->nullable();
            $table->string('rfdDt',25)->nullable();
            $table->string('rfdRsnCd',25)->nullable();
            $table->integer('totItemCnt')->nullable();
            $table->double('taxblAmtA')->nullable();
            $table->double('taxblAmtB')->nullable();
            $table->double('taxblAmtC')->nullable();
            $table->double('taxblAmtC1')->nullable();
            $table->double('taxblAmtC2')->nullable();
            $table->double('taxblAmtC3')->nullable();
            $table->double('taxblAmtD')->nullable();
            $table->double('taxblAmtRvat')->nullable();
            $table->double('taxblAmtE')->nullable();
            $table->double('taxblAmtF')->nullable();
            $table->double('taxblAmtIpl1')->nullable();
            $table->double('taxblAmtIpl2')->nullable();
            $table->double('taxblAmtTl')->nullable();
            $table->double('taxblAmtEcm')->nullable();
            $table->double('taxblAmtExeeg')->nullable();
            $table->double('taxblAmtTot')->nullable();
            $table->double('taxRtA')->nullable();
            $table->double('taxRtB')->nullable();
            $table->double('taxRtC1')->nullable();
            $table->double('taxRtC2')->nullable();
            $table->double('taxRtC3')->nullable();
            $table->double('taxRtD')->nullable();
            $table->double('taxRtRvat')->nullable();
            $table->double('taxRtE')->nullable();
            $table->double('taxRtF')->nullable();
            $table->double('taxRtIpl1')->nullable();
            $table->double('taxRtIpl2')->nullable();
            $table->double('taxRtTl')->nullable();
            $table->double('taxRtEcm')->nullable();
            $table->double('taxRtExeeg')->nullable();
            $table->double('taxRtTot')->nullable();
            $table->double('taxAmtA')->nullable();
            $table->double('taxAmtB')->nullable();
            $table->double('taxAmtC')->nullable();
            $table->double('taxAmtC1')->nullable();
            $table->double('taxAmtC2')->nullable();
            $table->double('taxAmtC3')->nullable();
            $table->double('taxAmtD')->nullable();
            $table->double('taxAmtRvat')->nullable();
            $table->double('taxAmtE')->nullable();
            $table->double('taxAmtF')->nullable();
            $table->double('taxAmtIpl1')->nullable();
            $table->double('taxAmtIpl2')->nullable();
            $table->double('taxAmtTl')->nullable();
            $table->double('taxAmtEcm')->nullable();
            $table->double('taxAmtExeeg')->nullable();
            $table->double('taxAmtTot')->nullable();
            $table->double('totTaxblAmt')->nullable();
            $table->double('totTaxAmt')->nullable();
            $table->string('prchrAcptcYn')->nullable();
            $table->string('remark')->nullable();
            $table->string('regrId')->nullable();
            $table->string('regrNm')->nullable();
            $table->string('modrId')->nullable();
            $table->string('modrNm')->nullable();
            $table->string('saleCtyCd')->nullable();
            $table->string('lpoNumber')->nullable();
            $table->string('currencyTyCd')->nullable();
            $table->double('exchangeRt')->nullable();
            $table->string('destnCountryCd')->nullable();
            $table->string('dbtRsnCd')->nullable();
            $table->string('invcAdjustReason')->nullable();
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
        Schema::dropIfExists('invoices');
    }
}
