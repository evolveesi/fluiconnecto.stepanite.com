<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'baseUrl' => "http://localhost:8080/zraprodvsdc",
            'tinNumber' => "1001882723",
            'branchCode' => "000",
            'lastRequestDate' => "20230520141921",
            'created_at' => date('Y-m-d h:i:s'),
            'updated_at' => date('Y-m-d h:i:s'),
        ]);
    }
}





