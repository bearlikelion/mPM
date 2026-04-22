<?php

namespace App\Filament\App\Resources\OrganizationMemberResource\Pages;

use App\Filament\App\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrganizationMember extends EditRecord
{
    protected static string $resource = OrganizationMemberResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        $data['organization_role'] = $record->organizationRoleFor($tenant?->getKey() ?? 0) ?? 'member';
        $data['project_memberships'] = $record->projects()
            ->where('organization_id', $tenant?->getKey())
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'project_id' => $project->id,
                'role' => $project->pivot->role ?? 'member',
            ])
            ->values()
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Organization $tenant */
        $tenant = Filament::getTenant();
        $projectIds = Project::query()
            ->where('organization_id', $tenant->id)
            ->pluck('id');

        $memberships = collect($data['project_memberships'] ?? [])
            ->filter(fn (array $membership): bool => filled($membership['project_id'] ?? null) && $projectIds->contains((int) $membership['project_id']))
            ->unique('project_id')
            ->mapWithKeys(fn (array $membership): array => [
                (int) $membership['project_id'] => [
                    'role' => $membership['role'] ?? 'member',
                ],
            ]);

        $tenant->users()->updateExistingPivot($record->getKey(), [
            'role' => $data['organization_role'],
        ]);

        $record->projects()->detach($projectIds->diff($memberships->keys())->all());

        foreach ($memberships as $projectId => $pivot) {
            $record->projects()->syncWithoutDetaching([
                $projectId => $pivot,
            ]);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kickMember')
                ->label('Kick member')
                ->icon('heroicon-o-user-minus')
                ->color('danger')
                ->requiresConfirmation()
                ->hidden(fn (): bool => $this->record->is(auth()->user()))
                ->action(function (): void {
                    /** @var User $record */
                    $record = $this->record;
                    /** @var Organization $tenant */
                    $tenant = Filament::getTenant();

                    $tenantProjectIds = Project::query()
                        ->where('organization_id', $tenant->id)
                        ->pluck('id');

                    $nextDefaultOrganizationId = $record->default_organization_id === $tenant->id
                        ? $record->organizations()->whereKeyNot($tenant->id)->value('organizations.id')
                        : $record->default_organization_id;

                    $record->projects()->detach($tenantProjectIds->all());
                    $tenant->users()->detach($record->id);
                    $record->update([
                        'default_organization_id' => $nextDefaultOrganizationId,
                    ]);

                    Notification::make()
                        ->title('Member removed')
                        ->success()
                        ->send();

                    $this->redirect(OrganizationMemberResource::getUrl('index', tenant: $tenant), navigate: true);
                }),
        ];
    }
}
