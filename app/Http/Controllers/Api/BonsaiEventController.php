<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bonsai;
use App\Models\BonsaiImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonsaiEventController extends Controller
{
    public function store(Request $request, Bonsai $bonsai): JsonResponse
    {
        abort_unless($bonsai->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_description' => ['nullable', 'string', 'max:255'],
        ]);

        $event = $bonsai->events()->create([
            'date' => $validated['date'],
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('bonsais', 'public');
            BonsaiImage::create([
                'bonsai_id' => $bonsai->id,
                'image' => '/storage/'.$path,
                'date' => $validated['date'],
                'description' => $validated['image_description'] ?? $validated['type'],
            ]);
        }

        return response()->json([
            'event' => $event,
            'bonsai' => $bonsai->load(['events', 'images', 'treatments', 'calendarTasks']),
        ], 201);
    }
}
