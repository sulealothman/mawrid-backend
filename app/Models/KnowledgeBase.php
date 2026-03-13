<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'description',
        'owner_id',
    ];

    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}
