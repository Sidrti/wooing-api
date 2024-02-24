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
    public function likes()
    {
        return $this->hasMany(Reaction::class)->where('reaction', 'like');
    }

    public function dislikes()
    {
        return $this->hasMany(Reaction::class)->where('reaction', 'dislike');
    }
}
