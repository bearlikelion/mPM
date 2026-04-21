<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body>
        <div class="app-shell">
            <flux:sidebar sticky stashable class="app-sidebar">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                @php
                    $currentOrg = auth()->user()->defaultOrganization ?? auth()->user()->organizations()->first();
                    $isSiteAdmin = auth()->user()->hasRole('site_admin');
                    $isOrgAdmin = $currentOrg && auth()->user()->can('update', $currentOrg);
                    $projectCount = $currentOrg?->projects()->count() ?? 0;
                @endphp

                <a href="{{ route('dashboard') }}" class="mr-5 flex items-center gap-3 rounded-2xl border border-transparent p-1 transition hover:border-neutral-700/70" wire:navigate>
                    @if($currentOrg)
                        <img src="{{ $currentOrg->logoUrl() }}" alt="" class="size-11 rounded-2xl border border-neutral-700/60 bg-neutral-900 object-cover shadow-lg shadow-black/20" />
                    @else
                        <span class="app-brand-mark">mPM</span>
                    @endif
                    <div class="min-w-0">
                        <div class="app-eyebrow">Workspace</div>
                        <div class="truncate text-xl font-semibold tracking-tight text-neutral-50">
                            {{ $currentOrg?->name ?? config('app.name', 'mPM') }}
                        </div>
                        <div class="mt-1 text-sm text-neutral-400">
                            {{ $projectCount }} {{ \Illuminate\Support\Str::plural('project', $projectCount) }} in motion
                        </div>
                    </div>
                </a>

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-create-task-modal')"
                    class="app-task-trigger mt-4 flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left transition hover:border-amber-400/40 hover:bg-neutral-950/75"
                >
                    <div>
                        <div class="app-eyebrow">Create</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">New task</div>
                    </div>
                    <span class="text-2xl font-semibold text-amber-300">+</span>
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
