<?php

namespace App\Features\N8nMedia\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class N8nMediaService
{
    private const MAX_DIMENSION = 1800;

    private const WEBP_QUALITY = 82;

    private const MEDIA_DIRECTORY = 'posts';

    /**
     * @param array<string, mixed> $payload
     * @return array{id: int, filename: string, path: string, url: string}
     */
    public function storeFromBase64(string $imageData, ?string $name = null): array
    {
        if (! $this->supportsServerSideWebpConversion()) {
            throw new ApiException('Máy chủ chưa hỗ trợ tối ưu ảnh WebP.');
        }

        $binary = $this->extractBinaryFromBase64($imageData);
        $imageResource = $this->createImageResource($binary);

        [$targetWidth, $targetHeight] = $this->calculateDimensions(
            imagesx($imageResource),
            imagesy($imageResource),
        );

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($imageResource);
            throw new ApiException('Không thể tạo bộ đệm cho việc tối ưu ảnh.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled(
            $canvas,
            $imageResource,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($imageResource),
            imagesy($imageResource),
        );

        ob_start();
        imagewebp($canvas, null, self::WEBP_QUALITY);
        $binaryWebp = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($imageResource);

        if (! is_string($binaryWebp) || $binaryWebp === '') {
            throw new ApiException('Không thể chuyển ảnh sang WebP.');
        }

        $filename = $this->buildFilename($name);
        $path = $this->publicUploadRelativePath($filename);

        $stored = Storage::disk('public')->put($path, $binaryWebp, [
            'visibility' => 'public',
            'mimetype' => 'image/webp',
        ]);

        if (! $stored) {
            throw new ApiException('Không thể lưu ảnh đã tải lên.');
        }

        return [
            'id' => $this->buildId($path),
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * @return array<int, array{id: int, filename: string, path: string, url: string, size: int}>
     */
    public function list(): array
    {
        $files = Storage::disk('public')->files(self::MEDIA_DIRECTORY);

        $items = [];

        foreach ($files as $file) {
            if (! Str::endsWith(strtolower($file), '.webp')) {
                continue;
            }

            $items[] = [
                'id' => $this->buildId($file),
                'filename' => pathinfo($file, PATHINFO_BASENAME),
                'path' => $file,
                'url' => Storage::disk('public')->url($file),
                'size' => Storage::disk('public')->size($file),
            ];
        }

        return $items;
    }

    /**
     * @return array{id: int, filename: string, path: string, url: string, size: int}|null
     */
    public function findByFilename(string $filename): ?array
    {
        if (! preg_match('/^[A-Za-z0-9._-]+\.webp$/', $filename)) {
            return null;
        }

        $path = $this->publicUploadRelativePath($filename);

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $this->toArray($path);
    }

    public function deleteByFilename(string $filename): bool
    {
        $image = $this->findByFilename($filename);
        if ($image === null) {
            return false;
        }

        return Storage::disk('public')->delete($image['path']);
    }

    private function supportsServerSideWebpConversion(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagewebp');
    }

    private function extractBinaryFromBase64(string $imageData): string
    {
        $raw = trim($imageData);

        if (preg_match('/^data:(?<mime>[a-z0-9.+-]+\/[a-z0-9.+-]+);base64,(?<data>[A-Za-z0-9+\/=\s]+)$/i', $raw, $matches) === 1) {
            $raw = $matches['data'];
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $raw), true);

        if ($binary === false || $binary === '') {
            throw new ApiException('Chuỗi base64 ảnh không hợp lệ.');
        }

        $imageInfo = getimagesizefromstring($binary);

        if (! is_array($imageInfo) || ! str_starts_with((string) $imageInfo['mime'], 'image/')) {
            throw new ApiException('Dữ liệu ảnh không hợp lệ.');
        }

        return $binary;
    }

    private function createImageResource(string $binary): mixed
    {
        $resource = imagecreatefromstring($binary);

        if ($resource === false) {
            throw new ApiException('Không thể đọc ảnh đã tải lên.');
        }

        return $resource;
    }

    private function buildFilename(?string $name): string
    {
        $base = $this->normalizeName($name);
        $filename = $base;
        $counter = 1;

        while (Storage::disk('public')->exists(self::MEDIA_DIRECTORY.'/'.$filename)) {
            $filename = $this->normalizeName($base.'-'.$counter);
            $counter++;
        }

        return $filename;
    }

    private function normalizeName(?string $name = null): string
    {
        $base = Str::slug(trim((string) $name ?: now()->format('YmdHis').'-media'));
        $base = trim((string) $base, '-_.');

        if ($base === '') {
            $base = 'image';
        }

        return $base.'.webp';
    }

    private function calculateDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_DIMENSION && $height <= self::MAX_DIMENSION) {
            return [$width, $height];
        }

        $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    private function buildId(string $path): int
    {
        return (int) sprintf('%u', crc32($path));
    }

    private function publicUploadRelativePath(string $filename): string
    {
        return self::MEDIA_DIRECTORY.'/'.$filename;
    }

    /**
     * @return array{id: int, filename: string, path: string, url: string, size: int}
     */
    private function toArray(string $path): array
    {
        return [
            'id' => $this->buildId($path),
            'filename' => pathinfo($path, PATHINFO_BASENAME),
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'size' => Storage::disk('public')->size($path),
        ];
    }
}
