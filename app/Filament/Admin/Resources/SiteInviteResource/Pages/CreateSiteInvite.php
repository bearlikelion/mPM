<?php

namespace App\Filament\Admin\Resources\SiteInviteResource\Pages;

use App\Filament\Admin\Resources\SiteInviteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteInvite extends CreateRecord
{
    protected static string $resource = SiteInviteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
