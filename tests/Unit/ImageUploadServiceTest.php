<?php

use App\Features\Client\Upload\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('dịch vụ upload ảnh lưu file webp trên public disk', function () {
    if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
        $this->markTestSkipped('Môi trường test hiện tại chưa cài GD.');
    }

    Storage::fake('public');

    $service = new class extends ImageUploadService
    {
        protected function publicUploadRelativePath(string $filename): string
        {
            return 'uploads/testing/image/'.date('Y/m/d').'/'.$filename;
        }
    };

    $uploaded = $service->store(
        UploadedFile::fake()->image('banner.png', 2400, 1200),
        'Logo Trang Chủ',
    );

    expect($uploaded['extension'])->toBe('webp');
    expect($uploaded['mime_type'])->toBe('image/webp');
    expect($uploaded['path'])->toStartWith('uploads/testing/image/'.date('Y/m/d').'/');
    expect($uploaded['url'])->toContain('/storage/uploads/testing/image/'.date('Y/m/d').'/');

    Storage::disk('public')->assertExists($uploaded['path']);
});

test('dịch vụ upload ảnh vẫn lưu được file webp khi máy chủ chưa cài GD', function () {
    Storage::fake('public');

    $temporaryWebpPath = tempnam(sys_get_temp_dir(), 'webp-test-');
    file_put_contents($temporaryWebpPath, 'RIFFxxxxWEBPVP8 ');

    $uploadedFile = new UploadedFile(
        $temporaryWebpPath,
        'logo.webp',
        'image/webp',
        null,
        true,
    );

    $service = new class extends ImageUploadService
    {
        protected function supportsServerSideWebpConversion(): bool
        {
            return false;
        }

        protected function publicUploadRelativePath(string $filename): string
        {
            return 'uploads/testing/image/'.date('Y/m/d').'/'.$filename;
        }
    };

    $uploaded = $service->store($uploadedFile, 'Logo WebP');

    expect($uploaded['extension'])->toBe('webp');
    expect($uploaded['mime_type'])->toBe('image/webp');
    expect($uploaded['path'])->toStartWith('uploads/testing/image/'.date('Y/m/d').'/');
    expect($uploaded['url'])->toContain('/storage/uploads/testing/image/'.date('Y/m/d').'/');

    Storage::disk('public')->assertExists($uploaded['path']);

    @unlink($temporaryWebpPath);
});
