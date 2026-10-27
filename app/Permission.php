<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Permission extends Model
{
    public function number_of_roles()
    {
        $number_assigned = DB::select("select count(*) as items from role_permissions where permission_id=?", [$this->id]);
        return $number_assigned[0]->items;
    }
}
