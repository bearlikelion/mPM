<div class="flex h-[calc(100vh-7rem)] w-full flex-col gap-2 p-1">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0)] px-3 py-2">
        <div class="flex min-w-0 items-center gap-2">
            <span class="text-[color:var(--gv-amber)]">»</span>
            <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="font-mono text-xs text-[color:var(--gv-fg4)] transition hover:text-[color:var(--gv-amber)]">
                {{ $project->name }}
            </a>
            <span class="text-[color:var(--gv-fg4)]">·</span>
            <span class="text-sm text-[color:var(--gv-fg1)]">whiteboard</span>
        </div>
        <div class="flex items-center gap-3 text-xs text-[color:var(--gv-fg4)]">
            <span x-data="{ status: 'saved' }"
                  x-on:whiteboard-saving.window="status = 'saving'"
                  x-on:whiteboard-saved.window="status = 'saved'"
                  x-on:whiteboard-error.window="status = 'error'">
                <span x-show="status === 'saved'" class="text-[color:var(--gv-aqua)]">saved</span>
                <span x-show="status === 'saving'">saving...</span>
                <span x-show="status === 'error'" class="text-[color:var(--gv-red)]">error</span>
            </span>
            <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="app-link">back to project</a>
        </div>
    </div>

    <div
        wire:ignore
        x-data="whiteboard({ initial: @js($whiteboard->data) })"
        class="relative min-h-0 flex-1 overflow-hidden rounded-md border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0)]"
    >
        <div data-excalidraw-root class="absolute inset-0"></div>
    </div>
</div>
