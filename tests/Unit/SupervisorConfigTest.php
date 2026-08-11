<?php

test('dailyproxy uses one supervisor file for all long running processes', function () {
    $supervisorDirectory = dirname(__DIR__, 2).'/deploy/supervisor';
    $configFiles = glob($supervisorDirectory.'/*.conf');
    $config = file_get_contents($supervisorDirectory.'/dailyproxy.conf');

    expect($configFiles)
        ->toHaveCount(1)
        ->and(basename($configFiles[0]))->toBe('dailyproxy.conf')
        ->and($config)
        ->toContain('[program:dailyproxy-worker]')
        ->toContain('[program:dailyproxy-scheduler]')
        ->toContain('[program:dailyproxy-reverb]')
        ->toContain('[group:dailyproxy]')
        ->toContain('programs=dailyproxy-worker,dailyproxy-scheduler,dailyproxy-reverb');
});

test('supervisor worker timeout stays below every configured queue retry window', function () {
    $projectRoot = dirname(__DIR__, 2);
    $config = file_get_contents($projectRoot.'/deploy/supervisor/dailyproxy.conf');
    $queueConfig = require $projectRoot.'/config/queue.php';

    preg_match('/queue:work[^\r\n]*--timeout=(\d+)/', $config, $matches);

    $retryAfterValues = collect($queueConfig['connections'])
        ->pluck('retry_after')
        ->filter(fn (mixed $retryAfter): bool => is_numeric($retryAfter))
        ->map(fn (mixed $retryAfter): int => (int) $retryAfter);

    expect($matches)->toHaveKey(1)
        ->and((int) $matches[1])->toBeLessThan($retryAfterValues->min());
});
