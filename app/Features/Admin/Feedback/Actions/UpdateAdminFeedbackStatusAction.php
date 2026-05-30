<?php

namespace App\Features\Admin\Feedback\Actions;

use App\Features\Admin\Feedback\Services\AdminFeedbackService;
use App\Models\ContactFeedback;
use App\Models\User;

class UpdateAdminFeedbackStatusAction
{
    public function __construct(private readonly AdminFeedbackService $adminFeedbackService)
    {
    }

    public function handle(ContactFeedback $feedback, User $admin, string $status): ContactFeedback
    {
        return $this->adminFeedbackService->updateStatus($feedback, $admin, $status);
    }
}
