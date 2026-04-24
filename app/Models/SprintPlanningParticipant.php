<?php

namespace App\Models;

use Database\Factories\SprintPlanningParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintPlanningParticipant extends Model
{
    /** @use HasFactory<SprintPlanningParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'sprint_planning_meeting_id',
        'user_id',
        'joined_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SprintPlanningMeeting::class, 'sprint_planning_meeting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
