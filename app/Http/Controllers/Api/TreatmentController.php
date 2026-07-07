<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bonsai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    public function store(Request $request, Bonsai $bonsai): JsonResponse
    {
        abort_unless($bonsai->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'problem' => ['required', 'string', 'max:255'],
            'product' => ['nullable', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $treatment = $bonsai->treatments()->create($validated);

        return response()->json([
            'treatment' => $treatment,
            'bonsai' => $bonsai->load(['events', 'images', 'treatments', 'calendarTasks']),
        ], 201);
    }
}
