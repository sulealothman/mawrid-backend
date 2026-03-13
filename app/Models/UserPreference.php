<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    //
    protected $fillable = [
        'user_id',
        'language',
        'dark_mode',
        'sidebar_collapse',
        'email_notifications',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
