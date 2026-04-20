<?php

namespace App\Filament\App\Resources\SprintResource\Pages;

use App\Filament\App\Resources\SprintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSprint extends EditRecord
{
    protected static string $resource = SprintResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
