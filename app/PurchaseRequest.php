<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $casts = [
        'zra_response' => 'json',
    ];
}
