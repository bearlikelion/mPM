<?php

namespace App\Filament\App\Resources\EpicResource\Pages;

use App\Filament\App\Resources\EpicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEpic extends EditRecord
{
    protected static string $resource = EpicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
