<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ChatMessage extends Model
{
    use SoftDeletes;

    protected $with = [];

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'sources',
        'usage',
    ];

    protected $casts = [
        'sources' => 'array',
        'usage'   => 'array',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
}
