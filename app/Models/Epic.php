<?php

namespace App\Models;

use App\Casts\RichText;
use Database\Factories\EpicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Epic extends Model
{
    /** @use HasFactory<EpicFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'avatar_path',
        'due_date',
        'completed_at',
    ];

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Str::startsWith($this->avatar_path, ['http://', 'https://'])
                ? $this->avatar_path
                : Storage::disk('public')->url($this->avatar_path);
        }

        $initials = Str::upper(Str::substr($this->name, 0, 2));

        return route('avatars.default', ['initials' => $initials ?: '?']);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'description' => RichText::class,
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
}
