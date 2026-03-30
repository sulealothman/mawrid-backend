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
        'parent_id',
        'role',
        'content',
        'sources',
        'usage',
        'status',
        'status_message',
    ];

    protected $casts = [
        'sources' => 'array',
        'usage'   => 'array',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function parent()
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }
}
