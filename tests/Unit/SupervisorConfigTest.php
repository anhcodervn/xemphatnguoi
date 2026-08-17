<?php

test('xemphatnguoi uses one supervisor file for all long running processes', function () {
    $supervisorDirectory = dirname(__DIR__, 2).'/deploy/supervisor';
    $configFiles = glob($supervisorDirectory.'/*.conf');
    $config = file_get_contents($supervisorDirectory.'/xemphatnguoi.conf');

    expect($configFiles)
        ->toHaveCount(1)
        ->and(basename($configFiles[0]))->toBe('xemphatnguoi.conf')
        ->and($config)
        ->toContain('[program:xemphatnguoi-worker]')
        ->toContain('[program:xemphatnguoi-scheduler]')
        ->toContain('[program:xemphatnguoi-reverb]')
        ->toContain('[group:xemphatnguoi]')
        ->toContain('programs=xemphatnguoi-worker,xemphatnguoi-scheduler,xemphatnguoi-reverb')
        ->toContain('/usr/bin/php8.2')
        ->toContain('queue:work --queue=default,mails,user-logs')
        ->toContain('schedule:work --no-interaction')
        ->toContain('reverb:start --host=127.0.0.1 --port=8080 --no-interaction')
        ->toContain('stdout_logfile=/home/xemphatnguoivn/xemphatnguoi.vn/storage/logs/queue-worker-%(process_num)02d.log')
        ->not->toContain('dailyproxy');
});

test('supervisor worker timeout stays below every configured queue retry window', function () {
    $projectRoot = dirname(__DIR__, 2);
    $config = file_get_contents($projectRoot.'/deploy/supervisor/xemphatnguoi.conf');
    $queueConfig = require $projectRoot.'/config/queue.php';

    preg_match('/queue:work[^\r\n]*--timeout=(\d+)/', $config, $matches);

    $retryAfterValues = collect($queueConfig['connections'])
        ->pluck('retry_after')
        ->filter(fn (mixed $retryAfter): bool => is_numeric($retryAfter))
        ->map(fn (mixed $retryAfter): int => (int) $retryAfter);

    expect($matches)->toHaveKey(1)
        ->and((int) $matches[1])->toBeLessThan($retryAfterValues->min());
});

test('supervisor allows workers to stop gracefully and keeps rotating logs isolated', function () {
    $config = file_get_contents(dirname(__DIR__, 2).'/deploy/supervisor/xemphatnguoi.conf');

    preg_match('/\[program:xemphatnguoi-worker\][\s\S]*?--timeout=(\d+)[\s\S]*?stopwaitsecs=(\d+)[\s\S]*?numprocs=(\d+)[\s\S]*?stdout_logfile=([^\r\n]+)/', $config, $matches);

    expect($matches)->toHaveKeys([1, 2, 3, 4])
        ->and((int) $matches[2])->toBeGreaterThan((int) $matches[1])
        ->and((int) $matches[3])->toBe(2)
        ->and($matches[4])->toContain('%(process_num)02d');
});
