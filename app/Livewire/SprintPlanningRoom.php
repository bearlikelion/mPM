<?php

namespace App\Livewire;

use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningMeeting;
use App\Models\Task;
use App\Support\SprintPlanningService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SprintPlanningRoom extends Component
{
    public SprintPlanningMeeting $meeting;

    public ?int $storyPoints = null;

    public function mount(int $meetingId): void
    {
        $this->meeting = SprintPlanningMeeting::query()
            ->with('project.organization')
            ->findOrFail($meetingId);

        abort_unless(Auth::user()?->can('view', $this->meeting->project) ?? false, 403);

        app(SprintPlanningService::class)->join($this->meeting, Auth::user());
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-presence:sprint-planning.{$this->meeting->id},.planning.updated" => 'refreshMeeting',
        ];
    }

    public function refreshMeeting(): void
    {
        $this->meeting->refresh();
    }

    public function begin(): void
    {
        app(SprintPlanningService::class)->begin($this->meeting, Auth::user());
        $this->refreshMeeting();
    }

    public function vote(int $points): void
    {
        $item = $this->currentItem();

        if (! $item) {
            return;
        }

        app(SprintPlanningService::class)->vote($item, Auth::user(), $points);
        $this->storyPoints = $points;
        $this->refreshMeeting();
    }

    public function resolveTie(int $points): void
    {
        $item = $this->currentItem();

        if (! $item) {
            return;
        }

        app(SprintPlanningService::class)->resolveTie($item, Auth::user(), $points);
        $this->refreshMeeting();
    }

    public function claim(): void
    {
        $item = $this->currentItem();

        if (! $item) {
            return;
        }

        app(SprintPlanningService::class)->claim($item, Auth::user());
        $this->storyPoints = null;
        $this->refreshMeeting();
    }

    public function delay(): void
    {
        $item = $this->currentItem();

        if (! $item) {
            return;
        }

        app(SprintPlanningService::class)->delay($item, Auth::user());
        $this->storyPoints = null;
        $this->refreshMeeting();
    }

    public function backlog(): void
    {
        $item = $this->currentItem();

        if (! $item) {
            return;
        }

        app(SprintPlanningService::class)->markBacklog($item, Auth::user());
        $this->storyPoints = null;
        $this->refreshMeeting();
    }

    public function complete(): void
    {
        app(SprintPlanningService::class)->complete($this->meeting, Auth::user());
        $this->refreshMeeting();
    }

    public function render()
    {
        $this->meeting->load([
            'facilitator',
            'project.organization',
            'participants.user',
            'items.task.assignees',
            'items.votes.user',
            'currentItem.task',
            'currentItem.votes.user',
        ]);

        $currentItem = $this->currentItem();
        $tieOptions = $currentItem
            ? app(SprintPlanningService::class)->tieOptions($currentItem)
            : collect();

        return view('livewire.sprint-planning-room', [
            'currentItem' => $currentItem,
            'participants' => $this->meeting->participants->pluck('user')->filter(),
            'attendanceUsers' => $this->meeting->participants
                ->pluck('user')
                ->filter()
                ->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatarUrl(),
                ])
                ->values(),
            'plannedPoints' => $this->meeting->plannedStoryPoints(),
            'storyPointOptions' => Task::STORY_POINTS,
            'voteSummary' => $this->voteSummary($currentItem),
            'tieOptions' => $tieOptions,
            'isFacilitator' => $this->meeting->isFacilitatedBy(Auth::user()),
        ]);
    }

    private function currentItem(): ?SprintPlanningItem
    {
        if (! $this->meeting->current_item_id) {
            return null;
        }

        return SprintPlanningItem::query()
            ->with('meeting.project', 'task', 'votes')
            ->find($this->meeting->current_item_id);
    }

    /**
     * @return Collection<int, array{points: int, votes: int}>
     */
    private function voteSummary(?SprintPlanningItem $item): Collection
    {
        if (! $item) {
            return collect();
        }

        return $item->votes
            ->groupBy('story_points')
            ->map(fn (Collection $votes, int $points): array => [
                'points' => $points,
                'votes' => $votes->count(),
            ])
            ->sortByDesc('votes')
            ->values();
    }
}
