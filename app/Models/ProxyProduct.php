<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProxyProduct extends Model
{
    use HasFactory;

    public const SUPPORTED_PROTOCOLS = ['http', 'https', 'socks4', 'socks5'];

    protected $fillable = [
        'proxy_category_id',
        'code',
        'name',
        'country_code',
        'protocol',
        'supported_protocols',
        'description',
        'provider_product_code',
        'default_provider_id',
        'sort_order',
        'base_price',
        'selling_price',
        'max_quantity',
        'is_active',
        'settings',
    ];

    protected $attributes = [
        'protocol' => 'http',
        'sort_order' => 0,
        'max_quantity' => 100,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'supported_protocols' => 'array',
            'sort_order' => 'integer',
            'max_quantity' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProxyProvider::class, 'default_provider_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProxyCategory::class, 'proxy_category_id');
    }

    public function supportedProtocols(): array
    {
        $protocols = is_array($this->supported_protocols) ? $this->supported_protocols : [$this->protocol];

        $supported = collect($protocols)
            ->map(fn (mixed $protocol): string => strtolower((string) $protocol))
            ->filter(fn (string $protocol): bool => in_array($protocol, self::SUPPORTED_PROTOCOLS, true))
            ->unique()
            ->values()
            ->all();

        return $supported !== [] ? $supported : ['http'];
    }
}
