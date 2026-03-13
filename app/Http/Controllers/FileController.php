<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\KnowledgeBase;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FileOperation;
use App\Enums\FileOperationStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\DispatchFileOperationToWorker;

class FileController extends Controller
{
    public function index(Request $request, string $knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::where('id', $knowledgeBaseId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        return FileResource::collection(
            $knowledgeBase->files()->with('latestOperation')->get()
        );
    }

    public function show(Request $request, File $file)
    {
        abort_unless(
            $file->owner_id === $request->user()->id,
            403
        );

        return new FileResource($file->load('latestOperation'));
    }

    public function store(Request $request)
{
    $request->validate([
        'content' => 'required|string|max:1024',
        'filename' => 'nullable|string|max:255',
        'knowledge_base_id' => 'required|uuid',
    ]);

    $kb = KnowledgeBase::where('id', $request->knowledge_base_id)
        ->where('owner_id', $request->user()->id)
        ->firstOrFail();

    $file = DB::transaction(function () use ($request, $kb) {

        $content = $request->content;

        $originalName = $request->filename
            ? (Str::endsWith($request->filename, '.txt')
                ? $request->filename
                : $request->filename . '.txt')
            : 'text-' . now()->timestamp . '.txt';

        $storeName = (string) Str::uuid();

        $path = "docs/{$storeName}.txt";

        Storage::disk('s3')->put($path, $content);

        $file = File::create([
            'owner_id'          => $request->user()->id,
            'knowledge_base_id' => $kb->id,
            'original_name'     => $originalName,
            'storage_name'      => $storeName,
            'bucket'            => 'files',
            'path'              => $path,
            'mime_type'         => 'text/plain',
        ]);

        $operation = FileOperation::create([
            'file_id'  => $file->id,
            'owner_id' => $request->user()->id,
            'status'   => FileOperationStatus::Queued,
        ]);

        DispatchFileOperationToWorker::dispatch($operation->id)
            ->onQueue('ai-dispatch');

        return $file;
    });

    return new FileResource($file->load('latestOperation'));
}

    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimetypes:' . implode(',', config('files.allowed_mime_types')),
            ],
            'file_name' => 'nullable|string|max:255',
            'knowledge_base_id' => 'required|uuid',
        ]);

        $kb = KnowledgeBase::where('id', $request->knowledge_base_id)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $uploaded = $request->file('file');

        $file = DB::transaction(function () use ($request, $uploaded, $kb) {

            $storeName = (string) Str::uuid();

            $path = Storage::disk('s3')->putFileAs(
                'docs',
                $uploaded,
                "{$storeName}.{$uploaded->extension()}"
            );

            if (!$path) {
                throw new \Exception('Failed to upload file to storage.');
            }

            $file = File::create([
                'owner_id'          => $request->user()->id,
                'knowledge_base_id' => $kb->id,
                'original_name'     => $request->file_name ?? $uploaded->getClientOriginalName(),
                'storage_name'      => $storeName,
                'bucket'            => 'files',
                'path'              => $path,
                'mime_type'         => $uploaded->getMimeType(),
            ]);

            $operation = FileOperation::create([
                'file_id'  => $file->id,
                'owner_id' => $request->user()->id,
                'status'   => FileOperationStatus::Queued,
            ]);

            DispatchFileOperationToWorker::dispatch($operation->id)
                ->onQueue('ai-dispatch');

            return $file;
        });

        return new FileResource($file->load('latestOperation'));
    }

    public function update(Request $request, File $file)
    {
        $request->validate([
            'original_name' => 'required|string|max:255',
        ]);

        $file->update([
            'original_name' => $request->original_name,
        ]);

        return new FileResource($file->fresh('latestOperation'));
    }

    public function remove(File $file)
    {
        $file->delete();
        return response()->json(['message' => 'file_deleted_successfully']);
    }

    public function destroy(File $file)
    {
        $file->forceDelete();
        return response()->json(['message' => 'File permanently deleted successfully.']);
    }
}
