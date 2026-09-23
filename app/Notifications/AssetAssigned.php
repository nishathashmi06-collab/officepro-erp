<?php

namespace App\Notifications;

use App\Models\Asset;

class AssetAssigned extends AppNotification
{
    public function __construct(public Asset $asset)
    {
    }

    public function type(): string
    {
        return 'asset_assigned';
    }

    public function title(): string
    {
        return 'Asset assigned to you';
    }

    public function message(): string
    {
        return sprintf('%s (%s) has been assigned to you.', $this->asset->name, $this->asset->asset_code);
    }

    public function url(): string
    {
        return route('profile.show');
    }

    public function icon(): string
    {
        return 'bi-laptop';
    }

    public function color(): string
    {
        return 'info';
    }
}
