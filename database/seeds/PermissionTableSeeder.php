<?php

use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createPermissions();
    }

    protected function createPermissions(){
        $permissions = [
            [
                "name"=>"view-users",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"user-management",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"update-user",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"store-user",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"store-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"view-permissions",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"update-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"view-roles",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"store-role",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"update-role",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"configure-role",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"remove-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "upphpdated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"push-items",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"batch-push-items",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"pull-items",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"view-items-report",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "name"=>"add-permission",
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
        ];
        \Illuminate\Support\Facades\DB::table("permissions")->insert($permissions);
    }
}
