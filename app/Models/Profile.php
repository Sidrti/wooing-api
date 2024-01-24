<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'sex',
        'marital_status',
        'religion',
        'looking_for',
        'drinking',
        'smoking',
        'user_id',
        'bio'
        // Add other fields as needed
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
