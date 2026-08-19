<?php

namespace App\Features\N8nMedia\Controllers;

use App\Exceptions\ApiException;
use App\Features\N8nMedia\Requests\StoreN8nMediaRequest;
use App\Features\N8nMedia\Services\N8nMediaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class N8nMediaController extends Controller
{
    public function index(N8nMediaService $mediaService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $mediaService->list(),
            ],
        ]);
    }

    public function store(StoreN8nMediaRequest $request, N8nMediaService $mediaService): JsonResponse
    {
        $uploadedImage = $mediaService->storeFromBase64(
            $request->validated('image'),
            $request->validated('code'),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $uploadedImage['id'],
                'url' => $uploadedImage['url'],
            ],
        ], 201);
    }

    public function show(string $filename, N8nMediaService $mediaService): JsonResponse
    {
        $image = $mediaService->findByFilename($filename);

        if ($image === null) {
            throw new ApiException('Ảnh không tồn tại.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => $image,
        ]);
    }

    public function destroy(string $filename, N8nMediaService $mediaService): JsonResponse
    {
        $deleted = $mediaService->deleteByFilename($filename);

        if (! $deleted) {
            throw new ApiException('Ảnh không tồn tại.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'filename' => $filename,
            ],
        ]);
    }
}
