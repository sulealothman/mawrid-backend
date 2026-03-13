<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'knowledge_base_id',
        'original_name',
        'storage_name',
        'bucket',
        'path',
        'mime_type',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function knowledgeBase()
{
    return $this->belongsTo(KnowledgeBase::class);
}


    public function operations()
    {
        return $this->hasMany(FileOperation::class);
    }

    public function latestOperation()
{
    return $this->hasOne(FileOperation::class)->latestOfMany();
}
}
