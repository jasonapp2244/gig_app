<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListCategory extends Model
{
    protected $table = 'list_categories';
    protected $fillable = ['user_id', 'category', 'status'];
}
