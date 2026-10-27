<?php

namespace App\Console\Commands;

use App\Http\Controllers\Tasks\ItemImportsController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ItemsImportTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * php artisan import-items:task
     * crontab -e
     * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
     * storage/logs/schedule.log
     */

    protected $signature = 'import-items:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This Task Runs Daily at 9 O'clock to fetch imports and check if they are in the erp. If they don't exist they are created.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Items import task executed successfully at:'.date('Y-m-d h:i:s'));
        Log::alert('Items import task executed successfully at:'.date('Y-m-d h:i:s'));
        ItemImportsController::fetch_item_imports();
        return 0;
    }
}
