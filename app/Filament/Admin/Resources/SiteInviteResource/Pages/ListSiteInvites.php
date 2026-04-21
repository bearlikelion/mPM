<?php

namespace App\Filament\Admin\Resources\SiteInviteResource\Pages;

use App\Filament\Admin\Resources\SiteInviteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteInvites extends ListRecords
{
    protected static string $resource = SiteInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
