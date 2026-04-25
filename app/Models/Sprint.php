<?php

namespace App\Models;

use Database\Factories\SprintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sprint extends Model
{
    /** @use HasFactory<SprintFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'starts_at',
        'ends_at',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isActive(): bool
    {
        return $this->started_at !== null && $this->ended_at === null;
    }

    public function avatarUrl(): string
    {
        $initials = Str::upper(Str::substr($this->name, 0, 2));

        return route('avatars.default', ['initials' => $initials ?: 'SP']);
    }
}
