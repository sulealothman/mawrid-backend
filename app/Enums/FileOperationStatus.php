<?php 

namespace App\Enums;


enum FileOperationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Canceled = 'canceled';
}
