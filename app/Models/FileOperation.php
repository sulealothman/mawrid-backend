<?php

namespace App\Models;

use App\Enums\FileOperationStatus;
use Illuminate\Database\Eloquent\Model;

class FileOperation extends Model
{
    protected $fillable = [
        'file_id',
        'owner_id',
        'status',
    ];

    protected $casts = [
        'status' => FileOperationStatus::class,
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
