<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createUsers();
    }

    protected function createUsers(){
        $password = \Illuminate\Support\Facades\Hash::make('admin@stepanite.com');
        $users = [
            [
                "name"=>"Fluiconnecto Admin",
                "email"=>"admin@stepanite.com",
                "password"=>$password,
                "role_id" => 1,
                "status" => "active",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
        ];
        \Illuminate\Support\Facades\DB::table("users")->insert($users);
    }

}

