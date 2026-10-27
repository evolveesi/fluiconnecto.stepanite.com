<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDetailFieldsFromInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table-> dropColumn('itemSeq');
            $table-> dropColumn('itemCd');
            $table-> dropColumn('itemClsCd');
            $table-> dropColumn('itemNm');
            $table-> dropColumn('bcd');
            $table-> dropColumn('pkgUnitCd');
            $table-> dropColumn('Pkg');
            $table-> dropColumn('qtyUnitCd');
            $table-> dropColumn('qty');
            $table-> dropColumn('prc');
            $table-> dropColumn('splyAmt');
            $table-> dropColumn('dcRt');
            $table-> dropColumn('dcAmt');
            $table-> dropColumn('isrccCd');
            $table-> dropColumn('isrccNm');
            $table-> dropColumn('isrcAmt');
            $table-> dropColumn('vatCatCd');
            $table-> dropColumn('exciseTxCatCd');
            $table-> dropColumn('vatTaxblAmt');
            $table-> dropColumn('exciseTaxblAmt');
            $table-> dropColumn('tlTaxblAmt');
            $table-> dropColumn('iplTaxblAmt');
            $table-> dropColumn('iplAmt');
            $table-> dropColumn('tlAmt');
            $table-> dropColumn('vatAmt');
            $table-> dropColumn('exciseTxAmt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice', function (Blueprint $table) {
            //
        });
    }
}
