@props([
    'title',
    'subtitle' => null,
])

<header {{ $attributes->class(['page-header flex flex-wrap items-end justify-between gap-3 border-b border-[color:var(--gv-border)] pb-3']) }}>
    <div class="min-w-0">
        <h1 class="text-xl font-semibold tracking-tight text-[color:var(--gv-fg0)]">
            <span class="text-[color:var(--gv-amber)]">»</span> {{ $title }}
        </h1>
        @if($subtitle)
            <p class="mt-0.5 text-sm text-[color:var(--gv-fg4)]">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</header>
