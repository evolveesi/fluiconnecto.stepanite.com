<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Http\Controllers\Core\ItemController;

class CallControllerFunction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'controller:function';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $controller = new ItemController;
        while (false) {
            error_log('called job at '.time());
            $controller->batch_push_items();
            sleep(60); // 1 minutes
        }

    }
}
