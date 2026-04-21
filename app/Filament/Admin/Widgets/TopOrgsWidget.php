<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Analytics;
use Filament\Widgets\Widget;

class TopOrgsWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.top-orgs';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'byActivity' => Analytics::topOrgsByActivity(30, 5),
            'byStorage' => collect(Analytics::topOrgsByStorage(5))
                ->map(fn ($row) => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'human' => Analytics::humanBytes($row['bytes']),
                    'bytes' => $row['bytes'],
                ])
                ->all(),
        ];
    }
}
