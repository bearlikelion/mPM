<?php

namespace App\Models;

use App\Casts\RichText;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public const VISIBILITY_ORG = 'org';

    public const VISIBILITY_RESTRICTED = 'restricted';

    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'organization_id',
        'name',
        'key',
        'description',
        'avatar_path',
        'visibility',
        'task_counter',
    ];

    protected function casts(): array
    {
        return [
            'description' => RichText::class,
        ];
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Str::startsWith($this->avatar_path, ['http://', 'https://'])
                ? $this->avatar_path
                : Storage::disk('public')->url($this->avatar_path);
        }

        $initials = Str::upper(Str::substr($this->key ?: $this->name, 0, 2));

        return route('avatars.default', ['initials' => $initials ?: '?']);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function sprintPlanningMeetings(): HasMany
    {
        return $this->hasMany(SprintPlanningMeeting::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
