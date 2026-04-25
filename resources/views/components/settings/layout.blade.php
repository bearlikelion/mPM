<div class="flex items-start max-md:flex-col">
    <div class="mr-10 w-full pb-4 md:w-[220px]">
        <x-mary-menu activate-by-route>
            <x-mary-menu-item title="Profile" :link="route('settings.profile')" />
            <x-mary-menu-item title="Password" :link="route('settings.password')" />
        </x-mary-menu>
    </div>

    <hr class="border-[color:var(--gv-border)] md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        @if(!empty($heading ?? null))
            <h2 class="text-lg font-semibold text-[color:var(--gv-fg0)]">{{ $heading }}</h2>
        @endif
        @if(!empty($subheading ?? null))
            <p class="text-sm text-[color:var(--gv-fg3)]">{{ $subheading }}</p>
        @endif

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
