<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'caption',
        'media_path',
        'media_type',
        'like_count',
        'dislike_count',
        'status',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postMedia()
    {
        return $this->hasMany(PostMedia::class, 'post_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
