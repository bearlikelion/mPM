<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public const DEFAULT_SPRINT_LENGTH_DAYS = 14;

    public const DEFAULT_STORY_POINTS_PER_SPRINT = 20;

    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'logo_path',
        'settings',
        'registration_enabled',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'registration_enabled' => 'boolean',
        ];
    }

    public function preferredTimezone(): string
    {
        return $this->timezone ?: 'UTC';
    }

    public function sprintLengthDays(): int
    {
        return max(1, (int) ($this->settings['sprint_length_days'] ?? self::DEFAULT_SPRINT_LENGTH_DAYS));
    }

    public function storyPointsPerSprint(): int
    {
        return max(1, (int) ($this->settings['story_points_per_sprint'] ?? self::DEFAULT_STORY_POINTS_PER_SPRINT));
    }

    /**
     * @return array{sprint_length_days: int, story_points_per_sprint: int}
     */
    public function sprintSettings(): array
    {
        return [
            'sprint_length_days' => $this->sprintLengthDays(),
            'story_points_per_sprint' => $this->storyPointsPerSprint(),
        ];
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(OrganizationInvite::class);
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class);
    }

    public function logoUrl(): string
    {
        if ($this->logo_path) {
            return Str::startsWith($this->logo_path, ['http://', 'https://'])
                ? $this->logo_path
                : Storage::disk('public')->url($this->logo_path);
        }

        $initials = Str::upper(Str::substr($this->name, 0, 2));

        return route('avatars.default', ['initials' => $initials ?: '?']);
    }
}
