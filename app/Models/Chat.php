<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'title', 'title_generated', 'knowledge_base_id', 'owner_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}