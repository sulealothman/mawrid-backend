<?php

namespace App\Http\Controllers;

use App\Models\FileOperation;
use App\Enums\FileOperationStatus;
use Illuminate\Http\Request;

class FileOperationController extends Controller
{
    public function webhook(Request $request)
    {
        $this->verifyInternalKey($request);

        $request->validate([
            'operation_id' => ['required', 'integer'],
            'status' => ['required', 'in:processing,processed,failed'],
        ]);

        $operation = FileOperation::findOrFail($request->operation_id);

        if (! $this->isValidTransition(
            $operation->status->value,
            $request->status
        )) {
            return response()->json(['ignored' => true]);
        }

        $operation->update([
            'status' => FileOperationStatus::from($request->status),
        ]);

        return response()->json(['ok' => true]);
    }

    private function verifyInternalKey(Request $request): void
    {
        $incoming = $request->header('X-Internal-Key');

        if (!hash_equals(
            config('services.ai.internal_key'),
            (string) $incoming
        )) {
            abort(403, 'Unauthorized');
        }
    }

    private function isValidTransition(string $current, string $incoming): bool
    {
        return match ($current) {
            'queued'     => $incoming === 'processing',
            'processing' => in_array($incoming, ['processed', 'failed']),
            default      => false,
        };
    }
}
