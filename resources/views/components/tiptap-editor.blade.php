@props([
    'label' => null,
    'placeholder' => 'write here…',
    'orgId' => null,
    'rows' => 6,
])

@php
    $resolvedOrgId = $orgId ?? optional(app(\App\Support\SiteTenant::class)->currentOrganization(auth()->user()))->id;
    $minHeight = max(2, (int) $rows) * 1.5;
@endphp

<div
    class="tiptap-field"
    x-data="tiptap('', @js($placeholder), @js($resolvedOrgId))"
    x-init="
        value = $refs.source.value;
        $watch('value', () => {
            if ($refs.source.value === value) return;
            $refs.source.value = value;
            $refs.source.dispatchEvent(new Event('input', { bubbles: true }));
        });
        $refs.source.addEventListener('input', () => {
            if (value === $refs.source.value) return;
            value = $refs.source.value;
        });
    "
>
    @if($label)
        <label class="mb-1 block text-sm font-medium text-[color:var(--gv-fg2)]">{{ $label }}</label>
    @endif

    <div class="tiptap-shell">
        <div class="tiptap-toolbar" role="toolbar" aria-label="formatting" x-on:mousedown.prevent>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('bold') && 'is-active'" x-on:click="run('toggleBold')" title="bold"><strong>B</strong></button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('italic') && 'is-active'" x-on:click="run('toggleItalic')" title="italic"><em>I</em></button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('strike') && 'is-active'" x-on:click="run('toggleStrike')" title="strike"><s>S</s></button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('code') && 'is-active'" x-on:click="run('toggleCode')" title="inline code">{{ '<>' }}</button>
            <span class="tiptap-divider"></span>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('heading', { level: 2 }) && 'is-active'" x-on:click="run('toggleHeading', { level: 2 })" title="heading 2">H2</button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('heading', { level: 3 }) && 'is-active'" x-on:click="run('toggleHeading', { level: 3 })" title="heading 3">H3</button>
            <span class="tiptap-divider"></span>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('bulletList') && 'is-active'" x-on:click="run('toggleBulletList')" title="bullet list">&bull;</button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('orderedList') && 'is-active'" x-on:click="run('toggleOrderedList')" title="ordered list">1.</button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('blockquote') && 'is-active'" x-on:click="run('toggleBlockquote')" title="quote">&ldquo;</button>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('codeBlock') && 'is-active'" x-on:click="run('toggleCodeBlock')" title="code block">{{ '</>' }}</button>
            <span class="tiptap-divider"></span>
            <button type="button" tabindex="-1" class="tiptap-tool" :class="isActive('link') && 'is-active'" x-on:click="toggleLink()" title="link">&#8599;</button>
        </div>

        <div
            x-ref="editor"
            class="tiptap-editor"
            style="min-height: {{ $minHeight }}rem;"
            wire:ignore
        ></div>

        <textarea
            x-ref="source"
            class="tiptap-source sr-only"
            tabindex="-1"
            aria-hidden="true"
            {{ $attributes->whereStartsWith('wire:model') }}
        ></textarea>
    </div>
</div>
