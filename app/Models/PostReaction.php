<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostReaction extends Model
{
use HasFactory;

const UPDATED_AT = null;

protected $fillable = ['post_id', 'user_id', 'type'];
}