<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()
            ->calendarTasks()
            ->with('bonsai:id,name,species,main_image')
            ->orderBy('date')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bonsai_id' => ['nullable', 'exists:bonsais,id'],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if (! empty($validated['bonsai_id'])) {
            $ownsBonsai = $request->user()->bonsais()->whereKey($validated['bonsai_id'])->exists();
            abort_unless($ownsBonsai, 404);
        }

        $task = CalendarTask::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['task' => $task->load('bonsai:id,name,species,main_image')], 201);
    }

    public function destroy(Request $request, CalendarTask $calendarTask): JsonResponse
    {
        abort_unless($calendarTask->user_id === $request->user()->id, 404);
        $calendarTask->delete();

        return response()->json(['message' => 'Tarea eliminada']);
    }
}
