<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class listImage extends Model
{
    protected $table = 'list_images';
    protected $fillable = ['list_id', 'image_name', 'path', 'hash_name', 'status'];

    public function list()
    {
        return $this->belongsTo(ListStory::class, 'list_id');
    }
}
