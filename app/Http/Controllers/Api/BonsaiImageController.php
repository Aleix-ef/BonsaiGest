<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bonsai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonsaiImageController extends Controller
{
    public function store(Request $request, Bonsai $bonsai): JsonResponse
    {
        abort_unless($bonsai->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'image_file' => ['required', 'image', 'max:5120'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image_file')->store('bonsais', 'public');
        $image = $bonsai->images()->create([
            'image' => '/storage/'.$path,
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'image' => $image,
            'bonsai' => $bonsai->load(['events', 'images', 'treatments', 'calendarTasks']),
        ], 201);
    }
}
