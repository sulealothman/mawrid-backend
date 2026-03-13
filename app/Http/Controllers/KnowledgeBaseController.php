<?php

namespace App\Http\Controllers;

use App\Http\Resources\KnowledgeBaseResource;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $knowledgeBases = KnowledgeBase::withCount('files')
            ->where('owner_id', $request->user()->id)
            ->get();

        return KnowledgeBaseResource::collection($knowledgeBases);
    }

    public function show(Request $request, string $knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::withCount(['files', 'chats'])
            ->with([
                'files.latestOperation',
                'chats' => function ($query) {
                    $query->latest('created_at');
                },
            ])
            ->where('id', $knowledgeBaseId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(new KnowledgeBaseResource($knowledgeBase));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $knowledgeBase = KnowledgeBase::create([
            'id'          => (string) Str::uuid(),
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'owner_id'    => $request->user()->id,
        ]);

        return (new KnowledgeBaseResource($knowledgeBase))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::where('id', $knowledgeBaseId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $knowledgeBase->update($validated);
        return new KnowledgeBaseResource($knowledgeBase);
    }

    public function remove(Request $request, string $knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::where('id', $knowledgeBaseId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $knowledgeBase->delete();

        return response()->json(['message' => 'Knowledge base deleted successfully.']);
    }

    public function destroy(Request $request, string $knowledgeBaseId)
    {
        $knowledgeBase = KnowledgeBase::withTrashed()
            ->where('id', $knowledgeBaseId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $knowledgeBase->forceDelete();

        return response()->json(['message' => 'Knowledge base permanently deleted.']);
    }
}
