<?php

namespace App\Filament\App\Resources\OrganizationMemberResource\Pages;

use App\Filament\App\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ListOrganizationMembers extends ListRecords
{
    protected static string $resource = OrganizationMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviteMember')
                ->label('Invite member')
                ->icon('heroicon-o-envelope')
                ->schema([
                    TextInput::make('email')
                        ->email()
                        ->required(),
                    Select::make('role')
                        ->options(OrganizationMemberResource::organizationRoleOptions())
                        ->default('member')
                        ->required(),
                    DateTimePicker::make('expires_at')
                        ->default(now()->addDays(7))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Organization|null $tenant */
                    $tenant = Filament::getTenant();

                    if (! $tenant instanceof Organization) {
                        throw ValidationException::withMessages([
                            'email' => 'No organization is selected.',
                        ]);
                    }

                    if ($tenant->users()->count() >= SiteSetting::current()->user_limit_per_org) {
                        throw ValidationException::withMessages([
                            'email' => 'This organization is already at its user limit.',
                        ]);
                    }

                    $email = Str::lower($data['email']);

                    $invite = OrganizationInvite::query()
                        ->where('organization_id', $tenant->id)
                        ->where('email', $email)
                        ->whereNull('accepted_at')
                        ->first();

                    if ($invite) {
                        $invite->update([
                            'role' => $data['role'],
                            'expires_at' => $data['expires_at'],
                            'invited_by' => auth()->id(),
                            'token' => Str::random(48),
                        ]);
                    } else {
                        $invite = OrganizationInvite::create([
                            'organization_id' => $tenant->id,
                            'email' => $email,
                            'role' => $data['role'],
                            'expires_at' => $data['expires_at'],
                            'invited_by' => auth()->id(),
                        ]);
                    }

                    Notification::make()
                        ->title('Invite ready')
                        ->body(route('invite.show', ['token' => $invite->token]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
