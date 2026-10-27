<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AllowSplitInvoiceAndCreditNoteIds extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE sales_invoices MODIFY invoice_id VARCHAR(191) NOT NULL');
        DB::statement('ALTER TABLE credit_notes MODIFY credit_note_id VARCHAR(191) NOT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE sales_invoices MODIFY invoice_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE credit_notes MODIFY credit_note_id BIGINT UNSIGNED NOT NULL');
    }
}
