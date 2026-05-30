<?php

namespace App\Features\Client\Contact\Actions;

use App\Features\Client\Contact\Services\ContactService;
use App\Models\ContactFeedback;
use App\Models\User;

class StoreContactFeedbackAction
{
    public function __construct(private readonly ContactService $contactService)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(?User $user, array $payload): ContactFeedback
    {
        return $this->contactService->createFeedback($user, $payload);
    }
}
