<?php

namespace App\Filament\App\Resources\EpicResource\Pages;

use App\Filament\App\Resources\EpicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEpics extends ListRecords
{
    protected static string $resource = EpicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
