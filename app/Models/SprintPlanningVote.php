<?php

namespace App\Models;

use Database\Factories\SprintPlanningVoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintPlanningVote extends Model
{
    /** @use HasFactory<SprintPlanningVoteFactory> */
    use HasFactory;

    protected $fillable = [
        'sprint_planning_item_id',
        'user_id',
        'story_points',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SprintPlanningItem::class, 'sprint_planning_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
