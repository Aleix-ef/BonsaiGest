<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bonsai;
use App\Models\BonsaiImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonsaiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bonsais = $request->user()
            ->bonsais()
            ->withCount(['events', 'images', 'treatments'])
            ->latest()
            ->get();

        return response()->json(['bonsais' => $bonsais]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedBonsai($request);
        $validated['user_id'] = $request->user()->id;

        if ($request->hasFile('main_image_file')) {
            $validated['main_image'] = $this->storeImage($request->file('main_image_file'));
        }

        $bonsai = Bonsai::create($validated);

        if ($bonsai->main_image) {
            BonsaiImage::create([
                'bonsai_id' => $bonsai->id,
                'image' => $bonsai->main_image,
                'date' => $bonsai->acquired_date ?? now()->toDateString(),
                'description' => 'Foto inicial de '.$bonsai->name,
            ]);
        }

        return response()->json(['bonsai' => $this->loadBonsai($bonsai)], 201);
    }

    public function show(Request $request, Bonsai $bonsai): JsonResponse
    {
        $this->authorizeBonsai($request, $bonsai);

        return response()->json(['bonsai' => $this->loadBonsai($bonsai)]);
    }

    public function update(Request $request, Bonsai $bonsai): JsonResponse
    {
        $this->authorizeBonsai($request, $bonsai);

        $validated = $this->validatedBonsai($request, false);

        if ($request->hasFile('main_image_file')) {
            $validated['main_image'] = $this->storeImage($request->file('main_image_file'));
        }

        $bonsai->update($validated);

        return response()->json(['bonsai' => $this->loadBonsai($bonsai)]);
    }

    public function destroy(Request $request, Bonsai $bonsai): JsonResponse
    {
        $this->authorizeBonsai($request, $bonsai);
        $bonsai->delete();

        return response()->json(['message' => 'Bonsái eliminado']);
    }

    private function validatedBonsai(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'species' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'origin' => ['nullable', 'string', 'max:255'],
            'style' => ['nullable', 'string', 'max:255'],
            'acquired_date' => ['nullable', 'date'],
            'water_level' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'substrate' => ['nullable', 'string', 'max:255'],
            'fertilizer' => ['nullable', 'string', 'max:255'],
            'main_image' => ['nullable', 'string', 'max:2048'],
            'main_image_file' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function loadBonsai(Bonsai $bonsai): Bonsai
    {
        return $bonsai->load([
            'events',
            'images',
            'treatments',
            'calendarTasks',
        ]);
    }

    private function authorizeBonsai(Request $request, Bonsai $bonsai): void
    {
        abort_unless($bonsai->user_id === $request->user()->id, 404);
    }

    private function storeImage($file): string
    {
        $path = $file->store('bonsais', 'public');

        return '/storage/'.$path;
    }
}
