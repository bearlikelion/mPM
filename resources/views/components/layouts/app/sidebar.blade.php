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
            <aside
                x-data="{ open: false }"
                @open-sidebar.window="open = true"
                @close-sidebar.window="open = false"
                :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="app-sidebar fixed inset-y-0 left-0 z-40 flex w-64 flex-col gap-2 overflow-y-auto p-3 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            >
                <button
                    type="button"
                    class="self-end rounded p-1 text-[color:var(--gv-fg3)] hover:text-[color:var(--gv-fg0)] lg:hidden"
                    @click="open = false"
                    aria-label="Close sidebar"
                >
                    <x-mary-icon name="o-x-mark" class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2.5 rounded-sm border border-transparent p-1 transition hover:border-[color:var(--gv-border)]" wire:navigate>
                        @if($currentOrg)
                            <img src="{{ $currentOrg->logoUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                        @else
                            <span class="app-brand-mark shrink-0">mPM</span>
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

                    <livewire:notification-bell />
                </div>

                @if($organizations->count() > 1)
                    <x-mary-dropdown class="mt-2 w-full justify-between border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] text-[color:var(--gv-fg1)]" :label="$currentOrg?->name ?? 'Select organization'">
                        @foreach($organizations as $organization)
                            <form method="POST" action="{{ route('organizations.switch', $organization) }}" class="w-full">
                                @csrf
                                <x-mary-menu-item
                                    :title="$organization->name"
                                    :icon="$currentOrg?->is($organization) ? 'o-check' : 'o-building-office'"
                                    type="submit"
                                    no-wire-navigate
                                />
                            </form>
                        @endforeach
                    </x-mary-dropdown>
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

                <x-mary-menu activate-by-route active-bg-color="bg-[color:var(--gv-bg1)]" class="app-nav">
                    <li class="menu-title text-[color:var(--gv-fg4)]"><span>Platform</span></li>
                    <x-mary-menu-item data-desktop-tray-link icon="o-home" title="Dashboard" :route="'dashboard'" />
                    <x-mary-menu-item data-desktop-tray-link icon="o-squares-2x2" title="Projects" :route="'projects.index'" />
                    @if($isOrgAdmin)
                        <x-mary-menu-item data-desktop-tray-link icon="o-presentation-chart-line" title="Manager" :route="'manager'" />
                    @endif
                    <x-mary-menu-item data-desktop-tray-link icon="o-view-columns" title="Kanban" :route="'kanban'" />
                    <x-mary-menu-item data-desktop-tray-link icon="o-queue-list" title="Backlog" :route="'backlog'" />
                    <x-mary-menu-item data-desktop-tray-link icon="o-flag" title="Epics" :route="'epics'" />
                    <x-mary-menu-item data-desktop-tray-link icon="o-rocket-launch" title="Sprints" :route="'sprints'" />
                </x-mary-menu>

                <div class="grow"></div>

                <x-mary-menu class="app-nav">
                    <x-mary-menu-item data-desktop-tray-link icon="o-bug-ant" title="Bug Report" link="https://github.com/bearlikelion/mPM/issues/new?template=bug_report.md" external />
                    <x-mary-menu-item data-desktop-tray-link icon="o-light-bulb" title="Feature Request" link="https://github.com/bearlikelion/mPM/issues/new?template=feature_request.md" external />
                </x-mary-menu>

                @if($isSiteAdmin || $isOrgAdmin)
                    <x-mary-menu class="app-nav">
                        @if($isOrgAdmin && $currentOrg)
                            <x-mary-menu-item data-desktop-tray-link icon="o-building-office" title="Org admin" :link="url('/app/'.$currentOrg->slug)" no-wire-navigate />
                        @endif
                        @if($isSiteAdmin)
                            <x-mary-menu-item data-desktop-tray-link icon="o-shield-check" title="Site admin" link="/admin" no-wire-navigate />
                        @endif
                    </x-mary-menu>
                @endif

                <x-mary-dropdown right class="w-full">
                    <x-slot:trigger>
                        <button type="button" class="flex w-full items-center gap-2 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)] px-2 py-1.5 text-left text-sm hover:bg-[color:var(--gv-bg1)]">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-800 text-neutral-100">
                                {{ auth()->user()->initials() }}
                            </span>
                            <span class="flex-1 truncate font-medium">{{ auth()->user()->name }}</span>
                            <x-mary-icon name="o-chevron-up-down" class="h-4 w-4 text-[color:var(--gv-fg4)]" />
                        </button>
                    </x-slot:trigger>

                    <div class="px-3 py-2 text-sm">
                        <div class="font-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-[color:var(--gv-fg4)]">{{ auth()->user()->email }}</div>
                    </div>
                    <hr class="border-[color:var(--gv-border)]" />
                    <x-mary-menu-item icon="o-cog" title="Settings" link="/settings/profile" />
                    <hr class="border-[color:var(--gv-border)]" />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <x-mary-menu-item :title="__('Log Out')" icon="o-arrow-right-start-on-rectangle" type="submit" no-wire-navigate />
                    </form>
                </x-mary-dropdown>
            </aside>

            <header class="app-mobile-header sticky top-0 z-30 flex items-center gap-2 border-b border-[color:var(--gv-border)] bg-[color:var(--gv-bg0)] px-3 py-2 lg:hidden">
                <button
                    type="button"
                    class="rounded p-1 text-[color:var(--gv-fg2)] hover:text-[color:var(--gv-fg0)]"
                    x-data
                    x-on:click="$dispatch('open-sidebar')"
                    aria-label="Open sidebar"
                >
                    <x-mary-icon name="o-bars-2" class="h-5 w-5" />
                </button>

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-create-task-modal')"
                    class="rounded-full border border-neutral-700/70 bg-neutral-950/60 px-3 py-1.5 text-sm font-semibold text-neutral-50 transition hover:border-amber-400/50 hover:text-amber-200"
                >
                    + Task
                </button>

                <div class="grow"></div>

                <livewire:notification-bell />

                <x-mary-dropdown right>
                    <x-slot:trigger>
                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-800 text-sm font-semibold text-neutral-100">
                            {{ auth()->user()->initials() }}
                        </button>
                    </x-slot:trigger>
                    <x-mary-menu-item icon="o-cog" title="Settings" link="/settings/profile" />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <x-mary-menu-item :title="__('Log Out')" icon="o-arrow-right-start-on-rectangle" type="submit" no-wire-navigate />
                    </form>
                </x-mary-dropdown>
            </header>

            <main class="app-main lg:pl-0">
                {{ $slot }}
            </main>
        </div>

        <livewire:create-task-modal />
    </body>
</html>
