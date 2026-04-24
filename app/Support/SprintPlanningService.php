<?php

namespace App\Support;

use App\Events\SprintPlanningMeetingUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningMeeting;
use App\Models\SprintPlanningParticipant;
use App\Models\SprintPlanningVote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SprintPlanningService
{
    public function schedule(Project $project, User $facilitator, string $name, string $scheduledAt, int $storyPointsLimit): SprintPlanningMeeting
    {
        return SprintPlanningMeeting::query()->create([
            'project_id' => $project->id,
            'facilitator_id' => $facilitator->id,
            'name' => $name,
            'status' => SprintPlanningMeeting::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'story_points_limit' => $storyPointsLimit,
        ]);
    }

    public function join(SprintPlanningMeeting $meeting, User $user): void
    {
        SprintPlanningParticipant::query()->updateOrCreate(
            [
                'sprint_planning_meeting_id' => $meeting->id,
                'user_id' => $user->id,
            ],
            [
                'joined_at' => now(),
                'last_seen_at' => now(),
            ],
        );
    }

    public function begin(SprintPlanningMeeting $meeting, User $user): SprintPlanningMeeting
    {
        $this->ensureFacilitator($meeting, $user);

        $updatedMeeting = DB::transaction(function () use ($meeting, $user): SprintPlanningMeeting {
            $meeting = SprintPlanningMeeting::query()->lockForUpdate()->findOrFail($meeting->id);

            if ($meeting->status !== SprintPlanningMeeting::STATUS_SCHEDULED) {
                return $meeting;
            }

            $this->join($meeting, $user);

            Task::query()
                ->where('project_id', $meeting->project_id)
                ->whereNull('sprint_id')
                ->where('status', '!=', 'done')
                ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)")
                ->orderBy('created_at')
                ->get()
                ->values()
                ->each(function (Task $task, int $index) use ($meeting): void {
                    SprintPlanningItem::query()->firstOrCreate(
                        [
                            'sprint_planning_meeting_id' => $meeting->id,
                            'task_id' => $task->id,
                        ],
                        [
                            'status' => SprintPlanningItem::STATUS_PENDING,
                            'sort_order' => $index + 1,
                        ],
                    );
                });

            $meeting->update([
                'status' => SprintPlanningMeeting::STATUS_ACTIVE,
                'started_at' => now(),
            ]);

            $this->advance($meeting);

            return $meeting->refresh();
        });

        broadcast(new SprintPlanningMeetingUpdated($updatedMeeting));

        return $updatedMeeting;
    }

    public function vote(SprintPlanningItem $item, User $user, int $storyPoints): SprintPlanningItem
    {
        if (! in_array($storyPoints, Task::STORY_POINTS, true)) {
            throw ValidationException::withMessages(['storyPoints' => 'Choose a valid story point value.']);
        }

        if (! $item->isOpenForVoting()) {
            throw ValidationException::withMessages(['storyPoints' => 'This card is not open for voting.']);
        }

        $updatedItem = DB::transaction(function () use ($item, $user, $storyPoints): SprintPlanningItem {
            $item = SprintPlanningItem::query()->lockForUpdate()->findOrFail($item->id);
            $this->join($item->meeting, $user);

            SprintPlanningVote::query()->updateOrCreate(
                [
                    'sprint_planning_item_id' => $item->id,
                    'user_id' => $user->id,
                ],
                ['story_points' => $storyPoints],
            );

            $this->resolveIfVotingIsComplete($item);

            return $item->refresh();
        });

        broadcast(new SprintPlanningMeetingUpdated($updatedItem->meeting));

        return $updatedItem;
    }

    public function resolveTie(SprintPlanningItem $item, User $user, int $storyPoints): SprintPlanningItem
    {
        $this->ensureFacilitator($item->meeting, $user);

        if (! $this->tieOptions($item)->contains($storyPoints)) {
            throw ValidationException::withMessages(['storyPoints' => 'Choose one of the tied story point values.']);
        }

        $item->update([
            'status' => SprintPlanningItem::STATUS_ESTIMATED,
            'selected_story_points' => $storyPoints,
            'decision_by' => $user->id,
            'decided_at' => now(),
        ]);

        broadcast(new SprintPlanningMeetingUpdated($item->meeting));

        return $item->refresh();
    }

    public function claim(SprintPlanningItem $item, User $user): SprintPlanningItem
    {
        if ($item->status !== SprintPlanningItem::STATUS_ESTIMATED || ! $item->selected_story_points) {
            throw ValidationException::withMessages(['item' => 'Estimate this card before assigning it.']);
        }

        $meeting = $item->meeting;
        $nextTotal = $meeting->plannedStoryPoints() + (int) $item->selected_story_points;

        if ($nextTotal > $meeting->story_points_limit) {
            throw ValidationException::withMessages(['item' => 'This card would exceed the sprint story point limit.']);
        }

        $item->update([
            'status' => SprintPlanningItem::STATUS_CLAIMED,
            'assigned_user_id' => $user->id,
        ]);

        $this->advance($meeting);
        broadcast(new SprintPlanningMeetingUpdated($meeting->refresh()));

        return $item->refresh();
    }

    public function markBacklog(SprintPlanningItem $item, User $user): SprintPlanningItem
    {
        $this->ensureFacilitator($item->meeting, $user);

        return $this->markTerminal($item, SprintPlanningItem::STATUS_BACKLOG);
    }

    public function delay(SprintPlanningItem $item, User $user): SprintPlanningItem
    {
        $this->ensureFacilitator($item->meeting, $user);

        return $this->markTerminal($item, SprintPlanningItem::STATUS_DELAYED);
    }

    public function complete(SprintPlanningMeeting $meeting, User $user): SprintPlanningMeeting
    {
        $this->ensureFacilitator($meeting, $user);

        $updatedMeeting = DB::transaction(function () use ($meeting): SprintPlanningMeeting {
            $meeting = SprintPlanningMeeting::query()->with('project.organization')->lockForUpdate()->findOrFail($meeting->id);

            if ($meeting->status === SprintPlanningMeeting::STATUS_COMPLETED) {
                return $meeting;
            }

            /** @var Organization $organization */
            $organization = $meeting->project->organization;
            $startsAt = now($organization->preferredTimezone())->toDateString();
            $endsAt = now($organization->preferredTimezone())
                ->addDays($organization->sprintLengthDays() - 1)
                ->toDateString();

            $sprint = Sprint::query()->create([
                'project_id' => $meeting->project_id,
                'name' => $meeting->name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'started_at' => now(),
            ]);

            $meeting->items()
                ->where('status', SprintPlanningItem::STATUS_CLAIMED)
                ->with('task')
                ->get()
                ->each(function (SprintPlanningItem $item) use ($sprint, $meeting): void {
                    $item->task->update([
                        'sprint_id' => $sprint->id,
                        'story_points' => $item->selected_story_points,
                    ]);

                    if ($item->assigned_user_id) {
                        $item->task->assignees()->syncWithoutDetaching([$item->assigned_user_id]);

                        /** @var User|null $assignee */
                        $assignee = User::query()->find($item->assigned_user_id);

                        if ($assignee) {
                            app(TaskActivityNotifier::class)->taskAssigned($item->task, $assignee, $meeting->facilitator);
                        }
                    }
                });

            $meeting->update([
                'sprint_id' => $sprint->id,
                'status' => SprintPlanningMeeting::STATUS_COMPLETED,
                'current_item_id' => null,
                'completed_at' => now(),
            ]);

            return $meeting->refresh();
        });

        broadcast(new SprintPlanningMeetingUpdated($updatedMeeting));

        return $updatedMeeting;
    }

    /**
     * @return Collection<int, int>
     */
    public function tieOptions(SprintPlanningItem $item): Collection
    {
        $counts = $item->votes()
            ->selectRaw('story_points, count(*) as vote_count')
            ->groupBy('story_points')
            ->orderByDesc('vote_count')
            ->get();

        $topCount = (int) ($counts->first()->vote_count ?? 0);

        return $counts
            ->filter(fn (SprintPlanningVote $vote): bool => (int) $vote->vote_count === $topCount)
            ->pluck('story_points')
            ->map(fn (int $points): int => $points)
            ->values();
    }

    private function resolveIfVotingIsComplete(SprintPlanningItem $item): void
    {
        $participantCount = max(1, $item->meeting->participants()->count());
        $voteCount = $item->votes()->count();

        if ($voteCount < $participantCount) {
            return;
        }

        $tieOptions = $this->tieOptions($item);

        if ($tieOptions->count() === 1) {
            $item->update([
                'status' => SprintPlanningItem::STATUS_ESTIMATED,
                'selected_story_points' => $tieOptions->first(),
                'decided_at' => now(),
            ]);
        }
    }

    private function advance(SprintPlanningMeeting $meeting): void
    {
        $meeting = $meeting->refresh();

        if ($meeting->status !== SprintPlanningMeeting::STATUS_ACTIVE) {
            return;
        }

        if ($meeting->plannedStoryPoints() >= $meeting->story_points_limit) {
            $meeting->update(['current_item_id' => null]);

            return;
        }

        $nextItem = $meeting->items()
            ->where('status', SprintPlanningItem::STATUS_PENDING)
            ->orderBy('sort_order')
            ->first();

        if (! $nextItem) {
            $meeting->update(['current_item_id' => null]);

            return;
        }

        $nextItem->update(['status' => SprintPlanningItem::STATUS_VOTING]);
        $meeting->update(['current_item_id' => $nextItem->id]);
    }

    private function markTerminal(SprintPlanningItem $item, string $status): SprintPlanningItem
    {
        $item->update([
            'status' => $status,
            'selected_story_points' => null,
            'assigned_user_id' => null,
            'decided_at' => now(),
        ]);

        $this->advance($item->meeting);
        broadcast(new SprintPlanningMeetingUpdated($item->meeting->refresh()));

        return $item->refresh();
    }

    private function ensureFacilitator(SprintPlanningMeeting $meeting, User $user): void
    {
        if (! $meeting->isFacilitatedBy($user) && ! $user->can('update', $meeting->project)) {
            throw ValidationException::withMessages(['meeting' => 'Only the meeting facilitator can do that.']);
        }
    }
}
