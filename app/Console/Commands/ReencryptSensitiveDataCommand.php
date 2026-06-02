<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\RechargeMethod;
use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ReencryptSensitiveDataCommand extends Command
{
    protected $signature = 'security:reencrypt-sensitive-data
        {--chunk=200 : Number of records to process per chunk}
        {--pretend : Show what would be updated without saving changes}';

    protected $description = 'Re-encrypt sensitive bank account, webhook, and recharge method secrets using the current application encryption configuration.';

    public function handle(): int
    {
        $chunkSize = max((int) $this->option('chunk'), 1);
        $pretend = (bool) $this->option('pretend');

        $this->components->info($pretend
            ? 'Previewing sensitive data re-encryption...'
            : 'Re-encrypting sensitive data...'
        );

        $bankAccountCount = $this->reencryptBankAccounts($chunkSize, $pretend);
        $webhookCount = $this->reencryptWebhooks($chunkSize, $pretend);
        $rechargeMethodCount = $this->reencryptRechargeMethods($chunkSize, $pretend);

        $this->newLine();
        $this->components->info(sprintf(
            '%s complete. Bank accounts updated: %d. Webhooks updated: %d. Recharge methods updated: %d.',
            $pretend ? 'Preview' : 'Re-encryption',
            $bankAccountCount,
            $webhookCount,
            $rechargeMethodCount,
        ));

        return self::SUCCESS;
    }

    private function reencryptBankAccounts(int $chunkSize, bool $pretend): int
    {
        $updated = 0;
        $fields = ['username', 'password', 'token', 'proxy', 'data_login'];

        BankAccount::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $accounts) use (&$updated, $fields, $pretend): void {
                foreach ($accounts as $account) {
                    $payload = [];

                    foreach ($fields as $field) {
                        $rawValue = $account->getRawOriginal($field);

                        if ($rawValue === null || $rawValue === '') {
                            continue;
                        }

                        $payload[$field] = $account->getAttribute($field);
                    }

                    if ($payload === []) {
                        continue;
                    }

                    $updated++;

                    if ($pretend) {
                        continue;
                    }

                    $account->forceFill($payload)->saveQuietly();
                }
            });

        $this->line(sprintf('Bank accounts queued for re-encryption: %d', $updated));

        return $updated;
    }

    private function reencryptWebhooks(int $chunkSize, bool $pretend): int
    {
        $updated = 0;

        Webhook::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $webhooks) use (&$updated, $pretend): void {
                foreach ($webhooks as $webhook) {
                    $rawSecret = $webhook->getRawOriginal('secret_key');

                    if ($rawSecret === null || $rawSecret === '') {
                        continue;
                    }

                    $updated++;

                    if ($pretend) {
                        continue;
                    }

                    $webhook->forceFill([
                        'secret_key' => $webhook->secret_key,
                    ])->saveQuietly();
                }
            });

        $this->line(sprintf('Webhooks queued for re-encryption: %d', $updated));

        return $updated;
    }

    private function reencryptRechargeMethods(int $chunkSize, bool $pretend): int
    {
        $updated = 0;

        RechargeMethod::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $methods) use (&$updated, $pretend): void {
                foreach ($methods as $method) {
                    $rawSecret = $method->getRawOriginal('secret_key');

                    if ($rawSecret === null || $rawSecret === '') {
                        continue;
                    }

                    $updated++;

                    if ($pretend) {
                        continue;
                    }

                    $method->forceFill([
                        'secret_key' => $method->secret_key,
                    ])->saveQuietly();
                }
            });

        $this->line(sprintf('Recharge methods queued for re-encryption: %d', $updated));

        return $updated;
    }
}
