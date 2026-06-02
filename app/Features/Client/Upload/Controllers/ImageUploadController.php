<?php

namespace App\Features\Client\Upload\Controllers;

use App\Features\Client\Upload\Requests\StoreImageUploadRequest;
use App\Features\Client\Upload\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

class ImageUploadController
{
    public function store(StoreImageUploadRequest $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $uploadedImage = $imageUploadService->store(
            $request->file('image'),
            $request->validated('name'),
        );

        return response()->json([
            'status' => true,
            'message' => 'Tải ảnh lên thành công.',
            'data' => $uploadedImage,
        ]);
    }
}
