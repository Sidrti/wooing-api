<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name','admin_id','profile_picture','type'];

    public function users()
    {
        return $this->belongsToMany(User::class,'group_users');
    }
    public function groupUsers() 
    {
        return $this->hasMany(GroupUsers::class); 
    }


}
