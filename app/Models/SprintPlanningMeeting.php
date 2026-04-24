<?php

namespace App\Models;

use Database\Factories\SprintPlanningMeetingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SprintPlanningMeeting extends Model
{
    /** @use HasFactory<SprintPlanningMeetingFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'project_id',
        'facilitator_id',
        'sprint_id',
        'current_item_id',
        'name',
        'status',
        'scheduled_at',
        'story_points_limit',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function currentItem(): BelongsTo
    {
        return $this->belongsTo(SprintPlanningItem::class, 'current_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SprintPlanningItem::class)->orderBy('sort_order');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SprintPlanningParticipant::class);
    }

    public function isFacilitatedBy(User $user): bool
    {
        return (int) $this->facilitator_id === (int) $user->id;
    }

    public function plannedStoryPoints(): int
    {
        return (int) $this->items()
            ->where(SprintPlanningItem::query()->getModel()->qualifyColumn('status'), SprintPlanningItem::STATUS_CLAIMED)
            ->sum('selected_story_points');
    }
}
