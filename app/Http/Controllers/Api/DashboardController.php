<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonsaiEvent;
use App\Models\BonsaiImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $bonsaiIds = $user->bonsais()->pluck('id');

        return response()->json([
            'total_bonsais' => $bonsaiIds->count(),
            'bonsais' => $user->bonsais()
                ->withCount(['events', 'images', 'treatments'])
                ->latest()
                ->take(8)
                ->get(),
            'latest_events' => BonsaiEvent::query()
                ->whereIn('bonsai_id', $bonsaiIds)
                ->with('bonsai:id,name,species,main_image')
                ->latest('date')
                ->take(5)
                ->get(),
            'upcoming_tasks' => $user->calendarTasks()
                ->with('bonsai:id,name,species,main_image')
                ->whereDate('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->take(5)
                ->get(),
            'latest_images' => BonsaiImage::query()
                ->whereIn('bonsai_id', $bonsaiIds)
                ->with('bonsai:id,name,species,main_image')
                ->latest('date')
                ->take(6)
                ->get(),
        ]);
    }
}
