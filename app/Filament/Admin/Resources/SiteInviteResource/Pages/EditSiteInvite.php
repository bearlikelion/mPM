<?php

namespace App\Filament\Admin\Resources\SiteInviteResource\Pages;

use App\Filament\Admin\Resources\SiteInviteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteInvite extends EditRecord
{
    protected static string $resource = SiteInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
