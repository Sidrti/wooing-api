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
    // public function likes()
    // {
    //     return $this->hasMany(PostReaction::class)->where('reaction', 'like');
    // }

    // public function dislikes()
    // {
    //     return $this->hasMany(PostReaction::class)->where('reaction', 'dislike');
    // }

    public function postMedia()
    {
        return $this->hasMany(PostMedia::class, 'post_id', 'id');
    }
}
