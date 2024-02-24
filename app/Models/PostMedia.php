<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PostMedia extends Authenticatable
{
    use  HasFactory;

   public $table = 'post_medias';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'media_path',
        'media_type',
    ];
    protected $casts = [
        'media_path' => 'string', // Ensure the media_path attribute is cast to a string
    ];
    public function getMediaPathAttribute($value)
    {
        return $value != null ? config('app.media_base_url') . $value : $value;
    }

}
