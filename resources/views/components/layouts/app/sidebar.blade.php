@props([
    'currentOrg' => null,
    'organizations' => collect(),
    'isSiteAdmin' => false,
    'isOrgAdmin' => false,
    'projectCount' => 0,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body>
        <div class="app-shell">
            <flux:sidebar sticky stashable class="app-sidebar">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-sm border border-transparent p-1 transition hover:border-[color:var(--gv-border)]" wire:navigate>
                    @if($currentOrg)
                        <img src="{{ $currentOrg->logoUrl() }}" alt="" class="h-9 w-9 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                    @else
                        <span class="app-brand-mark">mPM</span>
                    @endif
                    <div class="min-w-0">
                        <div class="truncate font-mono text-sm font-semibold text-[color:var(--gv-fg0)]">
                            {{ $currentOrg?->name ?? config('app.name', 'mPM') }}
                        </div>
                        <div class="text-xs text-[color:var(--gv-fg4)]">
                            {{ $projectCount }} {{ \Illuminate\Support\Str::plural('project', $projectCount) }}
                        </div>
                    </div>
                </a>

                @if($organizations->count() > 1)
                    <flux:dropdown position="bottom" align="start">
                        <flux:button variant="subtle" icon:trailing="chevron-down" class="mt-2 w-full justify-between border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] text-[color:var(--gv-fg1)]">
                            <span class="truncate">{{ $currentOrg?->name ?? 'Select organization' }}</span>
                        </flux:button>

                        <flux:menu class="w-72">
                            @foreach($organizations as $organization)
                                <form method="POST" action="{{ route('organizations.switch', $organization) }}" class="w-full">
                                    @csrf

                                    <flux:menu.item
                                        as="button"
                                        type="submit"
                                        class="w-full justify-between"
                                        :icon="$currentOrg?->is($organization) ? 'check' : 'building-office'"
                                    >
                                        <span class="truncate">{{ $organization->name }}</span>
                                    </flux:menu.item>
                                </form>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                @endif

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-create-task-modal')"
                    class="app-task-trigger mt-3 flex w-full items-center justify-between rounded-sm px-3 py-2 text-left"
                >
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-fg1)]">new task</span>
                    <span class="font-mono text-base font-semibold text-[color:var(--gv-amber)]">+</span>
                </button>

                <flux:navlist variant="outline">
                    <flux:navlist.group heading="Platform" class="grid">
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Dashboard</flux:navlist.item>
                        <flux:navlist.item icon="squares-2x2" :href="route('projects.index')" :current="request()->routeIs('projects.index')" wire:navigate>Projects</flux:navlist.item>
                        @if($isOrgAdmin)
                            <flux:navlist.item icon="presentation-chart-line" :href="route('manager')" :current="request()->routeIs('manager')" wire:navigate>Manager</flux:navlist.item>
                        @endif
                        <flux:navlist.item icon="view-columns" :href="route('kanban')" :current="request()->routeIs('kanban')" wire:navigate>Kanban</flux:navlist.item>
                        <flux:navlist.item icon="queue-list" :href="route('backlog')" :current="request()->routeIs('backlog')" wire:navigate>Backlog</flux:navlist.item>
                        <flux:navlist.item icon="flag" :href="route('epics')" :current="request()->routeIs('epics')" wire:navigate>Epics</flux:navlist.item>
                        <flux:navlist.item icon="rocket-launch" :href="route('sprints')" :current="request()->routeIs('sprints')" wire:navigate>Sprints</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:spacer />

                <div class="flex items-center justify-end">
                    <livewire:notification-bell />
                </div>

                <flux:navlist variant="outline">
                    <flux:navlist.item icon="bug-ant" href="https://github.com/bearlikelion/mPM/issues/new?template=bug_report.md" target="_blank">Bug Report</flux:navlist.item>
                    <flux:navlist.item icon="light-bulb" href="https://github.com/bearlikelion/mPM/issues/new?template=feature_request.md" target="_blank">Feature Request</flux:navlist.item>
                </flux:navlist>

                @if($isSiteAdmin || $isOrgAdmin)
                    <flux:navlist variant="outline">
                        @if($isOrgAdmin && $currentOrg)
                            <flux:navlist.item icon="building-office" :href="url('/app/'.$currentOrg->slug)">
                                Org admin
                            </flux:navlist.item>
                        @endif

                        @if($isSiteAdmin)
                            <flux:navlist.item icon="shield-check" href="/admin">
                                Site admin
                            </flux:navlist.item>
                        @endif
                    </flux:navlist>
                @endif

                <!-- Desktop User Menu -->
                <flux:dropdown position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevrons-up-down"
                    />

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-800 text-neutral-100"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-left text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:sidebar>

            <!-- Mobile User Menu -->
            <flux:header class="app-mobile-header lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-create-task-modal')"
                    class="rounded-full border border-neutral-700/70 bg-neutral-950/60 px-3 py-1.5 text-sm font-semibold text-neutral-50 transition hover:border-amber-400/50 hover:text-amber-200"
                >
                    + Task
                </button>

                <flux:spacer />

                <livewire:notification-bell />

                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-800 text-neutral-100"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-left text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>

            {{ $slot }}
        </div>

        <livewire:create-task-modal />

        @fluxScripts
    </body>
</html>
