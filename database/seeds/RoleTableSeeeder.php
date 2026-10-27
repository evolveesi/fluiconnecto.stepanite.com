<?php

use Illuminate\Database\Seeder;

class RoleTableSeeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createRoles();
    }

    protected function createRoles(){
        $roles = [
            [
                "name"=> 'Evolve Admin',
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
        ];
        \Illuminate\Support\Facades\DB::table("roles")->insert($roles);
    }
}
