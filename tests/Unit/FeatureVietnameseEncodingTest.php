<?php

test('project text files do not contain mojibake vietnamese sequences', function () {
    $projectRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
    $directories = [
        'app',
        'resources',
        'routes',
        'tests',
        'docs',
        'config',
    ];
    $allowedExtensions = ['php', 'vue', 'ts', 'js', 'md', 'json', 'txt'];
    $excludedFiles = [
        'tests'.DIRECTORY_SEPARATOR.'Unit'.DIRECTORY_SEPARATOR.'FeatureVietnameseEncodingTest.php',
    ];
    $suspiciousFragments = [
        'Ãƒ',
        'Ã„',
        'Ã†',
        'Ã‚',
        'Ã¡Â»',
        'Ã¡Âº',
        'Ã¢â‚¬',
        'á»',
        'áº',
    ];

    $filesWithMojibake = [];

    foreach ($directories as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectRoot.$directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = str_replace($projectRoot, '', $file->getPathname());

            if (in_array($relativePath, $excludedFiles, true)) {
                continue;
            }

            $extension = $file->getExtension();
            $isBladePhp = str_ends_with($file->getFilename(), '.blade.php');

            if (! in_array($extension, $allowedExtensions, true) && ! $isBladePhp) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents) || ! mb_check_encoding($contents, 'UTF-8')) {
                $filesWithMojibake[] = $relativePath.' (invalid UTF-8)';

                continue;
            }

            foreach ($suspiciousFragments as $fragment) {
                if (str_contains($contents, $fragment)) {
                    $filesWithMojibake[] = $relativePath;

                    break;
                }
            }
        }
    }

    expect($filesWithMojibake)->toBe([]);
});
