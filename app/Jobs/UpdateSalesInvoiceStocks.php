<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\API\StockItemController;

class UpdateSalesInvoiceStocks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $zra_stock_item_array;

    public function __construct(array $zra_stock_item_array)
    {
        $this->zra_stock_item_array = $zra_stock_item_array;
    }

    public function handle()
    {
        StockItemController::interfaceStockItem($this->zra_stock_item_array);
    }
}
