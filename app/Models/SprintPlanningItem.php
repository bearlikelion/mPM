<?php

namespace App\Models;

use Database\Factories\SprintPlanningItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SprintPlanningItem extends Model
{
    /** @use HasFactory<SprintPlanningItemFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VOTING = 'voting';

    public const STATUS_ESTIMATED = 'estimated';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_DELAYED = 'delayed';

    public const STATUS_BACKLOG = 'backlog';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VOTING,
        self::STATUS_ESTIMATED,
        self::STATUS_CLAIMED,
        self::STATUS_DELAYED,
        self::STATUS_BACKLOG,
    ];

    protected $fillable = [
        'sprint_planning_meeting_id',
        'task_id',
        'assigned_user_id',
        'decision_by',
        'status',
        'sort_order',
        'selected_story_points',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SprintPlanningMeeting::class, 'sprint_planning_meeting_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SprintPlanningVote::class);
    }

    public function isOpenForVoting(): bool
    {
        return $this->status === self::STATUS_VOTING;
    }
}
