<?php

use App\Models\SprintPlanningMeeting;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('sprint-planning.{meetingId}', function (User $user, int $meetingId): array|false {
    $meeting = SprintPlanningMeeting::query()
        ->with('project')
        ->find($meetingId);

    if (! $meeting || ! $user->can('view', $meeting->project)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatarUrl(),
    ];
});
