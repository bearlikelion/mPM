<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizations';

    protected static ?string $title = 'Organizations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('role')
                ->options([
                    'org_admin' => 'Org admin',
                    'project_admin' => 'Project admin',
                    'member' => 'Member',
                ])
                ->default('member')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('pivot.role')->label('Role')->badge(),
                TextColumn::make('pivot.joined_at')->label('Joined')->dateTime(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->options([
                                'org_admin' => 'Org admin',
                                'project_admin' => 'Project admin',
                                'member' => 'Member',
                            ])
                            ->default('member')
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
