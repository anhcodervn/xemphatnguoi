<?php

namespace App\Features\Admin\Upload\Controllers;

use App\Features\Admin\Upload\Requests\StoreImageUploadRequest;
use App\Features\Admin\Upload\Services\ImageUploadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ImageUploadController extends Controller
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
