<?php

namespace App\Features\Recharge\Resources;

use App\Features\Recharge\Services\ApiBankVnPartnerService;
use App\Features\Recharge\Services\RechargeConfigService;
use App\Models\ConfigRecharge;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConfigRecharge
 */
class RechargeConfigResource extends JsonResource
{
    public function __construct($resource, private readonly ?User $user = null, private readonly float|int $amount = 0)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $service = app(RechargeConfigService::class);
        $partnerService = app(ApiBankVnPartnerService::class);
        $transferPrefix = $service->normalizePrefix((string) $this->transfer_prefix);
        $isApiProvider = $service->isApiBankVnProvider($this->resource);
        $transferContent = $this->user && ! $isApiProvider
            ? $service->transferContentFor($this->user, $this->resource)
            : null;

        return [
            'id' => $this->id,
            'provider' => $this->provider ?: 'manual',
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'qr_template' => $this->qr_template,
            'qr_url' => $transferContent !== null
                ? $service->buildQrUrlForTransfer($this->resource, $this->amount, $this->user->id, $transferContent)
                : null,
            'transfer_prefix' => $transferPrefix,
            'transfer_content' => $transferContent,
            'preview_transfer_content' => $service->previewTransferContent($transferPrefix, 123),
            'preview_qr_url' => $isApiProvider ? null : $service->previewQrUrl($this->resource),
            'api_base_url' => $this->api_base_url,
            'api_key' => $this->api_key,
            'api_secret' => $this->api_secret,
            'api_bank_id' => $this->api_bank_id,
            'api_ready' => $partnerService->isConfigured($this->resource),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
