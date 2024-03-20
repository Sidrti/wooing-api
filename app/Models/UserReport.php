<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reason',
        'media_path',
        'status'
    ];
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportee()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
    public function getMediaPathAttribute($value)
    {
        return $value != null ? config('app.media_base_url') . $value : $value;
    }
}
?>
