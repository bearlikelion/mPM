@props([
    'html' => null,
    'empty' => null,
])

@php
    $raw = (string) ($html ?? '');
    $hasMarkup = $raw !== '' && preg_match('/<[a-z][^>]*>/i', $raw) === 1;
    $rendered = $hasMarkup
        ? \Mews\Purifier\Facades\Purifier::clean($raw, 'tiptap')
        : ($raw !== '' ? '<p>'.nl2br(e($raw), false).'</p>' : '');
@endphp

@if($rendered === '')
    @if($empty !== null)
        <div {{ $attributes->merge(['class' => 'text-sm text-[color:var(--gv-fg4)]']) }}>{{ $empty }}</div>
    @endif
@else
    <div {{ $attributes->merge(['class' => 'prose-tiptap']) }}>{!! $rendered !!}</div>
@endif
