<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\FileOperation;
use App\Enums\FileOperationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchFileOperationToWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    private int $operationId;

    public function __construct(int $operationId)
    {
        $this->operationId = $operationId;
    }

    public function handle(): void
    {
        $operation = FileOperation::with('file')->find($this->operationId);

        if (!$operation) {
            return;
        }

        if ($operation->status !== FileOperationStatus::Queued) {
            return;
        }

        $file = $operation->file;

        if (!$file) {
            Log::error('File not found for operation', [
                'operation_id' => $operation->id
            ]);
            return;
        }

        Redis::xadd(
            config('services.ai.stream', 'ai:jobs'),
            '*',
            [
                'operation_id' => $operation->id,
                'file_id'      => $file->id,
                'kb_id'        => $file->knowledge_base_id,
                's3_bucket'    => config('filesystems.disks.s3.bucket'),
                's3_key'       => $file->path,
                'attempt'      => '1',
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchFileOperationToWorker failed', [
            'operation_id' => $this->operationId,
            'error' => $exception->getMessage(),
        ]);

        FileOperation::where('id', $this->operationId)
            ->update(['status' => FileOperationStatus::Failed]);
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
