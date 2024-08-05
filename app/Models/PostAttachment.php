<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostAttachment extends Model
{
     use HasFactory;

     const UPDATED_AT = null;

     protected $fillable = [
          'post_id',
          'name',
          'path',
          'mime',
          'size',
          'created_by',
     ];

     public function posts()
     {
          return $this->belongsToMany(Post::class);
     }
     protected static function boot()
     {
          parent::boot();

          static::deleted(function (self $model) {
               Storage::disk('public')->delete($model->path);
          });
     }
}
