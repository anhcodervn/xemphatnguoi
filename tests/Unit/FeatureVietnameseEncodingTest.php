<?php

test('app features php files do not contain mojibake vietnamese sequences', function () {
    $featureFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Features'),
    );

    $suspiciousPatterns = [
        '/Ã./u',
        '/Ä./u',
        '/Æ./u',
        '/Â./u',
        '/á»/u',
        '/áº/u',
        '/â€|â€œ|â€|â€˜|â€™/u',
    ];

    $filesWithMojibake = [];

    foreach ($featureFiles as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (! is_string($contents)) {
            continue;
        }

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $contents) === 1) {
                $projectRoot = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
                $filesWithMojibake[] = str_replace($projectRoot, '', $file->getPathname());
                break;
            }
        }
    }

    expect($filesWithMojibake)->toBe([]);
});
