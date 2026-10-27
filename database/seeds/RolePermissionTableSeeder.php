<?php

use Illuminate\Database\Seeder;

class RolePermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createRolePermissions();
    }

    protected function createRolePermissions(){
        $role_permissions = [
            [
                "role_id"=> 1,
                "permission_id"=> 1,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 2,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 3,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 4,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 5,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 6,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 7,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 8,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 9,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 10,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 11,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 12,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
            [
                "role_id"=> 1,
                "permission_id"=> 13,
                "created_at"=>date('Y-m-d h:i:s'),
                "updated_at"=>date('Y-m-d h:i:s'),
            ],
        ];
        \Illuminate\Support\Facades\DB::table("role_permissions")->insert($role_permissions);
    }
}
