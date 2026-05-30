<?php

namespace App\Features\Admin\Webhook\Actions;

use App\Models\Webhook;

class ToggleWebhookAction
{
    public function handle(Webhook $webhook): Webhook
    {
        $webhook->forceFill([
            'status' => $webhook->status === 'active' ? 'inactive' : 'active',
        ])->save();

        return $webhook->refresh();
    }
}
