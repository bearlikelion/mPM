<div wire:poll.30s="refreshBell">
    <x-mary-dropdown right>
        <x-slot:trigger>
            <button type="button" class="relative rounded-full border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] p-2 text-[color:var(--gv-fg2)] transition hover:border-[color:var(--gv-amber)] hover:text-[color:var(--gv-amber)]">
                <x-mary-icon name="o-bell" class="h-5 w-5" />
                @if($unreadCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[0.65rem] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="w-80">
            <div class="flex items-center justify-between px-3 py-2">
                <div class="text-sm font-semibold">Notifications</div>
                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead" class="text-xs text-[color:var(--gv-amber)] hover:underline">Mark all read</button>
                @endif
            </div>

            <hr class="border-[color:var(--gv-border)]" />

            @forelse($notifications as $notification)
                <div class="px-2 py-1">
                    <a
                        href="{{ $notification['url'] }}"
                        wire:navigate
                        wire:click="markAsRead('{{ $notification['id'] }}')"
                        @class([
                            'block rounded-lg border px-3 py-2 transition hover:border-[color:var(--gv-amber)]',
                            'border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)]' => $notification['read'],
                            'border-amber-500/40 bg-amber-500/10' => ! $notification['read'],
                        ])
                    >
                        <div class="text-sm font-semibold text-[color:var(--gv-fg0)]">{{ $notification['title'] }}</div>
                        <div class="mt-0.5 line-clamp-2 text-xs text-[color:var(--gv-fg4)]">{{ $notification['body'] }}</div>
                        <div class="mt-1 font-mono text-[0.65rem] text-[color:var(--gv-fg4)]">{{ $notification['created'] }}</div>
                    </a>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-[color:var(--gv-fg4)]">No notifications yet</div>
            @endforelse
        </div>
    </x-mary-dropdown>
</div>
