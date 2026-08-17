<?php

namespace App\View\Components;

use App\Models\AdSlot as AdSlotModel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class AdSlot extends Component
{
    public function __construct(
        public readonly string $name,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $adSlot = Cache::remember(
            "ad_slot:{$this->name}",
            now()->addMinutes(5),
            fn (): ?AdSlotModel => AdSlotModel::query()
                ->active()
                ->where('name', $this->name)
                ->first(),
        );

        return view('components.ad-slot', ['adSlot' => $adSlot]);
    }
}
