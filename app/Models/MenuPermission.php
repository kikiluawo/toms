<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    protected $table = 'user_menu_permissions';

    protected $fillable = [
        'user_id',
        'menu_key',
    ];
}
