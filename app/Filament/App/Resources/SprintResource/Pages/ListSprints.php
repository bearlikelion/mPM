<?php

namespace App\Filament\App\Resources\SprintResource\Pages;

use App\Filament\App\Resources\SprintResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSprints extends ListRecords
{
    protected static string $resource = SprintResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
