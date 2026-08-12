<?php

use App\Features\Admin\Upload\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('image upload service stores an existing webp on the public disk', function (): void {
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
            return 'uploads/testing/image/'.$filename;
        }
    };

    try {
        $uploaded = $service->store($uploadedFile, 'Logo WebP');

        expect($uploaded['extension'])->toBe('webp')
            ->and($uploaded['mime_type'])->toBe('image/webp')
            ->and($uploaded['path'])->toStartWith('uploads/testing/image/logo-webp-')
            ->and($uploaded['url'])->toContain('/storage/uploads/testing/image/');

        Storage::disk('public')->assertExists($uploaded['path']);
    } finally {
        @unlink($temporaryWebpPath);
    }
});
