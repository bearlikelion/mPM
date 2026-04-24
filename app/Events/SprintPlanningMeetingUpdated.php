<?php

namespace App\Events;

use App\Models\SprintPlanningMeeting;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SprintPlanningMeetingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public SprintPlanningMeeting $meeting) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('sprint-planning.'.$this->meeting->id);
    }

    public function broadcastAs(): string
    {
        return 'planning.updated';
    }

    /**
     * @return array{meeting_id: int, status: string, current_item_id: int|null}
     */
    public function broadcastWith(): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'status' => $this->meeting->status,
            'current_item_id' => $this->meeting->current_item_id,
        ];
    }
}
