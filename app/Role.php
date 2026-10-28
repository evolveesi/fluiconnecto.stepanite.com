<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    use Notifiable;
    public function number_of_permissions()
    {
        $number_assigned = DB::select("select count(*) as items from role_permissions where role_id=?", [$this->id]);
        return $number_assigned[0]->items;
    }

    public function number_of_users()
    {
        $number_assigned = DB::select("select count(*) as items from users where role_id=?", [$this->id]);
        return $number_assigned[0]->items;
    }

    public function role_permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id')->latest()->orderByDesc('id');
    }

    public function missing_permissions()
    {

        $permissions = DB::select("select * from permissions where id not in (select permission_id as id from role_permissions where role_id=?) order by id desc", [$this->id]);
        return $permissions;
    }
}
