<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'timezone',
        'password',
        'avatar_path',
        'default_organization_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preferredTimezone(): string
    {
        return $this->timezone ?: 'UTC';
    }

    public function convertToLocalTime(CarbonInterface|string $timestamp): Carbon
    {
        $date = $timestamp instanceof CarbonInterface
            ? Carbon::instance($timestamp)
            : Carbon::parse($timestamp);

        return $date->setTimezone($this->preferredTimezone());
    }

    public function formatLocalTime(CarbonInterface|string $timestamp, string $format = 'M j, Y g:i A T'): string
    {
        return $this->convertToLocalTime($timestamp)->format($format);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function defaultOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'default_organization_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function facilitatedSprintPlanningMeetings(): HasMany
    {
        return $this->hasMany(SprintPlanningMeeting::class, 'facilitator_id');
    }

    public function initials(): string
    {
        return Str::upper(Str::substr(
            Str::of($this->name)
                ->explode(' ')
                ->map(fn (string $name) => Str::substr($name, 0, 1))
                ->implode(''),
            0,
            2
        ));
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return route('avatars.default', ['initials' => $this->initials() ?: '?']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('site_admin'),
            'app' => $this->organizations()->exists(),
            default => false,
        };
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->organizations()->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->organizations()->whereKey($tenant->getKey())->exists();
    }

    public function organizationRoleFor(int $organizationId): ?string
    {
        return $this->organizations()
            ->whereKey($organizationId)
            ->value('organization_user.role');
    }
}
